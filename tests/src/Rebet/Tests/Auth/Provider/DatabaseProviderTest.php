<?php
namespace Rebet\Tests\Auth\Provider;

use Rebet\Auth\Password;
use Rebet\Auth\Provider\DatabaseProvider;
use Rebet\Auth\Provider\Entity\RememberToken;
use Rebet\Database\Database;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Securities;

class DatabaseProviderTest extends RebetDatabaseTestCase
{
    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user', 'password' => '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci', 'api_token' => 'fe0c1b9ca200d6e01d96f60bab714cbbaffdf89fed5a946ff1b9f024902d2a26'], // password-{user_id}, api-{user_id}
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user', 'password' => '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW', 'api_token' => '3d9b9b04a60382dd0f0acb2672b3b87acba7e9a9e44c529ba37baebe1cf9a00c'], // password-{user_id}, api-{user_id}
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user', 'password' => '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe', 'api_token' => 'df38d2697f917ca9460677a98bfbb8baaeabab8e83b9858ea70d6da10b06ad4d'], // password-{user_id}, api-{user_id}
            ],
            'remember_tokens' => [
                ['provider' => 'web', 'remember_token' => Securities::hash('token-1-a'), 'remember_id' => '1', 'expires_at' => DateTime::now()->addDay(30)],
                ['provider' => 'web', 'remember_token' => Securities::hash('token-1-b'), 'remember_id' => '1', 'expires_at' => DateTime::now()->addDay(-3)],
                ['provider' => 'web', 'remember_token' => Securities::hash('token-2-a'), 'remember_id' => '2', 'expires_at' => DateTime::now()->addDay(15)],
            ],
        ][$table_name] ?? [];
    }

    public function test___construct()
    {
        $this->assertInstanceOf(DatabaseProvider::class, new DatabaseProvider(User::class));
    }

    public function test_findById()
    {
        $this->eachDb(function (Database $db) {
            $provider = new DatabaseProvider(User::class);
            $this->assertNull($provider->findById(null));
            $this->assertNull($provider->findById(0));
            $this->assertTrue(User::find(1)->isSameAs($provider->findById(1)->raw()));
        });
    }

    public function test_findByToken()
    {
        $this->eachDb(function (Database $db) {
            $provider = new DatabaseProvider(User::class);

            $user = $provider->findByToken(null);
            $this->assertNull($user);

            $user = $provider->findByToken('invalid_token');
            $this->assertNull($user);

            foreach (User::select() as $expect_user) {
                $user = $provider->findByToken("api-{$expect_user->user_id}");
                $this->assertTrue($expect_user->isSameAs($user->raw()));
            }
        });
    }

    public function test_findByCredentials()
    {
        $this->eachDb(function (Database $db) {
            $provider = new DatabaseProvider(User::class);

            $this->assertNull($provider->findByCredentials(null, null));
            $this->assertNull($provider->findByCredentials('elody@s1.rebet.local', null));
            $this->assertNull($provider->findByCredentials(null, "password-1"));
            $this->assertNull($provider->findByCredentials('elody@s1.rebet.local', "password-invalid"));

            foreach (User::select() as $expect_user) {
                $user = $provider->findByCredentials($expect_user->email, "password-{$expect_user->user_id}");
                $this->assertTrue($expect_user->isSameAs($user->raw()));
            }
        });
    }

    public function test_rehashPassword()
    {
        $this->eachDb(function (Database $db) {
            $provider     = new DatabaseProvider(User::class);
            $user         = $provider->findById(1);
            $old_password = $user->password;

            $provider->rehashPassword(1, Password::hash('new'));

            $user         = $provider->findById(1);
            $new_password = $user->password;

            $this->assertNotSame($old_password, $new_password);
        });
    }

    public function test_supportRememberToken()
    {
        $provider = new DatabaseProvider(User::class);
        $this->assertTrue($provider->supportRememberToken());
    }

    public function test_findByRememberToken()
    {
        $this->eachDb(function (Database $db) {
            $provider = (new DatabaseProvider(User::class))->name('web');

            $this->assertNull($provider->findByRememberToken(null));

            $this->assertNotNull($user = $provider->findByRememberToken('token-1-a'));
            $this->assertTrue(User::find(1)->isSameAs($user->raw()));

            $this->assertNull($provider->findByRememberToken('token-1-b'));

            $this->assertNotNull($user = $provider->findByRememberToken('token-2-a'));
            $this->assertTrue(User::find(2)->isSameAs($user->raw()));

            $this->assertNull($provider->findByRememberToken('token-3-a'));

            $provider = (new DatabaseProvider(User::class))->name('user');
            $this->assertNull($provider->findByRememberToken('token-1-a'));
        });
    }

    public function test_issuingRememberToken()
    {
        DateTime::setTestNow('2020-01-10 00:00:00');
        $this->eachDb(function (Database $db) {
            $provider = (new DatabaseProvider(User::class))->name('web');

            foreach (User::select() as $user) {
                $this->assertNotNull($token = $provider->issuingRememberToken($user->user_id, 3));
                $this->assertNotNull($remember_token = RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash($token)]));
                $this->assertSame('2020-01-13 00:00:00', $remember_token->expires_at->format());
                $this->assertTrue($user->isSameAs($provider->findByRememberToken($token)->raw()));
            }
        });
    }

    public function test_removeRememberToken()
    {
        $this->eachDb(function (Database $db) {
            $provider = (new DatabaseProvider(User::class))->name('web');

            $this->assertNotNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-a')]));
            $provider->removeRememberToken('token-1-a');
            $this->assertNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-a')]));
        });
    }

    public function test_removeRememberToken_withExpired()
    {
        $this->eachDb(function (Database $db) {
            $provider = (new DatabaseProvider(User::class, 'email', 'password', 'api_token', 1))->name('web');

            $this->assertNotNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-a')]));
            $this->assertNotNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-b')]));
            $provider->removeRememberToken('token-1-a');
            $this->assertNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-a')]));
            $this->assertNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-b')]));
        });
    }

    public function test_name()
    {
        $provider = new DatabaseProvider(User::class);
        $this->assertNull($provider->name());
        $this->assertInstanceOf(DatabaseProvider::class, $provider->name('web'));
        $this->assertSame('web', $provider->name());
    }
}
