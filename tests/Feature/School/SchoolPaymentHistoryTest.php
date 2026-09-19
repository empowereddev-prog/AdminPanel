<?php

namespace Tests\Feature\School;

use App\Models\PermissionUser;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use App\Services\School\SchoolContractService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SchoolPaymentHistoryTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::factory()->create([
            'user_role_id' => 1,
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        PermissionUser::create([
            'user_id' => $admin->id,
            'menu_id' => 23,
            'is_view' => 'yes',
            'is_modify' => 'yes',
        ]);

        return $admin;
    }

    private function historyRows()
    {
        $response = $this->actingAs($this->admin(), 'admin')
            ->get(route('payment.history_index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();

        return collect($response->json('data') ?? []);
    }

    public function test_a_school_parent_grant_is_not_listed_on_payment_history(): void
    {
        $school = School::factory()->create(['name' => 'Grant Hide School ' . uniqid(), 'price' => 88.00]);
        (new SchoolContractService())->recordOnCreate($school);

        $parent = User::factory()->parent()->inSchool($school)->create([
            'name' => 'Hidden School Parent ' . uniqid(),
        ]);
        (new SchoolContractService())->grantParentEntitlement($school, $parent);

        $names = $this->historyRows()->pluck('user_name')->all();

        $this->assertNotContains($parent->name, $names);
        $this->assertContains($school->name, $names);
    }

    public function test_a_consumer_subscription_is_still_listed(): void
    {
        $parent = User::factory()->parent()->create([
            'name' => 'Paying Consumer ' . uniqid(),
            'school_id' => null,
        ]);

        Subscription::create([
            'user_id' => $parent->id,
            'subscription_type_id' => 'com.empowered.monthly',
            'user_type' => 'parent',
            'subscription_type' => 'monthly',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'currency' => 'SGD',
            'status' => 'successful',
            'price' => '13.49',
        ]);

        $names = $this->historyRows()->pluck('user_name')->all();

        $this->assertContains($parent->name, $names);
    }

    public function test_the_school_contract_shows_the_form_price(): void
    {
        $school = School::factory()->create([
            'name' => 'Priced School ' . uniqid(),
            'price' => 199.95,
            'subscription_type' => 'yearly',
        ]);
        (new SchoolContractService())->recordOnCreate($school);

        $row = $this->historyRows()->firstWhere('user_name', $school->name);

        $this->assertNotNull($row);
        $this->assertEquals('199.95', number_format((float) $row['price'], 2, '.', ''));
    }

    public function test_a_school_without_a_price_is_still_listed_at_zero(): void
    {
        $school = School::factory()->create([
            'name' => 'Unpriced School ' . uniqid(),
            'price' => null,
        ]);
        (new SchoolContractService())->recordOnCreate($school);

        $row = $this->historyRows()->firstWhere('user_name', $school->name);

        $this->assertNotNull($row);
        $this->assertEquals('0.00', number_format((float) $row['price'], 2, '.', ''));
    }
}
