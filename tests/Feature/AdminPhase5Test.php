<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 5: complete admin — dashboard, users, roles, sessions, audit log, exports.
 */
class AdminPhase5Test extends TestCase
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
            'password' => 'password12345',
            'role' => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeStaff(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Staff Member',
            'email' => 'staff-'.uniqid().'@example.com',
            'password' => 'password12345',
            'role' => 'staff',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user-'.uniqid().'@example.com',
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $overrides));
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    protected function makePaidOrder(User $user, string $currency, float $total): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateNumber(),
            'currency' => $currency,
            'subtotal' => $total,
            'grand_total' => $total,
            'status' => Order::STATUS_PAID,
            'payment_method' => 'razorpay',
            'no_refund_accepted' => true,
            'no_refund_accepted_at' => now(),
        ]);

        return $order;
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function test_dashboard_numbers_match_database(): void
    {
        $admin = $this->makeSuperAdmin();
        $u1 = $this->makeUser();
        $u2 = $this->makeUser(['country_code' => 'US', 'country_name' => 'United States', 'pricing_region' => 'INTL']);

        $this->makePaidOrder($u1, 'INR', 149);
        $this->makePaidOrder($u1, 'INR', 149);
        $this->makePaidOrder($u2, 'USD', 4.99);
        Order::create([
            'user_id' => $u1->id,
            'order_number' => Order::generateNumber(),
            'currency' => 'INR',
            'subtotal' => 149,
            'grand_total' => 149,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'razorpay',
            'no_refund_accepted' => true,
            'no_refund_accepted_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/reddy/dashboard');
        $response->assertOk();

        // Revenue kept separate: 298 INR and 4.99 USD, pending order excluded.
        $response->assertSee('₹298.00', false);
        $response->assertSee('$4.99', false);
        // INR and USD never combined into a single figure.
        $response->assertDontSee('₹302.99', false);
    }

    public function test_dashboard_requires_admin(): void
    {
        $this->get('/reddy/dashboard')->assertRedirect('/reddy/login');
    }

    public function test_dashboard_never_mixes_inr_and_usd(): void
    {
        $admin = $this->makeSuperAdmin();
        $u1 = $this->makeUser();
        $u2 = $this->makeUser(['country_code' => 'US', 'country_name' => 'United States', 'pricing_region' => 'INTL']);
        $book = Book::create([
            'title' => 'Currency Test Book',
            'slug' => 'currency-test-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'is_active' => true,
        ]);

        $o1 = $this->makePaidOrder($u1, 'INR', 149);
        $o1->items()->create(['book_id' => $book->id, 'title' => $book->title, 'unit_price' => 149, 'quantity' => 1, 'line_total' => 149, 'currency' => 'INR']);
        $o2 = $this->makePaidOrder($u2, 'USD', 4.99);
        $o2->items()->create(['book_id' => $book->id, 'title' => $book->title, 'unit_price' => 4.99, 'quantity' => 1, 'line_total' => 4.99, 'currency' => 'USD']);

        $response = $this->actingAs($admin, 'admin')->get('/reddy/dashboard');
        $response->assertOk();

        // Each currency shown with its own symbol; never summed together.
        $response->assertSee('₹149.00', false);
        $response->assertSee('$4.99', false);
        $response->assertDontSee('₹153.99', false);
    }

    // ------------------------------------------------------------------
    // Users
    // ------------------------------------------------------------------

    public function test_users_list_and_detail(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin, 'admin')->get('/reddy/users')->assertOk()->assertSee($user->email);
        $this->actingAs($admin, 'admin')->get('/reddy/users/'.$user->id)->assertOk()->assertSee($user->email);
    }

    public function test_blocked_user_cannot_log_in(): void
    {
        $user = $this->makeUser(['is_blocked' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_block_and_unblock_user(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();

        $this->actingAs($admin, 'admin')->post('/reddy/users/'.$user->id.'/block')->assertRedirect();
        $this->assertTrue($user->fresh()->is_blocked);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.blocked', 'target_id' => $user->id]);

        $this->actingAs($admin, 'admin')->post('/reddy/users/'.$user->id.'/unblock')->assertRedirect();
        $this->assertFalse($user->fresh()->is_blocked);
    }

    public function test_staff_cannot_block_users(): void
    {
        $staff = $this->makeStaff();
        $user = $this->makeUser();

        $this->actingAs($staff, 'admin')->post('/reddy/users/'.$user->id.'/block')->assertForbidden();
        $this->assertFalse($user->fresh()->is_blocked);
    }

    public function test_staff_can_view_users(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff, 'admin')->get('/reddy/users')->assertOk();
    }

    // ------------------------------------------------------------------
    // Roles & permissions
    // ------------------------------------------------------------------

    public function test_staff_cannot_open_super_admin_areas(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff, 'admin')->get('/reddy/settings')->assertForbidden();
        $this->actingAs($staff, 'admin')->get('/reddy/audit-log')->assertForbidden();
        $this->actingAs($staff, 'admin')->get('/reddy/admins')->assertForbidden();
    }

    public function test_super_admin_can_create_staff(): void
    {
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin, 'admin')->post('/reddy/admins', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'password' => 'a-very-long-password-123',
        ]);

        $response->assertRedirect();
        $staff = Admin::where('email', 'newstaff@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertSame('staff', $staff->role);
        $this->assertTrue(Hash::check('a-very-long-password-123', $staff->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.created']);

        $this->actingAs($admin, 'admin')->get('/reddy/admins')->assertOk()->assertSee('newstaff@example.com');
    }

    public function test_cannot_delete_self(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')->delete('/reddy/admins/'.$admin->id)->assertRedirect();
        $this->assertNotNull(Admin::find($admin->id));
    }

    public function test_cannot_delete_last_super_admin(): void
    {
        $admin = $this->makeSuperAdmin();
        $other = $this->makeSuperAdmin();

        // Deleting the other super admin is fine (one remains).
        $this->actingAs($admin, 'admin')->delete('/reddy/admins/'.$other->id)->assertRedirect();
        $this->assertNull(Admin::find($other->id));

        // Now only one super admin is left — deleting them is refused
        // (self-delete is also refused, so create a second and try).
        $second = $this->makeSuperAdmin();
        $third = $this->makeSuperAdmin();
        $this->actingAs($second, 'admin')->delete('/reddy/admins/'.$third->id);
        // third is not the last super admin (admin + second remain), so it deletes.
        $this->assertNull(Admin::find($third->id));

        // Only $admin and $second remain; $second cannot delete $admin (last? no—two remain).
        // The real last-super-admin guard: make $second the only one.
        $admin->delete();
        $this->actingAs($second, 'admin')->delete('/reddy/admins/'.$second->id)->assertRedirect();
        $this->assertNotNull(Admin::find($second->id), 'self-delete is always refused');
    }

    public function test_last_super_admin_protection(): void
    {
        // Reduce to exactly one super_admin (rolled back after the test).
        Admin::where('role', 'super_admin')->delete();
        $only = $this->makeSuperAdmin();
        $this->assertSame(1, Admin::where('role', 'super_admin')->count());

        // The last super_admin cannot be deleted — not even by themselves.
        $this->actingAs($only, 'admin')->delete('/reddy/admins/'.$only->id)
            ->assertSessionHasErrors('admin');
        $this->assertNotNull(Admin::find($only->id));
    }

    public function test_disabled_admin_cannot_log_in(): void
    {
        $this->makeStaff(['is_active' => false, 'email' => 'disabled-admin@example.com', 'password' => 'password12345']);

        $response = $this->post('/reddy/login', [
            'email' => 'disabled-admin@example.com',
            'password' => 'password12345',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_super_admin_can_disable_and_enable_staff(): void
    {
        $admin = $this->makeSuperAdmin();
        $staff = $this->makeStaff();

        $this->actingAs($admin, 'admin')->patch('/reddy/admins/'.$staff->id.'/toggle')->assertRedirect();
        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($admin, 'admin')->patch('/reddy/admins/'.$staff->id.'/toggle')->assertRedirect();
        $this->assertTrue($staff->fresh()->is_active);
    }

    // ------------------------------------------------------------------
    // Audit log
    // ------------------------------------------------------------------

    public function test_audit_log_viewer_super_admin_only(): void
    {
        $admin = $this->makeSuperAdmin();
        $staff = $this->makeStaff();

        AuditLog::create([
            'admin_id' => $admin->id,
            'action' => 'test.action',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin, 'admin')->get('/reddy/audit-log')->assertOk()->assertSee('test.action');
        $this->actingAs($staff, 'admin')->get('/reddy/audit-log')->assertForbidden();
    }

    // ------------------------------------------------------------------
    // CSV exports
    // ------------------------------------------------------------------

    public function test_orders_csv_export_contains_correct_rows(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $order = $this->makePaidOrder($user, 'INR', 149);

        $response = $this->actingAs($admin, 'admin')->get('/reddy/orders/export');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString($order->order_number, $content);
        $this->assertStringContainsString($user->email, $content);
        $this->assertStringContainsString('INR', $content);
    }

    public function test_orders_csv_export_respects_filters(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $inr = $this->makePaidOrder($user, 'INR', 149);
        $usd = $this->makePaidOrder($user, 'USD', 4.99);

        $content = $this->actingAs($admin, 'admin')
            ->get('/reddy/orders/export?currency=USD')
            ->streamedContent();

        $this->assertStringContainsString($usd->order_number, $content);
        $this->assertStringNotContainsString($inr->order_number, $content);
    }

    public function test_users_csv_export_contains_correct_rows(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();

        $content = $this->actingAs($admin, 'admin')
            ->get('/reddy/users/export')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($user->email, $content);
        $this->assertStringContainsString('INR', $content);
    }

    // ------------------------------------------------------------------
    // Sessions
    // ------------------------------------------------------------------

    public function test_sessions_page_loads(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')->get('/reddy/sessions')->assertOk();
    }

    public function test_logout_other_sessions_requires_password(): void
    {
        $admin = $this->makeSuperAdmin();

        // Wrong password → validation error, nothing happens.
        $this->actingAs($admin, 'admin')
            ->delete('/reddy/sessions/others', ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        // Correct password → audited, success message.
        $response = $this->actingAs($admin, 'admin')
            ->delete('/reddy/sessions/others', ['password' => 'password12345']);
        $response->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.sessions_revoked']);
    }
}
