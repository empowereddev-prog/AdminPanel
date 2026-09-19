<?php

namespace Tests\Feature\School;

use App\Models\PermissionUser;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymentHistoryQuery;
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

    /**
     * The reported bug: the onboarding loops that were later commented out in
     * SchoolController wrote the real retail price onto every imported parent,
     * so a price > 0 test could never hide them.
     */
    public function test_a_legacy_school_grant_with_a_real_price_is_not_listed(): void
    {
        $school = School::factory()->create(['name' => 'Legacy School ' . uniqid(), 'price' => 149.00]);
        (new SchoolContractService())->recordOnCreate($school);

        $parent = User::factory()->parent()->inSchool($school)->create([
            'name' => 'Legacy Imported Parent ' . uniqid(),
        ]);

        // Written the way the old code wrote it: retail price, no receipt.
        Subscription::create([
            'user_id' => $parent->id,
            'subscription_type_id' => 'com.empowered.monthly',
            'user_type' => 'parent',
            'subscription_type' => 'monthly',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'currency' => 'SGD',
            'status' => 'Successful',
            'price' => '13.49',
        ]);

        $names = $this->historyRows()->pluck('user_name')->all();

        $this->assertNotContains($parent->name, $names);
        $this->assertContains($school->name, $names);
    }

    public function test_a_hidden_row_cannot_be_opened_by_its_key(): void
    {
        $school = School::factory()->create(['name' => 'Direct Link School ' . uniqid()]);
        $parent = User::factory()->parent()->inSchool($school)->create();

        $subscription = Subscription::create([
            'user_id' => $parent->id,
            'subscription_type_id' => 'com.empowered.monthly',
            'user_type' => 'parent',
            'subscription_type' => 'monthly',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'currency' => 'SGD',
            'status' => 'Successful',
            'price' => '13.49',
        ]);

        $key = base64_encode('iap-' . $subscription->id);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('payment.history_view', ['id' => $key]))
            ->assertRedirect()
            ->assertSessionHas('error');
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

    public function test_the_dashboard_tile_matches_the_payment_history_list(): void
    {
        $school = School::factory()->create(['name' => 'Tile School ' . uniqid(), 'price' => 60.00]);
        (new SchoolContractService())->recordOnCreate($school);

        // A school parent: counted by the old bare count, absent from the list.
        (new SchoolContractService())->grantParentEntitlement(
            $school,
            User::factory()->parent()->inSchool($school)->create()
        );

        // A direct consumer: on both.
        Subscription::create([
            'user_id' => User::factory()->parent()->create(['school_id' => null])->id,
            'subscription_type_id' => 'com.empowered.monthly',
            'user_type' => 'parent',
            'subscription_type' => 'monthly',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'currency' => 'SGD',
            'status' => 'successful',
            'price' => '13.49',
        ]);

        $listed = $this->historyRows()
            ->filter(fn ($row) => str_contains(strtolower($row['status'] ?? ''), 'successful'))
            ->count();

        $this->assertSame($listed, (new PaymentHistoryQuery())->successfulCount());
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
