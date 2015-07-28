<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Illuminate\Support\Facades\Artisan;

use App\User;
use App\Phone;
use App\Photo;

class RelationModelTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Artisan::call('migrate:refresh', ['--seed' => '--seed']);
    }

    public function testRelationGetOne()
    {
        $user = User::find(1);
        $phone = $user->phone;
        $this->assertInstanceOf('App\Phone', $phone);
        $this->assertEquals('434524', $phone->number);

        $user = User::find(3);
        $phone = $user->phone;
        $this->assertSame(NULL, $phone);
    }

    public function testRelationGetOneReverse()
    {
        $user = User::find(1);
        $phone = $user->phone;
        $user1 = $phone->user;
        $this->assertEquals($user->id, $user1->id);
    }

    public function testRelationGetMany()
    {
        $user = User::find(1);
        $posts = $user->posts;
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $posts);
        $this->assertEquals('post 1', $posts[0]->content);
        $this->assertEquals('post 2', $posts[1]->content);

        $user = User::find(2);
        $posts = $user->posts;
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $posts);
        $this->assertEquals('post 3', $posts[0]->content);

        $user = User::find(3);
        $posts = $user->posts;
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $posts);
        $this->assertSame(TRUE, $posts->isEmpty());
    }

    public function testRelationGetManyReserve()
    {
        $user = User::find(1);
        $posts = $user->posts;
        $user1 = $posts[0]->user;
        $this->assertEquals($user->id, $user1->id);
    }

    public function testMorphGetOne()
    {
        $user = User::find(1);
        $photo = $user->photo;
        $this->assertEquals('photo 1', $photo->desc);
    }

    public function testMorphGetMany()
    {
        $phone = Phone::find(1);
        $photos = $phone->photos;
        $this->assertEquals('photo 2', $photos[0]->desc);
        $this->assertEquals('photo 3', $photos[1]->desc);
    }

    // Save do not update the old record
    // Not guarantee the newly saved model is gotten
    public function testMorphSaveOne()
    {
        $photo = new Photo;
        $photo->desc = 'new photo 1';

        $user = User::find(1);
        $user->photo()->save($photo);

        $photo = $user->photo;
        $this->assertNotEquals('new photo 1', $photo->desc);
    }

    public function testMorphSaveMany()
    {
        $photo = new Photo;
        $photo->desc = 'photo 4';

        $phone = Phone::find(1);
        $phone->photos()->save($photo);

        $photos = $phone->photos;
        $this->assertEquals('photo 2', $photos[0]->desc);
        $this->assertEquals('photo 3', $photos[1]->desc);
        $this->assertEquals('photo 4', $photos[2]->desc);
    }

    public function testMorphHasNo()
    {
        $user = User::find(2);
        $photo = $user->photo;
        $this->assertSame(NULL, $photo);

        $phone = Phone::find(2);
        $photos = $phone->photos;
        $this->assertSame(TRUE, $photos->isEmpty());
    }

    public function testMorphHas()
    {
        $user = User::find(1);
        $photo = $user->photo;
        $this->assertInstanceOf('App\Photo', $photo);

        $phone = Phone::find(1);
        $photos = $phone->photos;
        $this->assertSame(FALSE, $photos->isEmpty());
    }

    public function testMorphUpdateOne()
    {
        $user = User::find(1);
        $photo = $user->photo;
        $photo->desc = 'new photo 1';
        $photo->save();

        $photo = $user->photo;
        $this->assertEquals('new photo 1', $photo->desc);
    }
}
