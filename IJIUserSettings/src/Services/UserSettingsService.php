<?php

namespace IJIDeals\IJIUserSettings\Services;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use IJIDeals\IJIUserSettings\Models\UserSetting;

class UserSettingsService
{
    protected SettingRegistry $registry;
    protected $cache;
    protected string $cachePrefix;
    protected int $cacheDuration;
    protected string $userSettingModel;

    public function __construct(SettingRegistry $registry, $cache, string $cachePrefix, int $cacheDuration)
    {
        $this->registry = $registry;
        $this->cache = $cache;
        $this->cachePrefix = $cachePrefix;
        $this->cacheDuration = $cacheDuration;
        $this->userSettingModel = config('ijiusersettings.user_setting_model', UserSetting::class);
    }

    protected function getCacheKeyForUser(int $userId, string $settingKey = ''): string
    {
        return $this->cachePrefix . $userId . ($settingKey ? '.' . $settingKey : '.all_settings');
    }

    /**
     * Get a resolved setting value for a user.
     * It checks user-specific settings first, then falls back to declared defaults.
     *
     * @param UserContract $user
     * @param string $key
     * @param mixed $fallback Default value if no user setting and no declared default.
     * @return mixed
     */
    public function getResolvedSetting(UserContract $user, string $key, $fallback = null)
    {
        $cacheKey = $this->getCacheKeyForUser($user->getAuthIdentifier(), $key);

        // Attempt to get from cache
        // Note: Caching individual settings can lead to many cache keys.
        // Caching all user settings might be more efficient if many are accessed per request.
        // For now, let's cache individually for simplicity in this example.
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $userSetting = $this->userSettingModel::where('user_id', $user->getAuthIdentifier())
            ->where('key', $key)
            ->first();

        $value = null;
        $foundInDb = false;

        if ($userSetting) {
            $value = $userSetting->value; // Accessor handles type casting & decryption
            $foundInDb = true;
        } else {
            $declaration = $this->registry->getDeclaration($key);
            if ($declaration && array_key_exists('default', $declaration)) {
                $value = $declaration['default'];
                 // We might need to cast this default value based on declared type as well
                $type = $declaration['type'] ?? 'string';
                $value = $this->castValue($value, $type);

            } else {
                $value = $fallback;
            }
        }

        // Cache the resolved value
        if ($this->cacheDuration > 0) {
            $this->cache->put($cacheKey, $value, now()->addMinutes($this->cacheDuration));
        } else {
            $this->cache->forever($cacheKey, $value);
        }

        return $value;
    }

    /**
     * Get all resolved settings for a user, grouped by their declared group.
     *
     * @param UserContract $user
     * @return Collection
     */
    public function getResolvedSettingsForUserByGroup(UserContract $user): Collection
    {
        $allDeclarations = $this->registry->getAllDeclarations();
        $userSettings = $this->userSettingModel::where('user_id', $user->getAuthIdentifier())
            ->get()
            ->keyBy('key');

        $resolvedSettings = new Collection();

        foreach ($allDeclarations as $key => $declaration) {
            $group = $declaration['group'] ?? 'general';
            $value = null;

            if (isset($userSettings[$key])) {
                $value = $userSettings[$key]->value; // Accessor handles type casting
            } else {
                $value = $declaration['default'] ?? null;
                $type = $declaration['type'] ?? 'string';
                $value = $this->castValue($value, $type);
            }

            if (!$resolvedSettings->has($group)) {
                $resolvedSettings->put($group, new Collection());
            }
            $resolvedSettings[$group]->put($key, [
                'label' => $declaration['label'] ?? $key,
                'description' => $declaration['description'] ?? '',
                'type' => $declaration['type'] ?? 'string',
                'value' => $value,
                'options' => $declaration['options'] ?? null,
            ]);
        }
        return $resolvedSettings;
    }


    /**
     * Set a user-specific setting.
     *
     * @param UserContract $user
     * @param string $key
     * @param mixed $value
     * @param string|null $group
     * @param string|null $type
     * @return UserSetting
     * @throws ValidationException
     */
    public function setForUser(UserContract $user, string $key, $value, ?string $group = null, ?string $type = null): UserSetting
    {
        $declaration = $this->registry->getDeclaration($key);
        $declaredType = $type ?: ($declaration['type'] ?? 'string');
        $declaredGroup = $group ?: ($declaration['group'] ?? 'default');
        $validationRules = $declaration['rules'] ?? [];

        if (!empty($validationRules)) {
            $validator = ValidatorFacade::make([$key => $value], [$key => $validationRules]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        // Cast value before saving if type is boolean, integer, or float to store consistently
        // The model's mutator will handle array/json and encryption
        $castedValue = $this->prepareValueForStorage($value, $declaredType);

        $setting = $this->userSettingModel::updateOrCreate(
            [
                'user_id' => $user->getAuthIdentifier(),
                'key' => $key,
            ],
            [
                'value' => $castedValue, // Model mutator will handle further processing
                'type' => $declaredType,
                'group' => $declaredGroup,
            ]
        );

        // Invalidate cache for this specific setting and any grouped caches
        $this->cache->forget($this->getCacheKeyForUser($user->getAuthIdentifier(), $key));
        $this->cache->forget($this->getCacheKeyForUser($user->getAuthIdentifier())); // Invalidate "all_settings" cache for user

        return $setting;
    }

    /**
     * Prepares value for storage, mainly for basic types.
     * Model mutator handles complex types like array/json and encryption.
     */
    protected function prepareValueForStorage($value, string $type)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                return $value ? '1' : '0'; // Store booleans as '1' or '0'
            // For other types like integer, float, string, array, json, encrypted_string
            // the model's setValueAttribute will handle it.
            // We pass the raw value to the model.
            default:
                return $value;
        }
    }

    /**
     * Casts a value to its declared type.
     * Primarily for default values from config, as model accessors handle DB values.
     */
    protected function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
            case 'int':
                return (int) $value;
            case 'float':
            case 'double':
            case 'decimal':
                return (float) $value;
            case 'array':
                 // If default value is already an array, return it. If string, try to decode.
                return is_array($value) ? $value : json_decode($value, true);
            case 'json': // JSON should ideally be stored as string and decoded
                return is_string($value) ? json_decode($value, true) : $value;
            // 'encrypted_string' default values should typically not be pre-encrypted in config
            // but rather stored as plain text and encrypted on first set if needed.
            // Or, if they are, the get() method in PlatformSettingService handles decryption.
            // For user settings, default values are not encrypted.
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Get all declared settings (for UI generation, etc.).
     *
     * @return array
     */
    public function getAllDeclaredSettings(): array
    {
        return $this->registry->getAllDeclarations();
    }

    /**
     * Get declared settings for a specific group.
     *
     * @param string $group
     * @return array
     */
    public function getDeclaredSettingsByGroup(string $group): array
    {
        return $this->registry->getDeclarationsByGroup($group);
    }
}
