<?php

use Illuminate\Support\Facades\Artisan;

use App\User;
use App\Post;
use App\Phone;
use App\Photo;

class EloquentTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Artisan::call('migrate:refresh', ['--seed' => '--seed']);
    }

    public function testGet()
    {
        $users = User::get();
        $this->assertEquals(3, $users->count());

        $users = User::where('id', '>', 1)->get();
        $this->assertEquals(2, $users->count());

        // test chunk

        User::chunk(2, function($users){
           foreach($users as $user) {
               $this->assertInternalType('int', $user->id);
           }
        });

        User::chunk(1, function($users){
            $this->assertInstanceOf('Illuminate\Support\Collection', $users);
        });

        // test find with query builder
        $user = User::where('id', '>', 1)->find(1);
        $this->assertNull($user);
        $user = User::where('id', '>', 1)->find(2);
        $this->assertEquals(2, $user->id);

        // test first without query builder
        $user = User::first();
        $this->assertInstanceOf('App\User', $user);

        // test retrieving aggregates
        $max_id = User::max('id');
        $this->assertGreaterThanOrEqual(3, $max_id);
        $count = User::count();
        $this->assertGreaterThanOrEqual(3, $count);
        $sum = User::sum('id');
        $this->assertGreaterThanOrEqual(6, $sum);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFindOrFail()
    {
        $user = User::findOrFail(100);
    }

    public function testReadType()
    {
        // int -> int
        $user = User::find(1);
        $this->assertInternalType('int', $user->id);

        // varchar -> string
        $this->assertInternalType('string', $user->name);

        // timestamp -> Carbon
        $this->assertInstanceOf('Carbon\Carbon', $user->created_at);

        // NULL -> null
        $post = Post::find(4);
        $this->assertInternalType('null', $post->content);

        // empty string
        $post = Post::find(5);
        $this->assertInternalType('string', $post->content);
    }

    public function testCreateTypeOK()
    {
        // string -> int
        $post = new Post;
        $post->content = '123';
        $post->user_id = '1';
        $post->save();
        $id = $post->id;
        $post = Post::find($id);
        $this->assertSame(1, $post->user_id);

        // int -> string
        $post = new Post;
        $post->content = 123;
        $post->user_id = 1;
        $post->save();
        $id = $post->id;
        $post = Post::find($id);
        $this->assertSame('123', $post->content);

        // null -> NULL
        $post = new Post;
        $post->content = null;
        $post->user_id = 1;
        $post->save();
        $id = $post->id;
        $post = Post::find($id);
        $this->assertInternalType('null', $post->content);

        // string null -> string
        $post = new Post;
        $post->content = 'NULL';
        $post->user_id = 1;
        $post->save();
        $id = $post->id;
        $post = Post::find($id);
        $this->assertInternalType('string', $post->content);
    }

    /**
     * @expectedException Illuminate\Database\QueryException
     */
    public function testCreateTypeNotOK()
    {
        
    }
}