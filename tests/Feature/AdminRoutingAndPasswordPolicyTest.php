<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Admin routing (the /reddy area must never bounce to the storefront)
 * and the password policy (a special character is required everywhere
 * a password is set or changed).
 */
class AdminRoutingAndPasswordPolicyTest extends TestCase
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
            'password' => 'AdminPass123!',
            'role' => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeStaff(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Staff Member',
            'email' => 'staff-'.uniqid().'@example.com',
            'password' => 'StaffPass123!',
            'role' => 'staff',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user-'.uniqid().'@example.com',
            'password' => 'OldPass123!',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $overrides));
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    protected function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Reg User',
            'email' => 'reg-'.uniqid().'@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country_code' => 'IN',
            // Honeypot fields as a real browser would render them.
            'website_url' => '',
            'form_loaded_at' => (string) (time() - 10),
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Admin routing
    // ------------------------------------------------------------------

    public function test_guest_visiting_admin_login_sees_admin_login_form(): void
    {
        $this->get('/reddy/login')
            ->assertOk()
            ->assertViewIs('admin.auth.login');
    }

    public function test_authenticated_admin_visiting_login_is_sent_to_dashboard_not_storefront(): void
    {
        $admin = $this->makeStaff();

        $this->actingAs($admin, 'admin')
            ->get('/reddy/login')
            ->assertRedirect('/reddy/dashboard');
    }

    public function test_admin_login_redirects_to_dashboard(): void
    {
        $admin = $this->makeStaff();

        $this->post('/reddy/login', [
            'email' => $admin->email,
            'password' => 'StaffPass123!',
        ])
            ->assertRedirect('/reddy/dashboard');

        $this->assertAuthenticated('admin');
    }

    public function test_customer_session_on_admin_dashboard_is_sent_to_admin_login(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'web')->get('/reddy/dashboard');

        $response->assertRedirect('/reddy/login');
        // Must never bounce to the storefront.
        $this->assertNotSame('/', $response->headers->get('Location'));
    }

    public function test_admin_dashboard_loads_for_logged_in_admin(): void
    {
        $admin = $this->makeStaff();

        $this->actingAs($admin, 'admin')
            ->get('/reddy/dashboard')
            ->assertOk();
    }

    public function test_admin_path_returns_404(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
    }

    public function test_authenticated_customer_visiting_login_still_goes_to_storefront_home(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'web')
            ->get('/login')
            ->assertRedirect('/');
    }

    // ------------------------------------------------------------------
    // Password policy: registration
    // ------------------------------------------------------------------

    public function test_registration_rejects_password_without_symbol(): void
    {
        $payload = $this->registerPayload([
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->post('/register', $payload)
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_registration_accepts_password_with_symbol(): void
    {
        $payload = $this->registerPayload();

        $this->post('/register', $payload)->assertRedirect('/verify-email');
        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    // ------------------------------------------------------------------
    // Password policy: password reset
    // ------------------------------------------------------------------

    public function test_password_reset_rejects_password_without_symbol(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors('password');

        $this->assertFalse(Hash::check('Password123', $user->fresh()->password));
    }

    public function test_password_reset_accepts_password_with_symbol(): void
    {
        $user = $this->makeUser();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Password123!', $user->fresh()->password));
    }

    // ------------------------------------------------------------------
    // Password policy: customer account password change
    // ------------------------------------------------------------------

    public function test_account_password_change_rejects_password_without_symbol(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'web')
            ->put('/account/password', [
                'current_password' => 'OldPass123!',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldPass123!', $user->fresh()->password));
    }

    public function test_account_password_change_accepts_password_with_symbol(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'web')
            ->put('/account/password', [
                'current_password' => 'OldPass123!',
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('NewPass123!', $user->fresh()->password));
    }

    // ------------------------------------------------------------------
    // Password policy: admin password change + staff creation
    // ------------------------------------------------------------------

    public function test_admin_password_change_rejects_password_without_symbol(): void
    {
        $admin = $this->makeStaff();

        $this->actingAs($admin, 'admin')
            ->put('/reddy/password', [
                'current_password' => 'StaffPass123!',
                'password' => 'NewAdmin123',
                'password_confirmation' => 'NewAdmin123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('StaffPass123!', $admin->fresh()->password));
    }

    public function test_admin_password_change_accepts_password_with_symbol(): void
    {
        $admin = $this->makeStaff();

        $this->actingAs($admin, 'admin')
            ->put('/reddy/password', [
                'current_password' => 'StaffPass123!',
                'password' => 'NewAdmin123!',
                'password_confirmation' => 'NewAdmin123!',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('NewAdmin123!', $admin->fresh()->password));
    }

    public function test_staff_creation_rejects_password_without_symbol(): void
    {
        $super = $this->makeSuperAdmin();
        $email = 'newstaff-'.uniqid().'@example.com';

        $this->actingAs($super, 'admin')
            ->post('/reddy/admins', [
                'name' => 'New Staff',
                'email' => $email,
                'password' => 'verylongpassword1',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('admins', ['email' => $email]);
    }

    public function test_staff_creation_accepts_password_with_symbol(): void
    {
        $super = $this->makeSuperAdmin();
        $email = 'newstaff-'.uniqid().'@example.com';

        $this->actingAs($super, 'admin')
            ->post('/reddy/admins', [
                'name' => 'New Staff',
                'email' => $email,
                'password' => 'verylongpassword1!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admins', ['email' => $email]);
    }
}
