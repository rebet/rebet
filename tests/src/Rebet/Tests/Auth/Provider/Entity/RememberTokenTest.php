<?php
namespace Rebet\Tests\Auth\Provider\Entity;

use Rebet\Auth\Provider\Entity\RememberToken;
use Rebet\Database\Database;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Securities;

class RememberTokenTest extends RebetDatabaseTestCase
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
        $this->assertInstanceOf(RememberToken::class, new RememberToken());
    }

    public function test_deleteExpired()
    {
        $this->eachDb(function (Database $db) {
            $this->assertSame(3, RememberToken::count());
            $this->assertNotNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-b')]));
            $this->assertSame(1, RememberToken::deleteExpired());
            $this->assertNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-b')]));
            $this->assertSame(2, RememberToken::count());
        });
    }

    public function test_deleteByUser()
    {
        $this->eachDb(function (Database $db) {
            $this->assertSame(3, RememberToken::count());
            $this->assertNotNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-a')]));
            $this->assertSame(2, RememberToken::deleteByUser('web', '1'));
            $this->assertNull(RememberToken::find(['provider' => 'web', 'remember_token' => Securities::hash('token-1-a')]));
            $this->assertSame(1, RememberToken::count());
        });
    }
}
