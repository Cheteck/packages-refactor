<?php

namespace IJIDeals\Social\Models;

use IJIDeals\Social\Enums\ActivityTypeEnum;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Activity",
 *     title="Activity",
 *     description="Modèle représentant une activité enregistrée dans le système (logs)",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="event", type="string", description="Type of activity/event", enum={"user_created", "post_created", "comment_created", "reaction_created"}),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="user_id", type="integer", format="int64", description="User who performed the activity"),
 *     @OA\Property(property="subject_type", type="string", nullable=true, description="Polymorphic subject type (maps to loggable_type)"),
 *     @OA\Property(property="subject_id", type="integer", format="int64", nullable=true, description="Polymorphic subject ID (maps to loggable_id)"),
 *     @OA\Property(property="properties", type="object", nullable=true, description="Additional properties for the activity"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Activity extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'event',
        'description',
        'user_id',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'event' => ActivityTypeEnum::class,
        'properties' => 'json',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo('loggable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function (Activity $activity) {
            if (empty($activity->description)) {
                // Get the raw value that was set for 'event' during Activity::create()
                // This will be the string value from the seeder.
                $eventValue = $activity->getAttributes()['event'] ?? null;

                if (is_string($eventValue) && !empty($eventValue)) {
                    try {
                        $eventEnumInstance = ActivityTypeEnum::from($eventValue);
                        $activity->description = static::getDefaultDescription($eventEnumInstance);
                    } catch (\ValueError $e) {
                        Log::warning("Activity model 'creating' event: Could not generate default description. Invalid string value for event type.", [
                            'event_value' => $eventValue,
                            'error' => $e->getMessage(),
                            'activity_attributes' => $activity->getAttributes()
                        ]);
                        $activity->description = "Activity of type '{$eventValue}'";
                    }
                } else {
                    Log::warning("Activity model 'creating' event: Could not generate default description. Event type is null, empty, or not a string.", [
                        'event_value_received' => $eventValue,
                        'activity_attributes' => $activity->getAttributes()
                    ]);
                    $activity->description = "Activity of undetermined type";
                }
            }
        });
    }

    public static function getDefaultDescription(ActivityTypeEnum $eventEnum): string
    {
        return ucfirst("Nouvelle activité de type {$eventEnum->value}");
    }

    public static function rules(): array
    {
        return [
            'event' => 'required|string|max:255|in:'.implode(',', ActivityTypeEnum::values()),
            'description' => 'nullable|string|max:500',
            'user_id' => 'required|exists:users,id',
            'subject_type' => 'nullable|string|max:255',
            'subject_id' => 'nullable|integer|min:1',
            'properties' => 'nullable|array',
        ];
    }

    public static function validateActivity(array $data): ValidatorContract
    {
        return Validator::make($data, static::rules());
    }
}
