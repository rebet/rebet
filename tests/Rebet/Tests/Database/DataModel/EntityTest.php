<?php
namespace Rebet\Tests\Database\DataModel;

use Rebet\Database\Database;
use Rebet\Database\Event\BatchDeleted;
use Rebet\Database\Event\BatchDeleting;
use Rebet\Database\Event\BatchUpdated;
use Rebet\Database\Event\BatchUpdating;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Event\Event;
use Rebet\Tests\Mock\Entity\Article;
use Rebet\Tests\Mock\Entity\GroupUser;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\Mock\Entity\UserWithAnnot;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\Mock\Enum\GroupPosition;
use Rebet\Tests\RebetDatabaseTestCase;

class EntityTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    protected function tables(string $db_name) : array
    {
        return static::BASIC_TABLES[$db_name === 'main' ? 'sqlite' : $db_name] ?? [];
    }

    protected function records(string $db_name, string $table_name) : array
    {
        return [
            'users' => [
                ['user_id' => 1 , 'name' => 'Elody Bode III'        , 'gender' => 2, 'birthday' => '1990-01-08', 'email' => 'elody@s1.rebet.local' , 'role' => 'user'],
                ['user_id' => 2 , 'name' => 'Alta Hegmann'          , 'gender' => 1, 'birthday' => '2003-02-16', 'email' => 'alta_h@s2.rebet.local', 'role' => 'user'],
                ['user_id' => 3 , 'name' => 'Damien Kling'          , 'gender' => 1, 'birthday' => '1992-10-17', 'email' => 'damien@s0.rebet.local', 'role' => 'user'],
            ],
        ][$table_name] ?? [];
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Origin must be same class of [Rebet\Tests\Mock\Entity\User].
     */
    public function test_origin_diffrentClass()
    {
        $user  = new User();
        $othre = new UserWithAnnot();
        $user->origin($othre);
    }

    public function test_unmaps()
    {
        $base_protected = ['_origin', '_annotated_class', '_meta', '_belongs_result_set', '_relations'];
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
    }

    public function test_changes()
    {
        $user = new User();
        $this->assertSame([], $user->changes());
        $user->name = 'foo';
        $this->assertSame(['name' => 'foo'], $user->changes());
        $user->gender = Gender::FEMALE();
        $this->assertSame(['name' => 'foo', 'gender' => Gender::FEMALE()], $user->changes());
        $user->birthday = '1980-01-02';
        $this->assertSame(['name' => 'foo', 'gender' => Gender::FEMALE(), 'birthday' => '1980-01-02'], $user->changes());
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

    public function test_exist()
    {
        $this->eachDb(function (Database $db) {
            $user = new User();
            $this->assertSame(false, $user->exist());
            $user->user_id = 1;
            $this->assertSame(true, $user->exist());
            $user->user_id = 4;
            $this->assertSame(false, $user->exist());

            $user = User::find(1);
            $this->assertSame(true, $user->delete());
            $this->assertSame(false, $user->exist());
            $this->assertSame($db->driverName() === 'mysql' ? false : true, $user->exist('mysql'));
            $this->assertSame(true, $user->create());

            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exist());
            $this->assertSame(true, $gu->create());
            $this->assertSame(true, $gu->exist());
        });
    }

    public function test_create()
    {
        $this->eachDb(function (Database $db) {
            $now = DateTime::now()->startsOfSecond();

            $user = new UserWithAnnot();
            $user->user_id = 99;
            $this->assertSame(false, $user->exist());
            $this->assertSame(null, $user->created_at);

            $this->assertSame(true, $user->create($now));

            $this->assertSame(true, $user->exist());
            $this->assertEquals($now, $user->created_at);

            $user = UserWithAnnot::find(99);
            $this->assertEquals($now, $user->created_at);

            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exist());
            $this->assertSame(null, $gu->created_at);

            $this->assertSame(true, $gu->create($now));

            $this->assertSame(true, $gu->exist());
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
            $this->assertSame(false, $gu->exist());
            $this->assertSame(true, $gu->create($now));
            $this->assertSame(true, $gu->exist());
            $this->assertSame(null, $gu->updated_at);

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
            $this->assertSame(false, $user->exist());
            $this->assertSame(null, $user->created_at);
            $this->assertSame(null, $user->updated_at);

            $this->assertSame(true, $user->save($now));

            $this->assertSame(true, $user->exist());
            $this->assertEquals($now, $user->created_at);
            $this->assertSame(null, $user->updated_at);

            $this->assertSame(true, $user->save($new_now = $now->addSecond(1)));

            $this->assertEquals($now, $user->created_at);
            $this->assertEquals($new_now, $user->updated_at);


            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exist());
            $this->assertSame(null, $gu->created_at);
            $this->assertSame(null, $gu->updated_at);

            $this->assertSame(true, $gu->save($now));

            $this->assertSame(true, $gu->exist());
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
            $this->assertSame(true, $user->exist());
            $this->assertSame(true, $user->delete());
            $this->assertSame(false, $user->exist());

            $gu = new GroupUser();
            $gu->user_id  = 1;
            $gu->group_id = 1;
            $this->assertSame(false, $gu->exist());
            $this->assertSame(true, $gu->save($now));
            $this->assertSame(true, $gu->exist());
            $this->assertSame(true, $gu->delete());
            $this->assertSame(false, $gu->exist());
        });
    }

    public function test_updates()
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
            $this->assertEquals(0, User::updates(['name' => 'foo'], ['user_id' => 9999]));
            $this->assertTrue($updating_event_called);
            $this->assertFalse($updated_event_called);

            $now                   = DateTime::now();
            $updating_event_called = false;
            $updated_event_called  = false;
            $this->assertFalse($updating_event_called);
            $this->assertFalse($updated_event_called);
            $this->assertEquals(2, User::updates(['name' => 'foo', 'role' => 'admin'], ['user_id_lteq' => 2], $now));
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

    public function test_deletes()
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
            $this->assertEquals(0, User::deletes(['user_id' => 9999]));
            $this->assertTrue($deleting_event_called);
            $this->assertFalse($deleted_event_called);

            $deleting_event_called = false;
            $deleted_event_called  = false;
            $this->assertFalse($deleting_event_called);
            $this->assertFalse($deleted_event_called);
            $this->assertEquals(2, User::deletes(['user_id_lteq' => 2]));
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

    public function test_exists()
    {
        $this->eachDb(function (Database $db) {
            $this->assertFalse(User::exists(['user_id' => 9999]));
            $this->assertTrue(User::exists(['user_id' => 1]));
            $this->assertTrue(User::exists(['user_id' => 1, 'gender' => Gender::FEMALE()]));
            $this->assertFalse(User::exists(['user_id' => 1, 'gender' => Gender::MALE()]));
            $this->assertTrue(User::exists(['user_id_lt' => 9999]));
        });
    }

    public function test_counts()
    {
        $this->eachDb(function (Database $db) {
            $this->assertEquals(0, User::counts(['user_id' => 9999]));
            $this->assertEquals(1, User::counts(['user_id' => 1]));
            $this->assertEquals(1, User::counts(['user_id' => 1, 'gender' => Gender::FEMALE()]));
            $this->assertEquals(0, User::counts(['user_id' => 1, 'gender' => Gender::MALE()]));
            $this->assertEquals(2, User::counts(['user_id_lt' => 3]));
            $this->assertEquals(2, User::counts(['gender' => Gender::MALE()]));
            $this->assertEquals(3, User::counts());
        });
    }
}
