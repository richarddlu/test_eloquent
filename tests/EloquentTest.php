<?php

use Illuminate\Support\Facades\Artisan;

use App\User;
use App\Post;
use App\Phone;
use App\Photo;
use App\DefaultTest;

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

        // NULL -> NULL
        $post = Post::find(4);
        $this->assertInternalType('null', $post->content);

        // empty varchar -> string
        $post = Post::find(5);
        $this->assertSame('', $post->content);
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

        // NULL -> NULL
        $post = new Post;
        $post->content = null;
        $post->user_id = 1;
        $post->save();
        $id = $post->id;
        $post = Post::find($id);
        $this->assertInternalType('null', $post->content);

        // string NULL -> string
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
    public function testCreateTypeStringNullToIntNull()
    {
        // string NULL -> int NULL
        $post = new Post;
        $post->content = '123';
        $post->user_id = 'NULL';
        $post->save();
    }

    public function testCreate()
    {
        // test firstOrCreate
        $post = Post::firstOrCreate(['content' => 'post 1']);   // record exists
        $this->assertEquals(1, $post->id);
        $post = Post::firstOrCreate(['content' => 'post xxx']); // record does not exist
        $post = Post::where('content', '=', 'post xxx')->first();
        $this->assertEquals('post xxx', $post->content);

        // test firstOrNew
        $post = Post::firstOrNew(['content' => 'post 1']);   // record exists
        $this->assertEquals(1, $post->id);
        $post = Post::firstOrNew(['content' => 'post yyy']); // record does not exist
        $this->assertEquals('post yyy', $post->content);
        $this->assertFalse(property_exists($post, 'user_id'));
        $post = Post::where('content', '=', 'post yyy')->first();
        $this->assertNull($post);
    }

    public function testDelete()
    {
        // test delete()
        $phone = Phone::find(1);
        $this->assertNotNull($phone);
        $phone = Phone::find(1);
        $phone->delete();
        $phone = Phone::find(1);
        $this->assertNull($phone);

        // test delete() with query builder
        // Post is soft delete model
        $posts = Post::where('id', '<', 3)->get();
        $this->assertFalse($posts->isEmpty());
        Post::where('id', '<', 3)->delete();
        $posts = Post::where('id', '<', 3)->get();
        $this->assertTrue($posts->isEmpty());

        // test destroy()
        $phone = Phone::find(2);
        $this->assertNotNull($phone);
        Phone::destroy(2);
        $phone = Phone::find(2);
        $this->assertNull($phone);
        $photos = Photo::all();
        $this->assertFalse($photos->isEmpty());
        Photo::destroy([1,2,3]);
        $photos = Photo::all();
        $this->assertTrue($photos->isEmpty());

        // test destopy with non-existing primary key
        // expect no exception
        Photo::destroy([1,2,3]);
        $photos = Photo::all();
        $this->assertTrue($photos->isEmpty());
    }

    public function testSoftDelete()
    {
        // test trashed()
        $post = Post::find(1);
        $post->delete();
        $this->assertTrue($post->trashed());

        // test withTrashed()
        Post::find(2)->delete();
        $post = Post::find(2);
        $this->assertNull($post);
        $post = Post::withTrashed()->find(2);
        $this->assertNotNull($post);

        // test onlyTrashed()
        $post = Post::find(3);
        $this->assertNotNull($post);
        $post = Post::onlyTrashed()->find(3);
        $this->assertNull($post);

        // test restore
        $post = Post::find(3);
        $post->delete();
        $post->restore();
        $post1 = Post::find(3);
        $this->assertNull($post1);
        $post = Post::onlyTrashed()->find(3);
        $post->restore();
        $post1 = Post::find(3);
        $this->assertNotNull($post1);
    }

    public function testScope()
    {
        // test static scope
        $posts = Post::findID3()->get();
        $this->assertEquals(1, $posts->count());

        // test dynamic scope
        $posts = Post::findIDAndContent(5, '')->get();
        $this->assertEquals(1, $posts->count());
    }

    public function testDefaultValue()
    {
        $test = new DefaultTest;
        $test->save();
        $id = $test->id;
        $test = DefaultTest::find($id);
        // string_not_null_no_default
        $this->assertSame('', $test->string_not_null_no_default);
        // string_not_null_default
        $this->assertEquals('default string', $test->string_not_null_default);
        // string_null_no_default
        $this->assertNull($test->string_null_no_default);
        // string_null_default
        $this->assertEquals('default string', $test->string_null_default);
        // integer_not_null_no_default
        $this->assertSame(0, $test->integer_not_null_no_default);
        // integer_not_null_default
        $this->assertEquals(5, $test->integer_not_null_default);
        // integer_null_no_default
        $this->assertNull($test->integer_null_no_default);
        // integer_null_default
        $this->assertEquals(5, $test->integer_null_default);
    }
}