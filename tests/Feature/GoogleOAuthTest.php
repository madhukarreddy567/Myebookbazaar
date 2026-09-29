<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/**
 * Google OAuth sign-in (customer).
 *
 * Uses DatabaseTransactions against the MySQL dev database; every test
 * rolls back, and the settings cache is cleared between tests.
 */
class GoogleOAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
    }

    protected function tearDown(): void
    {
        app(SettingsService::class)->forgetCache();
        parent::tearDown();
    }

    protected function enableGoogle(string $clientId = 'test-client-id.apps.googleusercontent.com', string $secret = 'test-secret'): void
    {
        $settings = app(SettingsService::class);
        $settings->set('auth.google.enabled', '1');
        $settings->set('auth.google.client_id', $clientId);
        $settings->set('auth.google.client_secret', $secret);
        $settings->forgetCache();
    }

    protected function makeSocialUser(string $id = 'google-123', string $email = 'reader@example.com', bool $verified = true): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->setRaw([
            'sub' => $id,
            'email' => $email,
            'email_verified' => $verified,
            'name' => 'Test Reader',
        ]);
        $user->map([
            'id' => $id,
            'nickname' => null,
            'name' => 'Test Reader',
            'email' => $email,
            'avatar' => null,
        ]);

        return $user;
    }

    protected function mockGoogleDriver(?SocialiteUser $user = null, bool $throw = false): void
    {
        $driver = \Mockery::mock();
        if ($throw) {
            $driver->shouldReceive('user')->andThrow(new \Exception('Google exploded'));
        } else {
            $driver->shouldReceive('user')->andReturn($user ?? $this->makeSocialUser());
        }
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    protected function makeAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);
    }

    // ------------------------------------------------------------------
    // Button visibility
    // ------------------------------------------------------------------

    public function test_google_buttons_hidden_when_disabled(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertDontSee('Continue with Google', false);
        $this->get(route('register'))->assertOk()
            ->assertDontSee('Sign up with Google', false);
    }

    public function test_google_buttons_hidden_when_enabled_but_no_credentials(): void
    {
        app(SettingsService::class)->set('auth.google.enabled', '1');
        app(SettingsService::class)->forgetCache();

        $this->get(route('login'))->assertOk()
            ->assertDontSee('Continue with Google', false);
    }

    public function test_google_buttons_shown_when_configured(): void
    {
        $this->enableGoogle();

        $this->get(route('login'))->assertOk()
            ->assertSee('Continue with Google', false)
            ->assertSee(route('auth.google.redirect'), false);
        $this->get(route('register'))->assertOk()
            ->assertSee('Sign up with Google', false);
    }

    // ------------------------------------------------------------------
    // Redirect
    // ------------------------------------------------------------------

    public function test_redirect_route_refuses_when_not_configured(): void
    {
        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'));
    }

    public function test_redirect_route_starts_google_flow_when_configured(): void
    {
        $this->enableGoogle();

        $driver = \Mockery::mock();
        $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth?client_id=x'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth?client_id=x');
    }

    // ------------------------------------------------------------------
    // Callback
    // ------------------------------------------------------------------

    public function test_callback_creates_new_user_and_logs_in(): void
    {
        $this->enableGoogle();
        $this->mockGoogleDriver($this->makeSocialUser('google-999', 'newreader@example.com', true));

        $response = $this->get(route('auth.google.callback', ['code' => 'abc', 'state' => 'xyz']));

        $user = User::where('email', 'newreader@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-999', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user, 'web');
        // Email verification switch is on by default and Google verified
        // the email, so the user lands on their account.
        $response->assertRedirect(route('account'));
    }

    public function test_callback_links_existing_account_with_verified_email(): void
    {
        $this->enableGoogle();
        $user = User::create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ]);

        $this->mockGoogleDriver($this->makeSocialUser('google-555', 'existing@example.com', true));
        $this->get(route('auth.google.callback', ['code' => 'abc']));

        $this->assertSame('google-555', $user->fresh()->google_id);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_callback_does_not_link_when_google_email_unverified(): void
    {
        $this->enableGoogle();
        $user = User::create([
            'name' => 'Existing',
            'email' => 'someone@example.com',
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ]);

        $this->mockGoogleDriver($this->makeSocialUser('google-666', 'someone@example.com', false));
        $this->get(route('auth.google.callback', ['code' => 'abc']))
            ->assertRedirect(route('login'));

        $this->assertNull($user->fresh()->google_id);
        $this->assertGuest('web');
    }

    public function test_callback_finds_user_by_google_id(): void
    {
        $this->enableGoogle();
        $user = User::create([
            'name' => 'Linked',
            'email' => 'linked@example.com',
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
            'google_id' => 'google-777',
        ]);

        $this->mockGoogleDriver($this->makeSocialUser('google-777', 'linked@example.com', true));
        $this->get(route('auth.google.callback', ['code' => 'abc']));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_denied_callback_is_handled_gracefully(): void
    {
        $this->enableGoogle();

        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest('web');
    }

    public function test_callback_failure_shows_friendly_error(): void
    {
        $this->enableGoogle();
        $this->mockGoogleDriver(throw: true);

        $this->get(route('auth.google.callback', ['code' => 'abc']))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_guest_cart_merges_on_google_login(): void
    {
        $this->enableGoogle();

        $book = \App\Models\Book::where('slug', 'fit-at-home-start-edition-1')->firstOrFail();

        // Guest adds a book, keeping the same browser session for the callback.
        $this->carrySessionCookie(
            $this->post(route('cart.store'), ['book_id' => $book->id])->assertRedirect()
        );

        $this->mockGoogleDriver($this->makeSocialUser('google-888', 'cartreader@example.com', true));
        $this->get(route('auth.google.callback', ['code' => 'abc', 'state' => 'xyz']));

        $user = User::where('email', 'cartreader@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('carts', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseMissing('carts', [
            'book_id' => $book->id,
            'user_id' => null,
        ]);
    }

    /**
     * Carry the (encrypted) session cookie from one test response into the
     * next request, so consecutive HTTP calls share one guest session.
     */
    protected function carrySessionCookie(\Illuminate\Testing\TestResponse $response): void
    {
        $name = config('session.cookie');
        $cookie = $response->getCookie($name, false);

        if ($cookie) {
            $this->withUnencryptedCookie($name, $cookie->getValue());
        }
    }

    // ------------------------------------------------------------------
    // Admin: test connection + secret safety
    // ------------------------------------------------------------------

    public function test_test_google_connection_validates_credentials(): void
    {
        $this->enableGoogle('real-id.apps.googleusercontent.com', 'real-secret');
        $admin = $this->makeAdmin();

        // Google accepts the credentials (fake code => invalid_grant).
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('reddy.settings.test-google'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_test_google_connection_rejects_bad_credentials(): void
    {
        $this->enableGoogle('wrong-id.apps.googleusercontent.com', 'wrong-secret');
        $admin = $this->makeAdmin();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('reddy.settings.test-google'))
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_test_google_connection_requires_super_admin(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'cust-'.uniqid().'@example.com',
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ]);

        $this->actingAs($user, 'web')
            ->postJson(route('reddy.settings.test-google'))
            ->assertRedirect('/reddy/login');
    }

    public function test_secrets_never_leak_in_responses(): void
    {
        $secret = 'super-secret-value-'.uniqid();
        $this->enableGoogle('leak-test.apps.googleusercontent.com', $secret);
        $admin = $this->makeAdmin();

        // Settings page (masked display only).
        $this->actingAs($admin, 'admin')
            ->get(route('reddy.settings', ['tab' => 'updates']))
            ->assertOk()
            ->assertDontSee($secret, false);

        // Test-connection endpoint.
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_grant'], 400),
        ]);
        $this->actingAs($admin, 'admin')
            ->postJson(route('reddy.settings.test-google'))
            ->assertOk()
            ->assertDontSee($secret, false);

        // Public pages.
        $this->get(route('login'))->assertOk()->assertDontSee($secret, false);
    }

    public function test_google_secret_is_encrypted_at_rest(): void
    {
        $secret = 'at-rest-'.uniqid();
        $this->enableGoogle('enc-test.apps.googleusercontent.com', $secret);

        $raw = \App\Models\Setting::where('key', 'auth.google.client_secret')->value('value');
        $this->assertNotSame($secret, $raw);
        $this->assertSame($secret, app(SettingsService::class)->get('auth.google.client_secret'));
    }
}
