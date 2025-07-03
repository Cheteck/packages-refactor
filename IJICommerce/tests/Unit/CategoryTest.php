<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\Category;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $data = [
            'name' => 'Electronics',
            'description' => 'All kinds of electronic gadgets.',
            // 'image_path' is removed
        ];
        $category = Category::create($data);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Electronics', $category->name);
        $this->assertEquals(Str::slug('Electronics'), $category->slug);
    }

    /** @test */
    public function it_can_have_a_parent_category()
    {
        $parent = Category::create(['name' => 'Parent Category']);
        $child = Category::create(['name' => 'Child Category', 'parent_id' => $parent->id]);

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains($child));
    }

    /** @test */
    public function slug_is_generated_automatically()
    {
        $category = Category::create(['name' => 'Home Appliances']);
        $this->assertEquals('home-appliances', $category->slug);
    }

    /** @test */
    public function unique_slug_is_generated_for_same_name()
    {
        Category::create(['name' => 'Books']);
        $category2 = Category::create(['name' => 'Books']);
        $this->assertEquals('books-1', $category2->slug);
    }

    /** @test */
    public function it_can_have_an_image_via_medialibrary()
    {
        $category = Category::create(['name' => 'Media Category']);
        $fakeImage = UploadedFile::fake()->image('category.jpg');

        $category->addMedia($fakeImage)->toMediaCollection(config('ijicommerce.media_library_collections.category_image', 'category_images'));

        $this->assertTrue($category->hasMedia(config('ijicommerce.media_library_collections.category_image', 'category_images')));
        $this->assertInstanceOf(Media::class, $category->getFirstMedia(config('ijicommerce.media_library_collections.category_image', 'category_images')));
    }

    // If using a nested set package, tests for depth, ancestors, descendants would go here.
    // For simple parent_id, parent() and children() relationships are tested above.
}
