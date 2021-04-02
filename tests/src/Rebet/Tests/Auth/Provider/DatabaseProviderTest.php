<?php
namespace Rebet\Tests\Auth\Provider;

use App\Model\User;
use Rebet\Auth\Password;
use Rebet\Auth\Provider\DatabaseProvider;
use Rebet\Auth\Provider\Entity\RememberToken;
use Rebet\Database\Database;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Securities;

class DatabaseProviderTest extends RebetDatabaseTestCase
{
    protected function setUp() : void {
        parent::setUp();
        $this->setUpDataSet([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                 , 'role' , 'password'                                                     , 'api_token'                                                        ],
                // ------- | ---------------- | -------- | ------------ | ----------------------- | ------ | -------------------------------------------------------------- | ----------------------------------------------------------------- //
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.rebet.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , 'fe0c1b9ca200d6e01d96f60bab714cbbaffdf89fed5a946ff1b9f024902d2a26' ], // password-{user_id}, api-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.rebet.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '3d9b9b04a60382dd0f0acb2672b3b87acba7e9a9e44c529ba37baebe1cf9a00c' ], // password-{user_id}, api-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.rebet.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , 'df38d2697f917ca9460677a98bfbb8baaeabab8e83b9858ea70d6da10b06ad4d' ], // password-{user_id}, api-{user_id}
            ],
            'remember_tokens' => [
                ['provider' , 'remember_token'              , 'remember_id' , 'expires_at'                ],
                // -------- | ----------------------------- | ------------- | -------------------------- //
                ['web'      , Securities::hash('token-1-a') , '1'           , DateTime::now()->addDay(30) ],
                ['web'      , Securities::hash('token-1-b') , '1'           , DateTime::now()->addDay(-3) ],
                ['web'      , Securities::hash('token-2-a') , '2'           , DateTime::now()->addDay(15) ],
            ],
        ]);
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
