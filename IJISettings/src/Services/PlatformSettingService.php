<?php

namespace IJIDeals\IJISettings\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use IJIDeals\IJISettings\Models\PlatformSetting; // Assuming the model will be created here

class PlatformSettingService
{
    protected $cache;
    protected string $cachePrefix;
    protected ?int $cacheDuration; // minutes, null for rememberForever

    public function __construct($cache, string $cachePrefix, ?int $cacheDuration)
    {
        $this->cache = $cache;
        $this->cachePrefix = $cachePrefix;
        $this->cacheDuration = $cacheDuration;
    }

    protected function getCacheKey(string $key): string
    {
        return $this->cachePrefix . $key;
    }

    /**
     * Get a platform setting value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->getCacheKey($key);

        $getValue = function () use ($key, $default) {
            $setting = PlatformSetting::where('key', $key)->first();

            if (!$setting) {
                // Check if the key exists in default_settings config
                $defaultSettings = config('ijisettings.default_settings', []);
                if (array_key_exists($key, $defaultSettings) && isset($defaultSettings[$key]['value'])) {
                    // If we want to treat config defaults as actual settings if not in DB
                    // For now, let's assume 'default' parameter is the ultimate fallback if not in DB
                    // Or, we could seed these defaults into the DB.
                    // For this implementation, if not in DB, use the passed $default.
                    return $default;
                }
                return $default;
            }

            $value = $setting->value; // Accessor in model will handle decryption if needed
            $type = $setting->type ?? 'string';

            // Perform casting if not handled by model accessor or if further casting is needed
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
                case 'json':
                    return is_array($value) ? $value : json_decode($value, true); // Model accessor should handle this
                default:
                    return $value; // String or already decrypted string
            }
        };

        if ($this->cacheDuration === null) {
            return $this->cache->rememberForever($cacheKey, $getValue);
        }

        return $this->cache->remember($cacheKey, now()->addMinutes($this->cacheDuration), $getValue);
    }

    /**
     * Set a platform setting value.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @param string|null $group
     * @param string|null $label
     * @param string|null $description
     * @param bool|null $isEncrypted If null, checks config('ijisettings.force_encrypt_keys')
     * @return PlatformSetting
     */
    public function set(
        string $key,
        $value,
        ?string $type = null,
        ?string $group = null,
        ?string $label = null,
        ?string $description = null,
        ?bool $isEncrypted = null
    ): PlatformSetting {
        $setting = PlatformSetting::firstOrNew(['key' => $key]);

        // Determine type if not provided
        if ($type === null) {
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_int($value)) {
                $type = 'integer';
            } elseif (is_float($value)) {
                $type = 'float';
            } elseif (is_array($value)) {
                $type = 'array'; // Will be stored as JSON
            } else {
                $type = 'string';
            }
        }
        $setting->type = $type;

        // Determine encryption
        if ($isEncrypted === null) {
            $isEncrypted = in_array($key, config('ijisettings.force_encrypt_keys', []));
        }
        $setting->is_encrypted = $isEncrypted;

        // The model's setValueAttribute will handle actual serialization and encryption
        $setting->value = $value;

        if ($group !== null) $setting->group = $group;
        if ($label !== null) $setting->label = $label;
        if ($description !== null) $setting->description = $description;

        $setting->save();

        $this->cache->forget($this->getCacheKey($key));
        $this->cache->forget($this->getCacheKey('all_platform_settings')); // Invalidate grouped cache if any

        return $setting;
    }

    /**
     * Get all platform settings.
     *
     * @param bool $ignoreCache
     * @return \Illuminate\Support\Collection
     */
    public function all(bool $ignoreCache = false): \Illuminate\Support\Collection
    {
        $cacheKey = $this->getCacheKey('all_platform_settings');

        if (!$ignoreCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $settings = PlatformSetting::all()->mapWithKeys(function ($setting) {
            // Use the accessor to get the potentially casted/decrypted value
            return [$setting->key => $setting->value];
        });

        if ($this->cacheDuration === null) {
            $this->cache->forever($cacheKey, $settings);
        } else {
            $this->cache->put($cacheKey, $settings, now()->addMinutes($this->cacheDuration));
        }

        return $settings;
    }

    /**
     * Get settings by group.
     *
     * @param string $group
     * @param bool $ignoreCache
     * @return \Illuminate\Support\Collection
     */
    public function getByGroup(string $group, bool $ignoreCache = false): \Illuminate\Support\Collection
    {
        // For simplicity, this implementation fetches all and then filters.
        // For very large numbers of settings, a dedicated cached query might be better.
        $allSettings = $this->all($ignoreCache);
        $platformSettingsModels = PlatformSetting::where('group', $group)->get();

        return $platformSettingsModels->mapWithKeys(function ($setting) {
             // Use the accessor to get the potentially casted/decrypted value
            return [$setting->key => $setting->value];
        });
    }

    /**
     * Check if a setting key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if ($this->cache->has($this->getCacheKey($key))) {
            return true;
        }
        return PlatformSetting::where('key', $key)->exists();
    }

    /**
     * Remove a setting from the database and cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $deleted = PlatformSetting::where('key', $key)->delete();
        if ($deleted) {
            $this->cache->forget($this->getCacheKey($key));
            $this->cache->forget($this->getCacheKey('all_platform_settings'));
            return true;
        }
        return false;
    }

    /**
     * Get a setting value and cast it to its defined type.
     * This is largely handled by the model's accessor now.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getTyped(string $key, $default = null)
    {
        return $this->get($key, $default); // The model accessor handles typing
    }
}
