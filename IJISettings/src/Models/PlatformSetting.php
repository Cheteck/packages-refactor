<?php

namespace IJIDeals\IJISettings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $table;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        // 'value' will be handled by accessor/mutator based on 'type' and 'is_encrypted'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('ijisettings.table_name', 'platform_settings');
    }

    /**
     * Get the setting's value.
     * Automatically decrypts if 'is_encrypted' is true.
     * Casts to defined 'type' if applicable.
     *
     * @param  string  $value
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        $actualValue = $this->attributes['value']; // Get raw value before Eloquent casting

        if ($this->is_encrypted) {
            try {
                $actualValue = Crypt::decryptString($actualValue);
            } catch (DecryptException $e) {
                // Log error or handle appropriately - potentially return null or throw an exception
                // For now, returning null if decryption fails for a supposedly encrypted value
                report($e); // Log the exception
                return null;
            }
        }

        switch ($this->type) {
            case 'boolean':
            case 'bool':
                return filter_var($actualValue, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
            case 'int':
                return (int) $actualValue;
            case 'float':
            case 'double':
            case 'decimal':
                return (float) $actualValue;
            case 'array':
            case 'json':
                // If it was encrypted, it's already a string here. If not, it might be already an array/object if DB supports JSON type.
                return is_array($actualValue) ? $actualValue : json_decode($actualValue, true);
            case 'string':
            default:
                return (string) $actualValue;
        }
    }

    /**
     * Set the setting's value.
     * Automatically encrypts if 'is_encrypted' is true.
     * Serializes arrays/objects to JSON if type is 'array' or 'json'.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setValueAttribute($value): void
    {
        $type = $this->attributes['type'] ?? 'string'; // Get type, default to string if not set
        $isEncrypted = $this->attributes['is_encrypted'] ?? false;

        $processedValue = $value;

        switch ($type) {
            case 'boolean':
            case 'bool':
                $processedValue = $value ? '1' : '0';
                break;
            case 'array':
            case 'json':
                $processedValue = json_encode($value);
                break;
            case 'integer':
            case 'int':
                $processedValue = (string)(int) $value;
                 break;
            case 'float':
            case 'double':
            case 'decimal':
                $processedValue = (string)(float) $value;
                break;
            case 'string':
            default:
                $processedValue = (string) $value;
                break;
        }

        if ($isEncrypted) {
            $this->attributes['value'] = Crypt::encryptString($processedValue);
        } else {
            $this->attributes['value'] = $processedValue;
        }
    }
}
