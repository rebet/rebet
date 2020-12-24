<?php
namespace Rebet\Tests\Auth\Provider\Entity;

use Rebet\Auth\Password;
use Rebet\Auth\Provider\Entity\RememberToken;
use Rebet\Database\Database;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Securities;

class RememberTokenTest extends RebetDatabaseTestCase
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
                ['provider' => 'web', 'remember_token' => Securities::hash('token-2-1'), 'remember_id' => '2', 'expires_at' => DateTime::now()->addDay(15)],
            ],
        ][$table_name] ?? [];
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
