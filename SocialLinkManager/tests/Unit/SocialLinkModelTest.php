<?php

namespace IJIDeals\SocialLinkManager\Tests\Unit;

use IJIDeals\SocialLinkManager\Models\SocialLink;
use IJIDeals\SocialLinkManager\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use IJIDeals\SocialLinkManager\Traits\HasSocialLinks; // For test model

class SocialLinkModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $linkData = [
            'platform_key' => 'twitter',
            'url' => 'https://twitter.com/user',
            'label' => 'My Twitter',
            'sort_order' => 1,
            'is_public' => false,
        ];

        // Create a dummy model that uses HasSocialLinks to associate with
        $linkable = TestLinkableModel::create(['name' => 'Testable']);
        $socialLink = $linkable->socialLinks()->create($linkData);

        $this->assertInstanceOf(SocialLink::class, $socialLink);
        $this->assertEquals('twitter', $socialLink->platform_key);
        $this->assertEquals('https://twitter.com/user', $socialLink->url);
        $this->assertEquals('My Twitter', $socialLink->label);
        $this->assertEquals(1, $socialLink->sort_order);
        $this->assertFalse($socialLink->is_public);
    }

    /** @test */
    public function is_public_casts_to_boolean_and_sort_order_to_integer()
    {
        $linkable = TestLinkableModel::create(['name' => 'Testable']);
        $socialLink = $linkable->socialLinks()->create([
            'platform_key' => 'facebook',
            'url' => 'https://facebook.com/profile',
            'is_public' => 1, // Input as int
            'sort_order' => '2', // Input as string
        ]);

        $this->assertIsBool($socialLink->is_public);
        $this->assertTrue($socialLink->is_public);
        $this->assertIsInt($socialLink->sort_order);
        $this->assertEquals(2, $socialLink->sort_order);
    }

    /** @test */
    public function it_belongs_to_a_social_linkable_model()
    {
        $linkable = TestLinkableModel::create(['name' => 'Another Testable']);
        $socialLink = $linkable->socialLinks()->create([
            'platform_key' => 'linkedin',
            'url' => 'https://linkedin.com/in/user',
        ]);

        $this->assertInstanceOf(TestLinkableModel::class, $socialLink->socialLinkable);
        $this->assertEquals($linkable->id, $socialLink->socialLinkable->id);
    }

    /** @test */
    public function public_scope_returns_only_public_links()
    {
        $linkable = TestLinkableModel::create(['name' => 'Scope Test']);
        $linkable->socialLinks()->create(['platform_key' => 'public_one', 'url' => 'https://public1.com', 'is_public' => true]);
        $linkable->socialLinks()->create(['platform_key' => 'private_one', 'url' => 'https://private1.com', 'is_public' => false]);
        $linkable->socialLinks()->create(['platform_key' => 'public_two', 'url' => 'https://public2.com', 'is_public' => true]);

        $publicLinks = $linkable->socialLinks()->public()->get();
        $this->assertCount(2, $publicLinks);
        $this->assertTrue($publicLinks->every('is_public', true));
    }

    /** @test */
    public function it_retrieves_platform_details_from_config()
    {
        // Ensure config is loaded for the test
        config(['socialinkmanager.platforms.testplatform' => [
            'name' => 'Test Platform Display',
            'icon_class' => 'fas fa-test',
        ]]);

        $linkable = TestLinkableModel::create(['name' => 'Platform Config Test']);
        $socialLink = $linkable->socialLinks()->create([
            'platform_key' => 'testplatform',
            'url' => 'https://test.com',
        ]);

        $this->assertEquals('Test Platform Display', $socialLink->platform_display_name);
        $this->assertEquals('fas fa-test', $socialLink->platform_icon_class);
    }

    /** @test */
    public function platform_display_name_falls_back_to_capitalized_key()
    {
        // Ensure 'unknown_platform' is not in config
        config(['socialinkmanager.platforms' => []]);

        $linkable = TestLinkableModel::create(['name' => 'Fallback Test']);
        $socialLink = $linkable->socialLinks()->create([
            'platform_key' => 'unknown_platform',
            'url' => 'https://unknown.com',
        ]);
        $this->assertEquals('Unknown platform', $socialLink->platform_display_name); // ucfirst(str_replace('_', ' ', ...))
    }
}

// Dummy model for testing the polymorphic relationship
class TestLinkableModel extends Model
{
    use HasSocialLinks; // The trait we are building
    protected $table = 'test_linkable_models';
    protected $guarded = [];
    public static function booted()
    {
        // Create a simple table for this test model if it doesn't exist
        if (!\Illuminate\Support\Facades\Schema::hasTable('test_linkable_models')) {
            \Illuminate\Support\Facades\Schema::create('test_linkable_models', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }
}
