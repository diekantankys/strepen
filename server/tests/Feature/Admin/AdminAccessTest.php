<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    public function test_admin_routes_require_manager_or_admin_roles()
    {
        $normalUser = User::factory()->create();
        $manager = User::factory()->manager()->create();

        $managerRoutes = [
            'admin.home',
            'admin.users.crud',
            'admin.posts.crud',
            'admin.products.crud',
            'admin.inventories.crud',
            'admin.transactions.crud',
        ];

        foreach ($managerRoutes as $route) {
            $this->actingAs($normalUser)->get(route($route))->assertForbidden();
            $this->actingAs($manager)->get(route($route))->assertOk();
        }
    }

    public function test_admin_only_routes_reject_managers()
    {
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create();

        foreach (['admin.settings', 'admin.api_keys.crud'] as $route) {
            $this->actingAs($manager)->get(route($route))->assertForbidden();
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}
