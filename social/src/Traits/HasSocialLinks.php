<?php

namespace IJIDeals\Social\Traits;

use IJIDeals\Social\Models\SocialLink;
use IJIDeals\Social\Models\SocialNetwork;
use Illuminate\Support\Facades\Validator;

/**
 * Trait HasSocialLinks
 *
 * Provides functionality to manage social media links for models.
 *
 * @category SocialLinksManager
 *
 * @author Your Name <your.email@example.com>
 * @license MIT
 *
 * @link https://example.com
 */
trait HasSocialLinks
{
    /**
     * Relation with social links.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function socialLinks()
    {
        return $this->morphMany(SocialLink::class, 'socialable');
    }

    /**
     * Add a new social link for the model.
     *
     * @param  int  $socialNetworkId  The ID of the social network
     * @param  string  $url  The URL of the social media profile
     * @param  string|null  $username  The username on the social network
     *
     * @throws \Exception
     */
    public function addSocialLink(int $socialNetworkId, string $url, ?string $username = null): SocialLink
    {
        $socialNetwork = SocialNetwork::query()->findOrFail($socialNetworkId);
        $this->validateSocialLink($socialNetwork, $url);

        /** @var SocialLink $socialLink */
        $socialLink = $this->socialLinks()->create([
            'social_network_id' => $socialNetworkId,
            'url' => $url,
            'username' => $username,
        ]);

        return $socialLink;
    }

    /**
     * Remove a social link by social network ID.
     *
     * @param  int  $socialNetworkId  The ID of the social network to remove
     * @return int Number of deleted links
     */
    public function removeSocialLink(int $socialNetworkId): int
    {
        return $this->socialLinks()
            ->where('social_network_id', $socialNetworkId)
            ->delete();
    }

    /**
     * Get a specific social link by social network ID.
     *
     * @param  int  $socialNetworkId  The ID of the social network
     */
    public function getSocialLink(int $socialNetworkId): ?SocialLink
    {
        return $this->socialLinks()
            ->where('social_network_id', $socialNetworkId)
            ->first();
    }

    /**
     * Get all social links for the model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSocialLinks()
    {
        return $this->socialLinks;
    }

    /**
     /**
     * Validate a social link URL.
     *
     * @param  SocialNetwork  $socialNetwork  The SocialNetwork model
     * @param  string  $url  The URL to validate
     *
     * @throws \Exception
     */
    protected function validateSocialLink(SocialNetwork $socialNetwork, string $url): void
    {
        try {
            Validator::make(
                ['url' => $url],
                ['url' => ['required', 'url']]
            )->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw new \Exception(
                __('social_links.invalid_url', ['network' => $socialNetwork->name, 'url' => $url]),
                0,
                $e
            );
        }
    }
}
