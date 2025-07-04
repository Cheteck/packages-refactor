<?php

namespace IJIDeals\IJIUserSettings\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

class SettingRegistry
{
    protected array $declaredSettings = [];
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loadDeclaredSettings();
    }

    protected function loadDeclaredSettings(): void
    {
        $method = $this->config['discovery_method'] ?? 'directory';

        if ($method === 'directory') {
            $this->loadFromDirectory(config_path($this->config['declarations_path'] ?? 'user_settings_declarations'));
        } elseif ($method === 'config') {
            $this->declaredSettings = $this->config['declarations_config_array'] ?? [];
        }
        // 'programmatic' method would mean other service providers call a method on this registry.
    }

    /**
     * Load setting declarations from all .php files in a given directory.
     * Each file is expected to return an array of setting definitions.
     */
    public function loadFromDirectory(string $directoryPath): void
    {
        if (!File::isDirectory($directoryPath)) {
            return;
        }

        $files = File::files($directoryPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                try {
                    $declarations = require $file->getRealPath();
                    if (is_array($declarations)) {
                        $this->declaredSettings = array_merge($this->declaredSettings, $declarations);
                    }
                } catch (\Throwable $e) {
                    // Log error or handle - a file in the directory might not be a valid settings declaration
                    report("Error loading user setting declarations from file: " . $file->getRealPath() . " - " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Programmatically declare a setting.
     * Useful if 'programmatic' discovery_method is used or for dynamic additions.
     */
    public function declareSetting(string $key, array $attributes): void
    {
        // Potentially validate attributes structure here
        $this->declaredSettings[$key] = $attributes;
    }

    /**
     * Get the declaration for a specific setting key.
     *
     * @param string $key
     * @return array|null
     */
    public function getDeclaration(string $key): ?array
    {
        return $this->declaredSettings[$key] ?? null;
    }

    /**
     * Get all declared settings.
     *
     * @return array
     */
    public function getAllDeclarations(): array
    {
        return $this->declaredSettings;
    }

    /**
     * Get all declared settings for a specific group.
     *
     * @param string $group
     * @return array
     */
    public function getDeclarationsByGroup(string $group): array
    {
        return array_filter($this->declaredSettings, function ($setting) use ($group) {
            return ($setting['group'] ?? null) === $group;
        });
    }

    /**
     * Get the default value for a declared setting.
     *
     * @param string $key
     * @param mixed $fallback Default to return if the setting or its default is not declared.
     * @return mixed
     */
    public function getDefaultValue(string $key, $fallback = null)
    {
        return Arr::get($this->declaredSettings, "{$key}.default", $fallback);
    }

    /**
     * Get the type for a declared setting.
     *
     * @param string $key
     * @return string|null
     */
    public function getType(string $key): ?string
    {
        return Arr::get($this->declaredSettings, "{$key}.type");
    }

     /**
     * Get the validation rules for a declared setting.
     *
     * @param string $key
     * @return array|null
     */
    public function getValidationRules(string $key): ?array
    {
        return Arr::get($this->declaredSettings, "{$key}.rules");
    }
}
