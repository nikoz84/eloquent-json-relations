<?php

namespace Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\Models\Post;
use Tests\Models\User;

class BelongsToJsonTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (! method_exists(DB::connection()->query(), 'whereJsonContains')) {
            $this->markTestSkipped();
        }
    }

    public function testLazyLoading()
    {
        $roles = User::first()->roles;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
    }

    public function testLazyLoadingWithObjects()
    {
        $roles = User::first()->roles2;

        $this->assertEquals([1, 2], $roles->pluck('id')->all());
        $pivot = $roles[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
        $this->assertEquals(['active' => true], $pivot->getAttributes());
    }

    public function testEagerLoading()
    {
        $users = User::with('roles')->get();

        $this->assertEquals([1, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles->pluck('id')->all());
    }

    public function testEagerLoadingWithObjects()
    {
        $users = User::with('roles2')->get();

        $this->assertEquals([1, 2], $users[0]->roles2->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles2->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles2->pluck('id')->all());
        $pivot = $users[0]->roles2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
    }

    public function testLazyEagerLoading()
    {
        $users = User::all()->load('roles');

        $this->assertEquals([1, 2], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles->pluck('id')->all());
    }

    public function testLazyEagerLoadingWithObjects()
    {
        $users = User::get()->load('roles2');

        $this->assertEquals([1, 2], $users[0]->roles2->pluck('id')->all());
        $this->assertEquals([], $users[1]->roles2->pluck('id')->all());
        $this->assertEquals([2, 3], $users[2]->roles2->pluck('id')->all());
        $pivot = $users[0]->roles2[0]->pivot;
        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertTrue($pivot->exists);
    }

    public function testExistenceQuery()
    {
        $users = User::has('roles')->get();

        $this->assertEquals([1, 3], $users->pluck('id')->all());
    }

    public function testExistenceQueryWithObjects()
    {
        $users = User::has('roles2')->get();

        $this->assertEquals([1, 3], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation()
    {
        $posts = Post::has('recommendations')->get();

        $this->assertEquals([1], $posts->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationWithObjects()
    {
        $posts = Post::has('recommendations2')->get();

        $this->assertEquals([1], $posts->pluck('id')->all());
    }

    public function testAttach()
    {
        $user = (new User)->roles()->attach([1, 2]);

        $this->assertEquals([1, 2], $user->roles()->pluck('id')->all());

        $user->roles()->attach([2, 3]);

        $this->assertEquals([1, 2, 3], $user->roles()->pluck('id')->all());
    }

    public function testAttachWithObjects()
    {
        $user = (new User)->roles2()->attach([1 => ['active' => true], 2 => ['active' => false]]);

        $this->assertEquals([1, 2], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.active')->all());

        $user->roles2()->attach([2 => ['active' => true], 3 => ['active' => false]]);

        $roles = $user->load('roles2')->roles2->sortBy('id')->values();
        $this->assertEquals([1, 2, 3], $roles->pluck('id')->all());
        $this->assertEquals([true, true, false], $roles->pluck('pivot.active')->all());
    }

    public function testDetach()
    {
        $user = User::first()->roles()->detach(2);

        $this->assertEquals([1], $user->roles()->pluck('id')->all());

        $user->roles()->detach();

        $this->assertEquals([], $user->roles()->pluck('id')->all());
    }

    public function testDetachWithObjects()
    {
        $user = User::first()->roles2()->detach(2);

        $this->assertEquals([1], $user->roles2->pluck('id')->all());
        $this->assertEquals([true], $user->roles2->pluck('pivot.active')->all());

        $user->roles2()->detach();

        $this->assertEquals([], $user->roles2()->pluck('id')->all());
    }

    public function testSync()
    {
        $user = User::first()->roles()->sync([2, 3]);

        $this->assertEquals([2, 3], $user->roles()->pluck('id')->all());
    }

    public function testSyncWithObjects()
    {
        $user = User::first()->roles2()->sync([2 => ['active' => true], 3 => ['active' => false]]);

        $this->assertEquals([2, 3], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.active')->all());
    }

    public function testToggle()
    {
        $user = User::first()->roles()->toggle([2, 3]);

        $this->assertEquals([1, 3], $user->roles()->pluck('id')->all());
    }

    public function testToggleWithObjects()
    {
        $user = User::first()->roles2()->toggle([2, 3 => ['active' => false]]);

        $this->assertEquals([1, 3], $user->roles2->pluck('id')->all());
        $this->assertEquals([true, false], $user->roles2->pluck('pivot.active')->all());
    }
}
