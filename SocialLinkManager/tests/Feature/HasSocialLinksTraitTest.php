<?php

namespace IJIDeals\SocialLinkManager\Tests\Feature;

use IJIDeals\SocialLinkManager\Models\SocialLink;
use IJIDeals\SocialLinkManager\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use IJIDeals\SocialLinkManager\Traits\HasSocialLinks;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;


// Re-define TestLinkableModel here or ensure it's autoloadable for tests
if (!class_exists('\IJIDeals\SocialLinkManager\Tests\Feature\TestLinkableModelForTrait')) {
    class TestLinkableModelForTrait extends Model
    {
        use HasSocialLinks;
        protected $table = 'test_linkable_models_for_trait'; // Use a different table name
        protected $guarded = [];

        public static function setupTable()
        {
            if (!Schema::hasTable('test_linkable_models_for_trait')) {
                Schema::create('test_linkable_models_for_trait', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->timestamps();
                });
            }
        }
         public static function tearDownTable()
        {
            Schema::dropIfExists('test_linkable_models_for_trait');
        }
    }
}


class HasSocialLinksTraitTest extends TestCase
{
    use RefreshDatabase;

    protected TestLinkableModelForTrait $testModel;

    protected function setUp(): void
    {
        parent::setUp();
        TestLinkableModelForTrait::setupTable(); // Create the table for the test model
        $this->testModel = TestLinkableModelForTrait::create(['name' => 'Test Model with Links']);

        // Define some platforms in config for testing
        config(['socialinkmanager.platforms' => [
            'twitter' => ['name' => 'Twitter', 'validation_regex' => '/^https?:\/\/(www\.)?(twitter|x)\.com\/([a-zA-Z0-9_]{1,15})(\/)?$/'],
            'facebook' => ['name' => 'Facebook'],
            'linkedin' => ['name' => 'LinkedIn', 'base_url_pattern' => 'https://linkedin.com/in/{username}'],
        ]]);
        config(['socialinkmanager.defaults.is_public' => true]);
        config(['socialinkmanager.defaults.sort_order' => 0]);

    }

    protected function tearDown(): void
    {
        TestLinkableModelForTrait::tearDownTable();
        parent::tearDown();
    }


    /** @test */
    public function it_can_add_a_social_link()
    {
        $link = $this->testModel->addSocialLink('twitter', 'https://twitter.com/johndoe', ['label' => 'John D']);

        $this->assertInstanceOf(SocialLink::class, $link);
        $this->assertEquals('twitter', $link->platform_key);
        $this->assertEquals('https://twitter.com/johndoe', $link->url);
        $this->assertEquals('John D', $link->label);
        $this->assertTrue($this->testModel->socialLinks()->where('platform_key', 'twitter')->exists());
    }

    /** @test */
    public function add_social_link_updates_existing_link_for_same_platform()
    {
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/initial');
        $this->assertCount(1, $this->testModel->socialLinks);

        $updatedLink = $this->testModel->addSocialLink('twitter', 'https://twitter.com/updated', ['label' => 'Updated']);

        $this->assertCount(1, $this->testModel->socialLinks()->get()); // Still only one link for twitter
        $this->assertEquals('https://twitter.com/updated', $updatedLink->url);
        $this->assertEquals('Updated', $updatedLink->label);
    }

    /** @test */
    public function it_validates_platform_key_on_add()
    {
        $this->expectException(ValidationException::class);
        $this->testModel->addSocialLink('unknown_platform', 'https://example.com');
    }

    /** @test */
    public function it_validates_url_format_on_add()
    {
        $this->expectException(ValidationException::class);
        $this->testModel->addSocialLink('twitter', 'not_a_url');
    }

    /** @test */
    public function it_validates_url_against_platform_regex_if_defined()
    {
        // Valid twitter URL
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/validuser');
        $this->assertTrue($this->testModel->hasSocialLink('twitter'));

        // Invalid twitter URL based on regex
        $this->testModel->removeSocialLink('twitter'); // Clean up
        $this->expectException(ValidationException::class);
        $this->testModel->addSocialLink('twitter', 'https://example.com/notvalidtwitter');
    }

    /** @test */
    public function it_can_update_social_link_by_platform_key()
    {
        $this->testModel->addSocialLink('facebook', 'https://facebook.com/old');
        $updatedLink = $this->testModel->updateSocialLink('facebook', 'https://facebook.com/new', 'New FB');

        $this->assertNotNull($updatedLink);
        $this->assertEquals('https://facebook.com/new', $updatedLink->url);
        $this->assertEquals('New FB', $updatedLink->label);
    }

    /** @test */
    public function it_can_update_social_link_by_id()
    {
        $link = $this->testModel->addSocialLink('linkedin', 'https://linkedin.com/in/initial');
        $updateData = ['url' => 'https://linkedin.com/in/updated', 'is_public' => false, 'sort_order' => 5];

        $updated = $this->testModel->updateSocialLinkById($link->id, $updateData);

        $this->assertNotNull($updated);
        $this->assertEquals('https://linkedin.com/in/updated', $updated->url);
        $this->assertFalse($updated->is_public);
        $this->assertEquals(5, $updated->sort_order);
    }

    /** @test */
    public function update_social_link_by_id_does_not_change_platform_key()
    {
        $link = $this->testModel->addSocialLink('twitter', 'https://twitter.com/user');
        $this->testModel->updateSocialLinkById($link->id, ['platform_key' => 'facebook', 'url' => 'https://facebook.com/user']);

        $this->assertEquals('twitter', $link->fresh()->platform_key); // Should not change
        $this->assertEquals('https://facebook.com/user', $link->fresh()->url); // URL should change
    }


    /** @test */
    public function it_can_remove_social_link_by_platform_key()
    {
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/user');
        $this->assertTrue($this->testModel->hasSocialLink('twitter'));

        $wasRemoved = $this->testModel->removeSocialLink('twitter');
        $this->assertTrue($wasRemoved);
        $this->assertFalse($this->testModel->hasSocialLink('twitter'));
    }

    /** @test */
    public function it_can_remove_social_link_by_id()
    {
        $link = $this->testModel->addSocialLink('facebook', 'https://facebook.com/user');
        $this->assertTrue($this->testModel->hasSocialLink('facebook'));

        $wasRemoved = $this->testModel->removeSocialLinkById($link->id);
        $this->assertTrue($wasRemoved);
        $this->assertFalse($this->testModel->hasSocialLink('facebook'));
    }


    /** @test */
    public function it_can_get_a_specific_social_link()
    {
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/user1');
        $link = $this->testModel->getSocialLink('twitter');

        $this->assertInstanceOf(SocialLink::class, $link);
        $this->assertEquals('twitter', $link->platform_key);
    }

    /** @test */
    public function get_social_link_respects_public_only_flag()
    {
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/public', ['is_public' => true]);
        $this->testModel->addSocialLink('facebook', 'https://facebook.com/private', ['is_public' => false]);

        $this->assertNotNull($this->testModel->getSocialLink('twitter', true));
        $this->assertNull($this->testModel->getSocialLink('facebook', true));
        $this->assertNotNull($this->testModel->getSocialLink('facebook', false));
    }


    /** @test */
    public function it_can_get_all_social_links_ordered()
    {
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/user', ['sort_order' => 1]);
        $this->testModel->addSocialLink('facebook', 'https://facebook.com/user', ['sort_order' => 0]);

        $links = $this->testModel->getSocialLinks(false); // Get all, public and private
        $this->assertCount(2, $links);
        $this->assertEquals('facebook', $links->first()->platform_key); // Check order
        $this->assertEquals('twitter', $links->last()->platform_key);
    }

    /** @test */
    public function get_social_links_respects_public_only_flag()
    {
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/public', ['is_public' => true]);
        $this->testModel->addSocialLink('facebook', 'https://facebook.com/private', ['is_public' => false]);
        $this->testModel->addSocialLink('linkedin', 'https://linkedin.com/public', ['is_public' => true]);

        $publicLinks = $this->testModel->getSocialLinks(true);
        $this->assertCount(2, $publicLinks);
        $this->assertTrue($publicLinks->pluck('platform_key')->contains('twitter'));
        $this->assertTrue($publicLinks->pluck('platform_key')->contains('linkedin'));
        $this->assertFalse($publicLinks->pluck('platform_key')->contains('facebook'));
    }

    /** @test */
    public function it_can_sync_social_links()
    {
        // Initial state
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/old_twitter');
        $this->testModel->addSocialLink('facebook', 'https://facebook.com/old_facebook');

        $newLinksData = [
            ['platform_key' => 'twitter', 'url' => 'https://twitter.com/new_twitter', 'label' => 'New Twitter'],
            ['platform_key' => 'linkedin', 'url' => 'https://linkedin.com/in/new_linkedin', 'is_public' => false, 'sort_order' => 1],
            // Facebook is omitted, so it should be removed
        ];

        $this->testModel->syncSocialLinks($newLinksData);
        $syncedLinks = $this->testModel->getSocialLinks(false); // Get all to check details

        $this->assertCount(2, $syncedLinks);

        $twitterLink = $syncedLinks->firstWhere('platform_key', 'twitter');
        $this->assertNotNull($twitterLink);
        $this->assertEquals('https://twitter.com/new_twitter', $twitterLink->url);
        $this->assertEquals('New Twitter', $twitterLink->label);

        $linkedinLink = $syncedLinks->firstWhere('platform_key', 'linkedin');
        $this->assertNotNull($linkedinLink);
        $this->assertEquals('https://linkedin.com/in/new_linkedin', $linkedinLink->url);
        $this->assertFalse($linkedinLink->is_public);
        $this->assertEquals(1, $linkedinLink->sort_order);

        $this->assertNull($syncedLinks->firstWhere('platform_key', 'facebook')); // Should be removed
    }

    /** @test */
    public function sync_social_links_validates_input_data()
    {
        $this->expectException(ValidationException::class);
        $invalidLinksData = [
            ['platform_key' => 'twitter', 'url' => 'not-a-valid-url-for-twitter-if-regex-is-strict'],
        ];
        $this->testModel->syncSocialLinks($invalidLinksData);
    }

    /** @test */
    public function sync_social_links_handles_empty_array_to_remove_all_links()
    {
        $this->testModel->addSocialLink('twitter', 'https://twitter.com/user');
        $this->testModel->syncSocialLinks([]);
        $this->assertCount(0, $this->testModel->getSocialLinks(false));
    }
}
