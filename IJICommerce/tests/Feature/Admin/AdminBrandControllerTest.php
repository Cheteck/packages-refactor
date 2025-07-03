<?php

namespace IJIDeals\IJICommerce\Tests\Feature\Admin;

use IJIDeals\IJICommerce\Models\Brand;
use IJIDeals\IJICommerce\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AdminBrandControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformAdminUser;
    protected User $regularUser;
    protected Role $platformAdminRole;

    public function setUp(): void
    {
        parent::setUp();
        $this->platformAdminRole = Role::firstOrCreate(['name' => 'Platform Admin', 'guard_name' => 'web']);

        $this->platformAdminUser = $this->createUser(['email' => 'admin.brands@example.com']);
        $this->platformAdminUser->assignRole($this->platformAdminRole->name); // Assign global role

        $this->regularUser = $this->createUser(['email' => 'user.brands@example.com']);
    }

    /** @test */
    public function platform_admin_can_list_brands()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        Brand::create(['name' => 'Brand A']);
        Brand::create(['name' => 'Brand B']);

        $response = $this->getJson(route('ijicommerce.api.admin.brands.index'));
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Assuming pagination
            ->assertJsonFragment(['name' => 'Brand A'])
            ->assertJsonFragment(['name' => 'Brand B']);
    }

    /** @test */
    public function non_admin_cannot_list_brands()
    {
        $this->actingAs($this->regularUser, 'sanctum');
        $response = $this->getJson(route('ijicommerce.api.admin.brands.index'));
        $response->assertStatus(403); // Based on policy
    }

    /** @test */
    public function platform_admin_can_create_a_brand()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $brandData = [
            'name' => 'Newest Brand',
            'description' => 'A cool new brand.',
            'status' => 'active',
            // Add other rich fields
            'website_url' => 'http://newest.com',
            'is_featured' => true,
        ];
        $response = $this->postJson(route('ijicommerce.api.admin.brands.store'), $brandData);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Newest Brand', 'is_featured' => true]);
        $this->assertDatabaseHas('brands', ['name' => 'Newest Brand', 'website_url' => 'http://newest.com']);
    }

    /** @test */
    public function platform_admin_can_update_a_brand()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $brand = Brand::create(['name' => 'Old Brand Name', 'status' => 'inactive']);
        $updateData = ['name' => 'Updated Brand Name', 'status' => 'active', 'is_featured' => true];

        $response = $this->putJson(route('ijicommerce.api.admin.brands.update', $brand->id), $updateData);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Brand Name', 'status' => 'active', 'is_featured' => true]);
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'Updated Brand Name']);
    }

    /** @test */
    public function platform_admin_can_delete_a_brand()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $brand = Brand::create(['name' => 'Brand To Delete']);

        $response = $this->deleteJson(route('ijicommerce.api.admin.brands.destroy', $brand->id));
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Brand deleted successfully.']);
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }
}
