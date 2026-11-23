<?php

namespace Tests\Feature;

use App\Models\EarningType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EarningTypeManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_index_lists_earning_types(): void
    {
        EarningType::create(['name' => 'Overtime Pay', 'status' => true]);

        $this->actingAs($this->admin())
            ->get('/admin/earning-types')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('EarningTypes/Index')
                ->has('earning_types'));
    }

    public function test_earning_type_can_be_created(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/earning-types', [
                'name' => 'Holiday Pay',
                'description' => 'Holiday premium',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('earning_types', [
            'name' => 'Holiday Pay',
            'description' => 'Holiday premium',
        ]);
    }

    public function test_earning_type_requires_a_name(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/earning-types', ['name' => '', 'status' => 1])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('earning_types', ['name' => '']);
    }

    public function test_earning_type_can_be_updated(): void
    {
        $type = EarningType::create(['name' => 'Old', 'status' => true]);

        $this->actingAs($this->admin())
            ->put("/admin/earning-types/{$type->id}", [
                'name' => 'New',
                'description' => null,
                'status' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('earning_types', [
            'id' => $type->id,
            'name' => 'New',
            'status' => 0,
        ]);
    }

    public function test_earning_type_can_be_deleted(): void
    {
        $type = EarningType::create(['name' => 'Temp', 'status' => true]);

        $this->actingAs($this->admin())
            ->delete("/admin/earning-types/{$type->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('earning_types', ['id' => $type->id]);
    }
}
