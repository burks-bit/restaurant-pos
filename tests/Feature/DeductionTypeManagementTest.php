<?php

namespace Tests\Feature;

use App\Models\DeductionType;
use App\Models\Employee;
use App\Models\EmployeeDeductionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeductionTypeManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_index_lists_deduction_types(): void
    {
        DeductionType::create(['name' => 'Cash Advance', 'status' => true]);

        $this->actingAs($this->admin())
            ->get('/admin/deduction-types')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DeductionTypes/Index')
                ->has('deduction_types'));
    }

    public function test_deduction_type_can_be_created(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/deduction-types', [
                'name' => 'Loan',
                'description' => 'Salary loan',
                'status' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deduction_types', [
            'name' => 'Loan',
            'description' => 'Salary loan',
        ]);
    }

    public function test_deduction_type_requires_a_name(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/deduction-types', ['name' => '', 'status' => 1])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('deduction_types', ['name' => '']);
    }

    public function test_deduction_type_can_be_updated(): void
    {
        $type = DeductionType::create(['name' => 'Old', 'status' => true]);

        $this->actingAs($this->admin())
            ->put("/admin/deduction-types/{$type->id}", [
                'name' => 'New',
                'description' => null,
                'status' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deduction_types', [
            'id' => $type->id,
            'name' => 'New',
            'status' => 0,
        ]);
    }

    public function test_deduction_type_can_be_deleted(): void
    {
        $type = DeductionType::create(['name' => 'Temp', 'status' => true]);

        $this->actingAs($this->admin())
            ->delete("/admin/deduction-types/{$type->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('deduction_types', ['id' => $type->id]);
    }

    public function test_deduction_type_in_use_cannot_be_deleted(): void
    {
        $type = DeductionType::create(['name' => 'In Use', 'status' => true]);

        $employee = Employee::create([
            'employee_code' => 'EMP-TEST-1',
            'first_name' => 'Test',
            'last_name' => 'Employee',
        ]);

        $template = EmployeeDeductionTemplate::create(['employee_id' => $employee->id]);

        $template->details()->create([
            'deduction_type_id' => $type->id,
            'cutoff' => '15th',
            'default_value' => 0,
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/deduction-types/{$type->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('deduction_types', ['id' => $type->id]);
    }
}
