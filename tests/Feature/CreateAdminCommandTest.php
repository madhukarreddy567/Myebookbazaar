<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected array $savedEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Start from a clean admins table (rolled back after each test,
        // so the seeded admin used by other suites is untouched).
        Admin::query()->delete();

        foreach (['ADMIN_EMAIL', 'ADMIN_PASSWORD'] as $key) {
            $this->savedEnv[$key] = [
                'server' => $_SERVER[$key] ?? null,
                'env' => $_ENV[$key] ?? null,
                'getenv' => getenv($key) !== false ? getenv($key) : null,
            ];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $key => $vals) {
            if ($vals['server'] !== null) {
                $_SERVER[$key] = $vals['server'];
            } else {
                unset($_SERVER[$key]);
            }
            if ($vals['env'] !== null) {
                $_ENV[$key] = $vals['env'];
            } else {
                unset($_ENV[$key]);
            }
            if ($vals['getenv'] !== null) {
                putenv("{$key}={$vals['getenv']}");
            } else {
                putenv($key);
            }
        }

        parent::tearDown();
    }

    private function setAdminEnv(?string $email, ?string $password): void
    {
        foreach (['ADMIN_EMAIL' => $email, 'ADMIN_PASSWORD' => $password] as $key => $value) {
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    public function test_refuses_when_admin_already_exists(): void
    {
        Admin::create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->artisan('app:create-admin')
            ->expectsOutput('An admin already exists. Aborting.')
            ->assertFailed();

        $this->assertSame(1, Admin::count());
    }

    public function test_creates_super_admin_with_hashed_password(): void
    {
        $this->setAdminEnv('cmdtest@example.com', 'CmdTestPass123');

        $this->artisan('app:create-admin')
            ->expectsOutput('Super Admin created: cmdtest@example.com')
            ->assertSuccessful();

        $admin = Admin::where('email', 'cmdtest@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue(Hash::check('CmdTestPass123', $admin->password));
        $this->assertNotSame('CmdTestPass123', $admin->password);
    }

    public function test_fails_when_env_missing(): void
    {
        $this->setAdminEnv(null, null);

        $this->artisan('app:create-admin')
            ->expectsOutput('ADMIN_EMAIL and ADMIN_PASSWORD must be set in your .env file before running this command.')
            ->assertFailed();

        $this->assertSame(0, Admin::count());
    }
}
