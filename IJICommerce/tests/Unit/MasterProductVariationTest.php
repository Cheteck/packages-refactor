<?php

namespace IJIDeals\IJICommerce\Tests\Unit;

use IJIDeals\IJICommerce\Models\MasterProduct;
use IJIDeals\IJICommerce\Models\MasterProductVariation;
use IJIDeals\IJICommerce\Models\ProductAttributeValue;
use IJIDeals\IJICommerce\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class MasterProductVariationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_fillable_attributes()
    {
        $masterProduct = MasterProduct::factory()->create();
        $data = [
            'master_product_id' => $masterProduct->id,
            'sku' => 'MPV-001-RED-SM',
            'price_adjustment' => -5.00,
            'stock_override' => 50,
        ];
        $variation = MasterProductVariation::create($data);

        $this->assertInstanceOf(MasterProductVariation::class, $variation);
        $this->assertEquals($masterProduct->id, $variation->master_product_id);
        $this->assertEquals('MPV-001-RED-SM', $variation->sku);
        $this->assertEquals(-5.00, $variation->price_adjustment);
    }

    /** @test */
    public function it_belongs_to_a_master_product()
    {
        $masterProduct = MasterProduct::factory()->create();
        $variation = MasterProductVariation::factory()->create(['master_product_id' => $masterProduct->id]);
        $this->assertInstanceOf(MasterProduct::class, $variation->masterProduct);
    }

    /** @test */
    public function it_can_have_attribute_options()
    {
        $variation = MasterProductVariation::factory()->create();
        $option1 = ProductAttributeValue::factory()->create(); // Assuming factories exist
        $option2 = ProductAttributeValue::factory()->create();

        $variation->attributeOptions()->attach([$option1->id, $option2->id]);

        $this->assertCount(2, $variation->attributeOptions);
        $this->assertTrue($variation->attributeOptions->contains($option1));
    }

    /** @test */
    public function it_can_have_a_variant_image_via_medialibrary()
    {
        $variation = MasterProductVariation::factory()->create();
        $fakeImage = UploadedFile::fake()->image('variant.jpg');

        $variation->addMedia($fakeImage)->toMediaCollection(config('ijicommerce.media_library_collections.master_product_variant_image', 'master_product_variant_images'));

        $this->assertTrue($variation->hasMedia(config('ijicommerce.media_library_collections.master_product_variant_image', 'master_product_variant_images')));
        $this->assertInstanceOf(Media::class, $variation->getFirstMedia(config('ijicommerce.media_library_collections.master_product_variant_image', 'master_product_variant_images')));
    }
}

// Factories needed: MasterProductFactory, MasterProductVariationFactory, ProductAttributeValueFactory
// namespace Database\Factories;
// use IJIDeals\IJICommerce\Models\MasterProductVariation;
// class MasterProductVariationFactory extends Factory { protected $model = MasterProductVariation::class; public function definition() { return ['master_product_id' => MasterProduct::factory(), 'sku' => $this->faker->unique()->ean8]; } }
// use IJIDeals\IJICommerce\Models\ProductAttributeValue;
// use IJIDeals\IJICommerce\Models\ProductAttribute;
// class ProductAttributeValueFactory extends Factory { protected $model = ProductAttributeValue::class; public function definition() { return ['product_attribute_id' => ProductAttribute::factory(), 'value' => $this->faker->word]; } }
// use IJIDeals\IJICommerce\Models\ProductAttribute;
// class ProductAttributeFactory extends Factory { protected $model = ProductAttribute::class; public function definition() { return ['name' => $this->faker->word, 'type' => 'select']; } }
