<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\Brand;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
use Illuminate\Http\UploadedFile; // For media tests
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $data = [
            'name' => 'Awesome Brand',
            'description' => 'A description for an awesome brand.',
            // 'logo_path' and 'cover_photo_path' are removed
            'website_url' => 'http://awesomebrand.com',
            'social_links' => ['facebook' => 'fb.com/awesome'],
            'story' => 'The story of our brand...',
            'is_featured' => true,
            'meta_title' => 'Awesome Brand Title',
            'meta_description' => 'Awesome Brand Meta Description',
            'meta_keywords' => 'awesome, brand, quality',
            'status' => 'active',
        ];
        $brand = Brand::create($data);

        $this->assertInstanceOf(Brand::class, $brand);
        $this->assertEquals('Awesome Brand', $brand->name);
        $this->assertEquals(Str::slug('Awesome Brand'), $brand->slug); // Auto-slugged
        $this->assertTrue($brand->is_featured);
        $this->assertEquals(['facebook' => 'fb.com/awesome'], $brand->social_links);
        $this->assertEquals('active', $brand->status);
    }

    /** @test */
    public function slug_is_generated_automatically_if_not_provided()
    {
        $brand = Brand::create(['name' => 'Another Brand']);
        $this->assertEquals('another-brand', $brand->slug);
    }

    /** @test */
    public function slug_is_made_unique_if_base_slug_exists()
    {
        Brand::create(['name' => 'Unique Name']);
        $brand2 = Brand::create(['name' => 'Unique Name']);
        $this->assertEquals('unique-name-1', $brand2->slug);
    }

    /** @test */
    public function it_casts_social_links_to_array_and_is_featured_to_boolean()
    {
        $brand = Brand::create([
            'name' => 'Casting Brand',
            'social_links' => ['twitter' => 'x.com/cast'],
            'is_featured' => 1,
        ]);
        $this->assertIsArray($brand->social_links);
        $this->assertIsBool($brand->is_featured);
        $this->assertTrue($brand->is_featured);
    }

    /** @test */
    public function it_can_have_a_logo_and_cover_photo_via_medialibrary()
    {
        $brand = Brand::create(['name' => 'Media Brand']);

        // Simulate file upload for logo
        $fakeLogo = UploadedFile::fake()->image('logo.png');
        $brand->addMedia($fakeLogo)->toMediaCollection(config('ijicommerce.media_library_collections.brand_logo', 'brand_logo'));

        // Simulate file upload for cover photo
        $fakeCover = UploadedFile::fake()->image('cover.jpg');
        $brand->addMedia($fakeCover)->toMediaCollection(config('ijicommerce.media_library_collections.brand_cover', 'brand_covers'));

        $this->assertTrue($brand->hasMedia(config('ijicommerce.media_library_collections.brand_logo', 'brand_logo')));
        $this->assertInstanceOf(Media::class, $brand->getFirstMedia(config('ijicommerce.media_library_collections.brand_logo', 'brand_logo')));

        $this->assertTrue($brand->hasMedia(config('ijicommerce.media_library_collections.brand_cover', 'brand_covers')));
        $this->assertInstanceOf(Media::class, $brand->getFirstMedia(config('ijicommerce.media_library_collections.brand_cover', 'brand_covers')));

        // Check if conversions are present (if any defined and run synchronously for tests)
        // $this->assertFileExists($brand->getFirstMedia(config('ijicommerce.media_library_collections.brand_logo', 'brand_logo'))->getPath('thumb'));
    }
}
