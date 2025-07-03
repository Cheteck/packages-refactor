<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\MasterProduct;
use IJIDeals\IJICommerce\Models\Brand;
use IJIDeals\IJICommerce\Models\Category;
use IJIDeals\IJICommerce\Models\ProductProposal;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class MasterProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $brand = Brand::factory()->create(); // Assuming factories
        $category = Category::factory()->create();
        $proposal = ProductProposal::factory()->create();

        $data = [
            'name' => 'Super Laptop Pro',
            'description' => 'The latest and greatest laptop.',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'specifications' => ['ram' => '16GB', 'storage' => '1TB SSD'],
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MasterProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $brand = Brand::factory()->create(); // Assuming factories
        $category = Category::factory()->create();
        $proposal = ProductProposal::factory()->create();

        $data = [
            'name' => 'Super Laptop Pro',
            'description' => 'The latest and greatest laptop.',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'specifications' => ['ram' => '16GB', 'storage' => '1TB SSD'],
            'status' => 'active',
            'created_by_proposal_id' => $proposal->id,
            // 'images_payload' removed
        ];
        $masterProduct = MasterProduct::create($data);

        $this->assertInstanceOf(MasterProduct::class, $masterProduct);
        $this->assertEquals('Super Laptop Pro', $masterProduct->name);
        $this->assertEquals(Str::slug('Super Laptop Pro'), $masterProduct->slug);
        $this->assertEquals(['ram' => '16GB', 'storage' => '1TB SSD'], $masterProduct->specifications);
        $this->assertEquals($brand->id, $masterProduct->brand_id);
        $this->assertEquals($category->id, $masterProduct->category_id);
        $this->assertEquals($proposal->id, $masterProduct->created_by_proposal_id);
    }

    /** @test */
    public function it_belongs_to_a_brand()
    {
        $brand = Brand::factory()->create();
        $masterProduct = MasterProduct::factory()->create(['brand_id' => $brand->id]);
        $this->assertInstanceOf(Brand::class, $masterProduct->brand);
    }

    /** @test */
    public function it_belongs_to_a_category()
    {
        $category = Category::factory()->create();
        $masterProduct = MasterProduct::factory()->create(['category_id' => $category->id]);
        $this->assertInstanceOf(Category::class, $masterProduct->category);
    }

    /** @test */
    public function it_can_belong_to_a_product_proposal()
    {
        $proposal = ProductProposal::factory()->create();
        $masterProduct = MasterProduct::factory()->create(['created_by_proposal_id' => $proposal->id]);
        $this->assertInstanceOf(ProductProposal::class, $masterProduct->productProposal);
    }

    /** @test */
    public function it_casts_specifications_to_array() // images_payload removed
    {
        $masterProduct = MasterProduct::factory()->create([
            'specifications' => ['feature' => 'value'],
        ]);
        $this->assertIsArray($masterProduct->specifications);
    }

    /** @test */
    public function it_can_have_base_images_via_medialibrary()
    {
        $masterProduct = MasterProduct::factory()->create(['name' => 'Media MP']);
        $fakeImage1 = UploadedFile::fake()->image('mp1.jpg');
        $fakeImage2 = UploadedFile::fake()->image('mp2.png');

        $masterProduct->addMedia($fakeImage1)->toMediaCollection(config('ijicommerce.media_library_collections.master_product_base_images', 'master_product_base_images'));
        $masterProduct->addMedia($fakeImage2)->toMediaCollection(config('ijicommerce.media_library_collections.master_product_base_images', 'master_product_base_images'));

        $this->assertTrue($masterProduct->hasMedia(config('ijicommerce.media_library_collections.master_product_base_images', 'master_product_base_images')));
        $this->assertCount(2, $masterProduct->getMedia(config('ijicommerce.media_library_collections.master_product_base_images', 'master_product_base_images')));
    }
}

// Required Factories (simplified stubs, place in database/factories):
// namespace Database\Factories;
// use Illuminate\Database\Eloquent\Factories\Factory;
// use IJIDeals\IJICommerce\Models\Brand;
// class BrandFactory extends Factory { protected $model = Brand::class; public function definition() { return ['name' => $this->faker->company]; } }

// use IJIDeals\IJICommerce\Models\Category;
// class CategoryFactory extends Factory { protected $model = Category::class; public function definition() { return ['name' => $this->faker->word]; } }

// use IJIDeals\IJICommerce\Models\ProductProposal;
// use IJIDeals\IJICommerce\Models\Shop;
// class ProductProposalFactory extends Factory { protected $model = ProductProposal::class; public function definition() { return ['shop_id' => Shop::factory(), 'name' => $this->faker->sentence(3)]; } }

// use IJIDeals\IJICommerce\Models\MasterProduct;
// class MasterProductFactory extends Factory { protected $model = MasterProduct::class; public function definition() { return ['name' => $this->faker->catchPhrase, 'status' => 'active']; } }

// use IJIDeals\IJICommerce\Models\Shop;
// class ShopFactory extends Factory { protected $model = Shop::class; public function definition() { return ['name' => $this->faker->company . ' Shop']; } }
