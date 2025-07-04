<?php

namespace IJIDeals\IJIUserSettings\Traits;

use IJIDeals\IJIUserSettings\Models\UserSetting;
use IJIDeals\IJIUserSettings\Services\UserSettingsService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasUserSettings
{
    /**
     * Get all settings for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userSettings(): HasMany
    {
        $userSettingModel = config('ijiusersettings.user_setting_model', UserSetting::class);
        return $this->hasMany($userSettingModel);
    }

    /**
     * Get a specific setting value for the user.
     * This method resolves the setting by checking user-specific value first,
     * then falling back to declared defaults via UserSettingsService.
     *
     * @param string $key The setting key (e.g., 'notifications.newsletter.enabled').
     * @param mixed $default Fallback value if no user-specific or declared default value is found.
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        return app(UserSettingsService::class)->getResolvedSetting($this, $key, $default);
    }

    /**
     * Get multiple setting values for the user.
     *
     * @param array $keys An array of setting keys.
     * @param array $defaults An associative array of fallback values [key => default].
     * @return \Illuminate\Support\Collection
     */
    public function getSettings(array $keys, array $defaults = []): Collection
    {
        $settings = new Collection();
        foreach ($keys as $key) {
            $settings->put($key, $this->getSetting($key, $defaults[$key] ?? null));
        }
        return $settings;
    }

    /**
     * Get all resolved settings for a specific group for the user.
     *
     * @param string $group
     * @return \Illuminate\Support\Collection
     */
    public function getSettingsByGroup(string $group): Collection
    {
        return app(UserSettingsService::class)->getResolvedSettingsForUserByGroup($this, $group);
    }

    /**
     * Set a specific setting value for the user.
     *
     * @param string $key The setting key.
     * @param mixed $value The value to set.
     * @param string|null $group Optional group for the setting, primarily used if the setting is not pre-declared.
     * @param string|null $type Optional type for the setting, primarily used if the setting is not pre-declared.
     * @return UserSetting|null The saved UserSetting model instance or null on failure.
     */
    public function setSetting(string $key, $value, ?string $group = null, ?string $type = null): ?UserSetting
    {
        return app(UserSettingsService::class)->setForUser($this, $key, $value, $group, $type);
    }

    /**
     * Set multiple settings for the user.
     *
     * @param array $settingsData Array of settings, e.g., [['key' => 'key1', 'value' => 'val1'], ...]
     *                            or ['key1' => 'val1', 'key2' => 'val2']
     * @return \Illuminate\Support\Collection Collection of UserSetting models that were set.
     */
    public function setSettings(array $settingsData): Collection
    {
        $results = new Collection();
        // Handle both indexed array of arrays and associative array
        if (isset($settingsData[0]) && is_array($settingsData[0])) {
            foreach ($settingsData as $setting) {
                if (isset($setting['key']) && array_key_exists('value', $setting)) {
                    $results->push($this->setSetting(
                        $setting['key'],
                        $setting['value'],
                        $setting['group'] ?? null,
                        $setting['type'] ?? null
                    ));
                }
            }
        } else { // Associative array
            foreach ($settingsData as $key => $value) {
                $results->push($this->setSetting($key, $value));
            }
        }
        return $results->filter(); // Remove nulls if setSetting failed
    }

    /**
     * Check if a user has an explicit (non-default) setting for a given key.
     *
     * @param string $key
     * @return bool
     */
    public function hasExplicitSetting(string $key): bool
    {
        return $this->userSettings()->where('key', $key)->exists();
    }

    /**
     * Forget (delete) a user-specific setting, causing it to revert to the declared default.
     *
     * @param string $key
     * @return bool
     */
    public function forgetSetting(string $key): bool
    {
        return (bool) $this->userSettings()->where('key', $key)->delete();
    }

    /**
     * Get all settings explicitly set for the user from the database.
     * This does not include resolved default values.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllExplicitSettings(): Collection
    {
        return $this->userSettings()->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->value]; // Value will be casted by accessor
        });
    }
}
