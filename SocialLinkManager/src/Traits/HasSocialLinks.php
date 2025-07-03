<?php

namespace IJIDeals\SocialLinkManager\Traits;

use IJIDeals\SocialLinkManager\Models\SocialLink;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection; // For type hinting
use Illuminate\Support\Facades\Validator; // For validation
use Illuminate\Validation\Rule; // For Rule::in

trait HasSocialLinks
{
    /**
     * Get all of the model's social links.
     */
    public function socialLinks(): MorphMany
    {
        $socialLinkModel = config('socialinkmanager.social_link_model', SocialLink::class);
        return $this->morphMany($socialLinkModel, 'social_linkable')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('platform_key', 'asc'); // Secondary sort for consistency
    }

    /**
     * Add a social link to the model.
     * If a link for the platform_key already exists, it will be updated.
     *
     * @param string $platformKey The key of the social platform (e.g., 'facebook', 'twitter').
     * @param string $url The URL of the social link.
     * @param array $attributes Optional attributes: 'label', 'is_public', 'sort_order'.
     * @return SocialLink
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addSocialLink(string $platformKey, string $url, array $attributes = []): SocialLink
    {
        $this->validatePlatformKey($platformKey);
        $this->validateUrl($url, $platformKey);

        $defaultAttributes = [
            'label' => null,
            'is_public' => true,
            'sort_order' => 0,
        ];
        $data = array_merge($defaultAttributes, $attributes, ['platform_key' => $platformKey, 'url' => $url]);

        // Unset any attributes not in SocialLink's fillable to prevent mass assignment issues if extra data is passed
        $fillableAttributes = (new SocialLink())->getFillable();
        $cleanData = collect($data)->only($fillableAttributes)->toArray();

        return $this->socialLinks()->updateOrCreate(
            ['platform_key' => $platformKey], // Find by platform_key for this model
            $cleanData // Data to create or update with
        );
    }

    /**
     * Update an existing social link for a specific platform.
     * If attributes beyond 'url' and 'label' need updating (like is_public, sort_order),
     * it's often better to fetch the SocialLink model and update it directly, or use syncSocialLinks.
     * This method primarily focuses on URL and label for a given platform.
     *
     * @param string $platformKey The key of the social platform.
     * @param string $newUrl The new URL.
     * @param string|null $newLabel Optional new label.
     * @return SocialLink|null Returns the updated SocialLink or null if not found.
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateSocialLink(string $platformKey, string $newUrl, ?string $newLabel = null): ?SocialLink
    {
        $this->validatePlatformKey($platformKey);
        $this->validateUrl($newUrl, $platformKey);

        $link = $this->socialLinks()->where('platform_key', $platformKey)->first();

        if ($link) {
            $link->url = $newUrl;
            if ($newLabel !== null || array_key_exists('label', $attributes = func_get_args()[2] ?? [])) { // Check if label was explicitly passed
                $link->label = $newLabel;
            }
            $link->save();
            return $link;
        }
        return null;
    }

    /**
     * Update a social link by its ID.
     * More flexible for updating any attribute like is_public, sort_order.
     *
     * @param int $socialLinkId The ID of the SocialLink record.
     * @param array $attributes Attributes to update ('url', 'label', 'is_public', 'sort_order'). platform_key cannot be changed.
     * @return SocialLink|null
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateSocialLinkById(int $socialLinkId, array $attributes): ?SocialLink
    {
        $link = $this->socialLinks()->find($socialLinkId);
        if (!$link) {
            return null;
        }

        if (isset($attributes['url'])) {
            $this->validateUrl($attributes['url'], $link->platform_key);
        }

        // Filter attributes to only include fillable ones, excluding platform_key
        $fillable = (new SocialLink())->getFillable();
        $updateData = collect($attributes)->only(array_diff($fillable, ['platform_key']))->toArray();


        if (!empty($updateData)) {
            $link->update($updateData);
        }
        return $link;
    }


    /**
     * Remove a social link for a specific platform.
     *
     * @param string $platformKey The key of the social platform.
     * @return bool True if a link was deleted, false otherwise.
     */
    public function removeSocialLink(string $platformKey): bool
    {
        $this->validatePlatformKey($platformKey); // Optional: validate if platform exists before attempting delete
        $deletedCount = $this->socialLinks()->where('platform_key', $platformKey)->delete();
        return $deletedCount > 0;
    }

    /**
     * Remove a social link by its ID.
     * @param int $socialLinkId
     * @return bool
     */
    public function removeSocialLinkById(int $socialLinkId): bool
    {
        $link = $this->socialLinks()->find($socialLinkId);
        if ($link) {
            return $link->delete();
        }
        return false;
    }

    /**
     * Get a specific social link by platform key.
     *
     * @param string $platformKey The key of the social platform.
     * @param bool $publicOnly Whether to only retrieve public links.
     * @return SocialLink|null
     */
    public function getSocialLink(string $platformKey, bool $publicOnly = true): ?SocialLink
    {
        $query = $this->socialLinks()->where('platform_key', $platformKey);
        if ($publicOnly) {
            $query->where('is_public', true);
        }
        return $query->first();
    }

    /**
     * Get all social links for the model.
     *
     * @param bool $publicOnly Whether to only retrieve public links.
     * @return \Illuminate\Database\Eloquent\Collection|\IJIDeals\SocialLinkManager\Models\SocialLink[]
     */
    public function getSocialLinks(bool $publicOnly = true): Collection
    {
        $query = $this->socialLinks(); // Already ordered by sort_order, platform_key
        if ($publicOnly) {
            $query->where('is_public', true);
        }
        return $query->get();
    }

    /**
     * Check if the model has a social link for a specific platform.
     *
     * @param string $platformKey The key of the social platform.
     * @param bool $publicOnly Whether to only check for public links.
     * @return bool
     */
    public function hasSocialLink(string $platformKey, bool $publicOnly = true): bool
    {
        return $this->getSocialLink($platformKey, $publicOnly) !== null;
    }

    /**
     * Sync social links for the model.
     * Accepts an array of link data. Existing links not in the array will be removed.
     * New links will be added. Existing links (by platform_key) will be updated.
     *
     * Example $links array:
     * [
     *   ['platform_key' => 'twitter', 'url' => 'https://twitter.com/newuser', 'label' => 'Follow me', 'is_public' => true, 'sort_order' => 0],
     *   ['platform_key' => 'facebook', 'url' => 'https://facebook.com/newuser'], // label, is_public, sort_order use defaults
     * ]
     *
     * @param array $linksData
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function syncSocialLinks(array $linksData): void
    {
        $currentPlatformKeys = $this->socialLinks()->pluck('platform_key')->toArray();
        $providedPlatformKeys = [];

        $defaultAttributes = [
            'label' => null,
            'is_public' => true,
            'sort_order' => 0,
        ];
        $fillableAttributes = (new SocialLink())->getFillable();

        foreach ($linksData as $linkData) {
            Validator::make($linkData, [
                'platform_key' => ['required', 'string', Rule::in(array_keys(config('socialinkmanager.platforms', [])))],
                'url' => ['required', 'string', 'url', 'max:2048'],
                'label' => 'nullable|string|max:255',
                'is_public' => 'nullable|boolean',
                'sort_order' => 'nullable|integer',
            ])->validate();

            $this->validateUrl($linkData['url'], $linkData['platform_key']); // Custom platform regex validation

            $data = array_merge($defaultAttributes, $linkData);
            $cleanData = collect($data)->only($fillableAttributes)->toArray();


            $this->socialLinks()->updateOrCreate(
                ['platform_key' => $data['platform_key']],
                $cleanData
            );
            $providedPlatformKeys[] = $data['platform_key'];
        }

        // Remove links that were not in the provided array
        $keysToRemove = array_diff($currentPlatformKeys, $providedPlatformKeys);
        if (!empty($keysToRemove)) {
            $this->socialLinks()->whereIn('platform_key', $keysToRemove)->delete();
        }
    }

    /**
     * Helper to validate platform key.
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validatePlatformKey(string $platformKey): void
    {
        Validator::make(['platform_key' => $platformKey], [
            'platform_key' => ['required', 'string', Rule::in(array_keys(config('socialinkmanager.platforms', [])))],
        ])->validate();
    }

    /**
     * Helper to validate URL, potentially against platform-specific regex.
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateUrl(string $url, string $platformKey): void
    {
        $baseRules = ['required', 'string', 'url', 'max:2048'];
        $platformConfig = config("socialinkmanager.platforms.{$platformKey}");
        $regex = $platformConfig['validation_regex'] ?? null;

        if ($regex) {
            $baseRules[] = "regex:{$regex}";
        }

        Validator::make(['url' => $url], ['url' => $baseRules])->validate();
    }
}
