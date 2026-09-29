<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\UpdateService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Post-launch missing features:
 *  1. Super Admin can change a customer's country (+ pricing region), audited.
 *  2. Password re-entry required before applying a GitHub update.
 *  3. Customer profile editing (name + email only).
 */
class PostLaunchFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function makeSuperAdmin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Super Admin',
            'email' => 'super-'.uniqid().'@example.com',
            'password' => 'correct-admin-pass',
            'role' => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeStaff(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Staff Member',
            'email' => 'staff-'.uniqid().'@example.com',
            'password' => 'correct-admin-pass',
            'role' => 'staff',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // Feature 1: admin edits customer country
    // ------------------------------------------------------------------

    public function test_super_admin_can_change_user_country_and_region_updates(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin, 'admin')
            ->put('/reddy/users/'.$user->id.'/country', ['country_code' => 'US'])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('US', $user->country_code);
        $this->assertSame('United States', $user->country_name);
        $this->assertSame('INTL', $user->pricing_region);
    }

    public function test_changing_country_back_to_india_restores_inr_region(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser(['country_code' => 'US', 'country_name' => 'United States', 'pricing_region' => 'INTL']);

        $this->actingAs($admin, 'admin')
            ->put('/reddy/users/'.$user->id.'/country', ['country_code' => 'IN'])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('IN', $user->country_code);
        $this->assertSame('IN', $user->pricing_region);
    }

    public function test_country_change_is_audited_with_old_and_new_values(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin, 'admin')
            ->put('/reddy/users/'.$user->id.'/country', ['country_code' => 'GB']);

        $log = AuditLog::where('action', 'user.country_changed')
            ->where('target_id', $user->id)
            ->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('IN', $log->old_values['country_code']);
        $this->assertSame('GB', $log->new_values['country_code']);
        $this->assertSame('INTL', $log->new_values['pricing_region']);
        $this->assertSame($admin->id, $log->admin_id);
    }

    public function test_country_change_rejected_for_unknown_country(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin, 'admin')
            ->put('/reddy/users/'.$user->id.'/country', ['country_code' => 'XX'])
            ->assertSessionHasErrors('country_code');

        $this->assertSame('IN', $user->fresh()->country_code);
    }

    public function test_staff_cannot_change_user_country(): void
    {
        $staff = $this->makeStaff();
        $user = $this->makeUser();

        $this->actingAs($staff, 'admin')
            ->put('/reddy/users/'.$user->id.'/country', ['country_code' => 'US'])
            ->assertForbidden();

        $this->assertSame('IN', $user->fresh()->country_code);
    }

    public function test_guest_cannot_change_user_country(): void
    {
        $user = $this->makeUser();

        $this->put('/reddy/users/'.$user->id.'/country', ['country_code' => 'US'])
            ->assertRedirect();

        $this->assertSame('IN', $user->fresh()->country_code);
    }

    public function test_customer_cannot_change_own_country_via_profile(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put('/account/profile', [
                'name' => 'New Name',
                'email' => $user->email,
                'country_code' => 'US',
                'country_name' => 'United States',
                'pricing_region' => 'INTL',
            ])
            ->assertRedirect();

        $user->refresh();
        // Name updates, but country fields are silently ignored.
        $this->assertSame('New Name', $user->name);
        $this->assertSame('IN', $user->country_code);
        $this->assertSame('India', $user->country_name);
        $this->assertSame('IN', $user->pricing_region);
    }

    // ------------------------------------------------------------------
    // Feature 2: password re-entry before applying an update
    // ------------------------------------------------------------------

    public function test_update_apply_rejected_without_password(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->mock(UpdateService::class, fn ($m) => $m->shouldReceive('applyUpdate')->never());

        $this->actingAs($admin, 'admin')
            ->post('/reddy/updates/apply', [
                'sha' => str_repeat('a', 40),
                'confirm_backup' => '1',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_update_apply_rejected_with_wrong_password(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->mock(UpdateService::class, fn ($m) => $m->shouldReceive('applyUpdate')->never());

        $this->actingAs($admin, 'admin')
            ->post('/reddy/updates/apply', [
                'sha' => str_repeat('a', 40),
                'confirm_backup' => '1',
                'password' => 'wrong-pass',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_update_apply_accepted_with_correct_password(): void
    {
        $admin = $this->makeSuperAdmin();
        $sha = str_repeat('b', 40);
        $this->mock(UpdateService::class, function ($mock) use ($sha) {
            $mock->shouldReceive('applyUpdate')->once()->with($sha)->andReturnNull();
        });

        $this->actingAs($admin, 'admin')
            ->post('/reddy/updates/apply', [
                'sha' => $sha,
                'confirm_backup' => '1',
                'password' => 'correct-admin-pass',
            ])
            ->assertRedirect(route('reddy.updates.index'))
            ->assertSessionHas('status');
    }

    // ------------------------------------------------------------------
    // Feature 3: customer profile editing
    // ------------------------------------------------------------------

    public function test_customer_can_update_name_and_email(): void
    {
        $user = $this->makeUser(['name' => 'Old Name', 'email' => 'old-'.uniqid().'@example.com']);

        $this->actingAs($user)
            ->put('/account/profile', ['name' => 'New Name', 'email' => 'new-'.uniqid().'@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertStringStartsWith('new-', $user->email);
    }

    public function test_customer_can_keep_same_email(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put('/account/profile', ['name' => 'Renamed', 'email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_profile_email_must_be_unique(): void
    {
        $takenEmail = 'taken-'.uniqid().'@example.com';
        $this->makeUser(['email' => $takenEmail]);
        $user = $this->makeUser(['email' => 'mine-'.uniqid().'@example.com']);

        $this->actingAs($user)
            ->put('/account/profile', ['name' => 'X', 'email' => $takenEmail])
            ->assertSessionHasErrors('email');

        $this->assertStringStartsWith('mine-', $user->fresh()->email);
    }

    public function test_profile_name_is_required(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put('/account/profile', ['name' => '', 'email' => $user->email])
            ->assertSessionHasErrors('name');
    }

    public function test_profile_update_requires_login(): void
    {
        $this->put('/account/profile', ['name' => 'X', 'email' => 'x@example.com'])
            ->assertRedirect(route('login'));
    }

    public function test_account_page_shows_profile_form(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/account')
            ->assertOk()
            ->assertSee('Save profile', false);
    }
}
