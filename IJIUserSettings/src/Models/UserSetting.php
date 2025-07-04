<?php

namespace IJIDeals\IJIUserSettings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class UserSetting extends Model
{
    use HasFactory;

    protected $table;

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'group',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        // 'value' will be casted dynamically via accessor/mutator based on 'type'
        // 'is_encrypted' => 'boolean', // If we add an is_encrypted column
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('ijiusersettings.table_name', 'user_settings');
    }

    /**
     * Get the user that owns the setting.
     */
    public function user()
    {
        // Assumes the User model is the one configured in auth.providers.users.model
        // or specifically in ijiusersettings.user_model if that config exists.
        $userModel = config('ijiusersettings.user_model', config('auth.providers.users.model', \App\Models\User::class));
        return $this->belongsTo($userModel);
    }

    /**
     * Get the setting's value, casting it to the appropriate type.
     * Handles decryption if the setting type indicates encryption.
     *
     * @param  string  $value
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case 'boolean':
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
            case 'int':
                return (int) $value;
            case 'float':
            case 'double':
            case 'decimal': // Assuming decimal is stored as string and cast here
                return (float) $value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            case 'encrypted_string':
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $e) {
                    // Log error or handle appropriately
                    return null; // Or throw an exception
                }
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Set the setting's value, casting arrays/JSON to string and encrypting if necessary.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setValueAttribute($value): void
    {
        $type = $this->attributes['type'] ?? 'string'; // Get type, default to string if not set

        switch ($type) {
            case 'boolean':
            case 'bool':
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case 'array':
            case 'json':
                $this->attributes['value'] = json_encode($value);
                break;
            case 'encrypted_string':
                $this->attributes['value'] = Crypt::encryptString((string) $value);
                break;
            case 'integer':
            case 'int':
                $this->attributes['value'] = (string)(int) $value;
                 break;
            case 'float':
            case 'double':
            case 'decimal':
                $this->attributes['value'] = (string)(float) $value;
                break;
            case 'string':
            default:
                $this->attributes['value'] = (string) $value;
                break;
        }
    }
}
