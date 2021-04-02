<?php
namespace Rebet\Tests\Database\DataModel;

use App\Model\Article;
use App\Model\GroupUser;
use App\Model\User;
use App\Model\UserWithAnnot;
use App\Enum\Gender;
use App\Enum\GroupPosition;
use Rebet\Auth\Password;
use Rebet\Database\Database;
use Rebet\Database\Event\BatchDeleted;
use Rebet\Database\Event\BatchDeleting;
use Rebet\Database\Event\BatchUpdated;
use Rebet\Database\Event\BatchUpdating;
use Rebet\Event\Event;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;

class EntityTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
        $this->setUpDataSet([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                 , 'role' , 'password'                                                     , 'api_token'                                                        ],
                // ------- | ---------------- | -------- | ------------ | ----------------------- | ------ | -------------------------------------------------------------- | ----------------------------------------------------------------- //
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.rebet.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , 'fe0c1b9ca200d6e01d96f60bab714cbbaffdf89fed5a946ff1b9f024902d2a26' ], // password-{user_id}, api-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.rebet.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '3d9b9b04a60382dd0f0acb2672b3b87acba7e9a9e44c529ba37baebe1cf9a00c' ], // password-{user_id}, api-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.rebet.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , 'df38d2697f917ca9460677a98bfbb8baaeabab8e83b9858ea70d6da10b06ad4d' ], // password-{user_id}, api-{user_id}
            ],
            'group_user' => null,
        ]);
    }

    public function test_tabelName()
    {
        $this->assertSame('users', User::tabelName());
        $this->assertSame('users', UserWithAnnot::tabelName());
        $this->assertSame('group_user', GroupUser::tabelName()); // This is pivot tabel entity
    }

    public function test_originAndRemoveOrigin()
    {
        $user  = new User();
        $clone = clone $user;
        $this->assertSame(null, $user->origin());
        $this->assertSame($user, $user->origin($clone));
        $this->assertSame($clone, $user->origin());
        $this->assertSame($user, $user->removeOrigin());
        $this->assertSame(null, $user->origin());
    }

    public function test_origin_diffrentClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Origin must be same class of [App\Model\User].");

        $user  = new User();
        $othre = new UserWithAnnot();
        $user->origin($othre);
    }

    public function test_unmaps()
    {
        $base_protected = ['_origin', '_annotated_class', '_meta', '_belongs_result_set', '_eager_loads', '_relations'];
        $this->assertSame($base_protected, Article::unmaps());
        $this->assertSame(array_merge(['unmap'], $base_protected), User::unmaps());
        $this->assertSame(array_merge(['foo', 'bar'], $base_protected), UserWithAnnot::unmaps());
    }

    public function test_defaults()
    {
        $this->assertSame([], Article::defaults());
        $this->assertSame(['role' => ['user', null]], User::defaults());
        $this->assertSame([
            'name'       => ['foo', null],
            'gender'     => [2, Gender::class],
            'birthday'   => ["20 years ago", Date::class],
            'email'      => ['foo@bar.local', null],
            'role'       => ['user', null],
            'created_at' => ['now', DateTime::class],
        ], UserWithAnnot::defaults());
        $this->assertSame([
            'position'   => [3, GroupPosition::class],
            'join_on'    => ['today', Date::class],
            'created_at' => ["now", DateTime::class],
        ], GroupUser::defaults());
    }

    public function test_changes()
    {
        $user = new User();
        $this->assertSame([], $user->changes());
        $user->name = 'foo';
        $this->assertSame(['name' => 'foo'], $user->changes());
        $user->gender = Gender::FEMALE();
        $this->assertSame(['name' => 'foo', 'gender' => Gender::FEMALE()], $user->changes());
        $user->birthday = new Date('1980-01-02');
        $this->assertEquals(['name' => 'foo', 'gender' => Gender::FEMALE(), 'birthday' => new Date('1980-01-02')], $user->changes());
        $user->birthday = null;
        $this->assertSame(['name' => 'foo', 'gender' => Gender::FEMALE()], $user->changes());

        $user->origin(clone $user);
        $this->assertSame([], $user->changes());
        $user->name = 'foo';
        $this->assertSame([], $user->changes());
        $user->name = 'bar';
        $this->assertSame(['name' => 'bar'], $user->changes());
        $user->name = null;
        $this->assertSame(['name' => null], $user->changes());
        $user->birthday = new Date('1980-01-02');
        $this->assertSame(['name' => null, 'birthday' => $user->birthday], $user->changes());
    }

    public function test_isDirty()
    {
        $user = new User();
        $this->assertSame(false, $user->isDirty());
        $user->name = 'foo';
        $this->assertSame(true, $user->isDirty());
        $user->name = null;
        $this->assertSame(false, $user->isDirty());
        $user->name   = 'foo';
        $user->gender = Gender::FEMALE();
        $this->assertSame(true, $user->isDirty());

        $user->origin(clone $user);
        $this->assertSame(false, $user->isDirty());
        $user->name = null;
        $this->assertSame(true, $user->isDirty());
    }

    public function test_isDynamicProperty()
    {
        $user      = new User();
        $user->foo = 1;
        $user->bar = null;
        $this->assertSame(false, $user->isDynamicProperty('name'));
        $this->assertSame(true, $user->isDynamicProperty('foo'));
        $this->assertSame(true, $user->isDynamicProperty('bar'));
        $this->assertSame(true, $user->isDynamicProperty('nothing'));
    }

    public function test_exists()
    {
        $this->eachDb(function (Database $db) {
            $user = new User();
            $this->assertSame(false, $user->exists());
            $user->user_id = 1;
            $this->assertSame(true, $user->exists());
            $user->user_id = 4;
            $this->assertSame(false, $user->exists());

            $user = User::find(1);
            $this->assertSame(true, $user->delete());
            $this->assertSame(false, $user->exists());
            $this->assertSame($db->name() === 'mysql' ? false : true, $user->exists('mysql'));
            $this->assertSame(true, $user->create());

            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exists());
            $this->assertSame(true, $gu->create());
            $this->assertSame(true, $gu->exists());
        });
    }

    public function test_create()
    {
        $this->eachDb(function (Database $db) {
            $now = DateTime::now()->startsOfSecond();

            $user = new UserWithAnnot();
            $user->user_id = 99;
            $user->password = Password::hash($user->user_id);
            $this->assertSame(false, $user->exists());
            $this->assertSame(null, $user->created_at);

            $this->assertSame(true, $user->create($now));

            $this->assertSame(true, $user->exists());
            $this->assertEquals($now, $user->created_at);

            $user = UserWithAnnot::find(99);
            $this->assertEquals($now, $user->created_at);

            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exists());
            $this->assertSame(null, $gu->created_at);

            $this->assertSame(true, $gu->create($now));

            $this->assertSame(true, $gu->exists());
            $this->assertEquals($now, $gu->created_at);

            $gu = GroupUser::find(['user_id' => 1, 'group_id' => 1]);
            $this->assertEquals($now, $gu->created_at);
        });
    }

    public function test_update()
    {
        $this->eachDb(function (Database $db) {
            $now = DateTime::now()->startsOfSecond();

            $user = User::find(1);
            $this->assertSame(Gender::FEMALE(), $user->gender);
            $this->assertSame(null, $user->updated_at);
            $user->gender = Gender::MALE();
            $this->assertSame(true, $user->update($now));
            $this->assertSame(Gender::MALE(), $user->gender);
            $this->assertSame($now, $user->updated_at);

            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exists());
            $this->assertSame(true, $gu->create($now));
            $this->assertSame(true, $gu->exists());
            $this->assertSame(null, $gu->updated_at);
            $this->assertDatabaseMatches($db, [
                'group_user' => [
                    ['group_id' , 'user_id' , 'position' , 'join_on'     , 'created_at' , 'updated_at'],
                    [         1 ,         1 ,          3 , Date::today() ,        $now  , null        ],
                ]
            ]);

            $gu = GroupUser::find(['user_id' => 1, 'group_id' => 1]);
            $this->assertSame(GroupPosition::MEMBER(), $gu->position);
            $this->assertSame(null, $gu->updated_at);
            $gu->position = GroupPosition::LEADER();
            $this->assertSame(true, $gu->update($now));
            $this->assertSame($now, $gu->updated_at);

            $gu = GroupUser::find(['user_id' => 1, 'group_id' => 1]);
            $this->assertEquals($now, $gu->updated_at);
            $this->assertSame(true, $gu->update($new_now = $now->addSecond(1)));
            $this->assertEquals($new_now, $gu->updated_at);
        });
    }

    public function test_save()
    {
        $this->eachDb(function (Database $db) {
            $now = DateTime::now()->startsOfSecond();

            $user = new UserWithAnnot();
            $user->user_id  = 99;
            $user->password = Password::hash($user->user_id);
            $this->assertSame(false, $user->exists());
            $this->assertSame(null, $user->created_at);
            $this->assertSame(null, $user->updated_at);

            $this->assertSame(true, $user->save($now));

            $this->assertSame(true, $user->exists());
            $this->assertEquals($now, $user->created_at);
            $this->assertSame(null, $user->updated_at);

            $this->assertSame(true, $user->save($new_now = $now->addSecond(1)));

            $this->assertEquals($now, $user->created_at);
            $this->assertEquals($new_now, $user->updated_at);


            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exists());
            $this->assertSame(null, $gu->created_at);
            $this->assertSame(null, $gu->updated_at);

            $this->assertSame(true, $gu->save($now));

            $this->assertSame(true, $gu->exists());
            $this->assertEquals($now, $gu->created_at);
            $this->assertSame(null, $gu->updated_at);

            $this->assertSame(true, $gu->save($new_now = $now->addSecond(1)));

            $this->assertEquals($now, $gu->created_at);
            $this->assertEquals($new_now, $gu->updated_at);
        });
    }

    public function test_delete()
    {
        $this->eachDb(function (Database $db) {
            $now = DateTime::now()->startsOfSecond();

            $user = new UserWithAnnot();
            $user->user_id  = 1;
            $this->assertSame(true, $user->exists());
            $this->assertSame(true, $user->delete());
            $this->assertSame(false, $user->exists());

            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exists());
            $this->assertSame(true, $gu->save($now));
            $this->assertSame(true, $gu->exists());
            $this->assertSame(true, $gu->delete());
            $this->assertSame(false, $gu->exists());
        });
    }

    public function test_updateBy()
    {
        $updating_event_called = false;
        $updated_event_called  = false;
        Event::listen(function (BatchUpdating $event) use (&$updating_event_called) {
            $updating_event_called = true;
        });
        Event::listen(function (BatchUpdated $event) use (&$updated_event_called) {
            $updated_event_called = true;
        });

        $this->eachDb(function (Database $db) use (&$updating_event_called, &$updated_event_called) {
            $updating_event_called = false;
            $updated_event_called  = false;

            $this->assertFalse($updating_event_called);
            $this->assertFalse($updated_event_called);
            $this->assertEquals(0, User::updateBy(['name' => 'foo'], ['user_id' => 9999]));
            $this->assertTrue($updating_event_called);
            $this->assertFalse($updated_event_called);

            $now                   = DateTime::now();
            $updating_event_called = false;
            $updated_event_called  = false;
            $this->assertFalse($updating_event_called);
            $this->assertFalse($updated_event_called);
            $this->assertEquals(2, User::updateBy(['name' => 'foo', 'role' => 'admin'], ['user_id_lteq' => 2], $now));
            $this->assertTrue($updating_event_called);
            $this->assertTrue($updated_event_called);
            foreach ([1, 2] as $user_id) {
                $user = User::find($user_id);
                $this->assertEquals('foo', $user->name);
                $this->assertEquals('admin', $user->role);
                $this->assertEquals($now, $user->updated_at);
            }
            $user = User::find(3);
            $this->assertEquals('Damien Kling', $user->name);
        });
    }

    public function test_deleteBy()
    {
        $deleting_event_called = false;
        $deleted_event_called  = false;
        Event::listen(function (BatchDeleting $event) use (&$deleting_event_called) {
            $deleting_event_called = true;
        });
        Event::listen(function (BatchDeleted $event) use (&$deleted_event_called) {
            $deleted_event_called = true;
        });

        $this->eachDb(function (Database $db) use (&$deleting_event_called, &$deleted_event_called) {
            $deleting_event_called = false;
            $deleted_event_called  = false;

            $this->assertFalse($deleting_event_called);
            $this->assertFalse($deleted_event_called);
            $this->assertEquals(0, User::deleteBy(['user_id' => 9999]));
            $this->assertTrue($deleting_event_called);
            $this->assertFalse($deleted_event_called);

            $deleting_event_called = false;
            $deleted_event_called  = false;
            $this->assertFalse($deleting_event_called);
            $this->assertFalse($deleted_event_called);
            $this->assertEquals(2, User::deleteBy(['user_id_lteq' => 2]));
            $this->assertTrue($deleting_event_called);
            $this->assertTrue($deleted_event_called);
            foreach ([1, 2] as $user_id) {
                $user = User::find($user_id);
                $this->assertNull($user);
            }
            $user = User::find(3);
            $this->assertNotNull($user);
        });
    }

    public function test_existsBy()
    {
        $this->eachDb(function (Database $db) {
            $this->assertFalse(User::existsBy(['user_id' => 9999]));
            $this->assertTrue(User::existsBy(['user_id' => 1]));
            $this->assertTrue(User::existsBy(['user_id' => 1, 'gender' => Gender::FEMALE()]));
            $this->assertFalse(User::existsBy(['user_id' => 1, 'gender' => Gender::MALE()]));
            $this->assertTrue(User::existsBy(['user_id_lt' => 9999]));
        });
    }

    public function test_count()
    {
        $this->eachDb(function (Database $db) {
            $this->assertEquals(0, User::count(['user_id' => 9999]));
            $this->assertEquals(1, User::count(['user_id' => 1]));
            $this->assertEquals(1, User::count(['user_id' => 1, 'gender' => Gender::FEMALE()]));
            $this->assertEquals(0, User::count(['user_id' => 1, 'gender' => Gender::MALE()]));
            $this->assertEquals(2, User::count(['user_id_lt' => 3]));
            $this->assertEquals(2, User::count(['gender' => Gender::MALE()]));
            $this->assertEquals(3, User::count());
        });
    }
}
