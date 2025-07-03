<?php

namespace IJIDeals\IJICommerce\Tests\Feature\Admin;

use IJIDeals\IJICommerce\Models\Category;
use IJIDeals\IJICommerce\Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AdminCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformAdminUser;
    protected User $regularUser;
    protected Role $platformAdminRole;

    public function setUp(): void
    {
        parent::setUp();
        $this->platformAdminRole = Role::firstOrCreate(['name' => 'Platform Admin', 'guard_name' => 'web']);
        $this->platformAdminUser = $this->createUser(['email' => 'admin.cats@example.com']);
        $this->platformAdminUser->assignRole($this->platformAdminRole->name);
        $this->regularUser = $this->createUser(['email' => 'user.cats@example.com']);
    }

    /** @test */
    public function platform_admin_can_list_categories()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        Category::create(['name' => 'Electronics']);
        $parent = Category::create(['name' => 'Fashion']);
        Category::create(['name' => 'Men', 'parent_id' => $parent->id]);

        $response = $this->getJson(route('ijicommerce.api.admin.categories.index'));
        $response->assertStatus(200)
            // ->assertJsonCount(2, 'data') // Only top-level by default in controller
            ->assertJsonFragment(['name' => 'Electronics'])
            ->assertJsonFragment(['name' => 'Fashion']);
    }

    /** @test */
    public function platform_admin_can_create_a_category()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $parentCategory = Category::create(['name' => 'Books']);
        $categoryData = [
            'name' => 'Fiction',
            'description' => 'Fictional books.',
            'parent_id' => $parentCategory->id,
        ];
        $response = $this->postJson(route('ijicommerce.api.admin.categories.store'), $categoryData);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Fiction', 'parent_id' => $parentCategory->id]);
        $this->assertDatabaseHas('categories', ['name' => 'Fiction', 'parent_id' => $parentCategory->id]);
    }

    /** @test */
    public function platform_admin_can_update_a_category()
    {
        $this->actingAs($this->platformAdminUser, 'sanctum');
        $category = Category::create(['name' => 'Old Category Name']);
        $updateData = ['name' => 'Updated Category Name', 'description' => 'New desc.'];

        $response = $this->putJson(route('ijicommerce.api.admin.categories.update', $category->id), $updateData);
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Category Name']);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Category Name']);
    }

    /** @test */
    public function regular_user_cannot_create_category()
    {
        $this->actingAs($this->regularUser, 'sanctum');
        $categoryData = ['name' => 'User Category Attempt'];
        $response = $this->postJson(route('ijicommerce.api.admin.categories.store'), $categoryData);
        $response->assertStatus(403);
    }
}
