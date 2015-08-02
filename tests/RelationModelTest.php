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

    // One to one relationship save new model
    // 1. create a new user and save it
    // 2. create a new phone
    // 3. save the phone from user's relationship
    public function testRelationOneToOneSaveSuccessSaveNew()
    {
        // create a new user
        $user = new User;
        $user->name = 'Blank';
        $user->save();

        // create a phone
        $phone = new Phone;
        $phone->number = '666';

        // save the phone
        $user->phone()->save($phone);

        // get the new saved phone
        $phone = Phone::find($phone->id);
        $this->assertEquals($user->id, $phone->user_id);
    }

    // One to one relationship save existing model
    // 1. create a new user and save it
    // 2. get a saved phone
    // 3. save the phone from user's relationship
    public function testRelationOneToOneSaveSuccessSaveExisting()
    {
        // create a new user
        $user = new User;
        $user->name = 'Blank';
        $user->save();

        // get saved phone
        $phone = Phone::find(1);
        $this->assertEquals(1, $phone->user_id);
        // save it to the new user
        $user->phone()->save($phone);
        $this->assertEquals($user->id, $phone->user_id);

        // check what happened to the old phone
        $phone = Phone::find(1);
        // the old phone changed
        $this->assertEquals($user->id, $phone->user_id);

        // assert the original owner does not have a phone any more
        $user = User::find(1);
        $phone = $user->phone;
        $this->assertNull($phone);
    }

    // One to one relastionship, must save a model before saving its related models
    /**
     * @expectedException Illuminate\Database\QueryException
     */
    public function testRelationOneToOneSaveInvalid()
    {
        // create a new user but not save it into DB
        $user = new User;
        $user->name = 'Blank';

        // create a phone
        $phone = new Phone;
        $phone->number = '666';

        // save the phone
        $user->phone()->save($phone);
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

    public function testMorphOneToManySave()
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

    // test order of MorphMany save
    // 1. create a photo
    // 2. save the photo
    // 3. get a saved phone
    // 4. add the photo to the phone
    public function testMorphOneToManySaveOrder1()
    {
        $photo = new Photo;
        $photo->desc = 'photo 4';
        $photo->photoable_id= 0;
        $photo->photoable_type = '';
        $photo->save();
        $id = $photo->id;
        $photo = Photo::find($id);
        $this->assertEquals('photo 4', $photo->desc);

        $phone = Phone::find(1);
        $phone->photos()->save($photo);
        $photos = $phone->photos;
        $this->assertEquals('photo 2', $photos[0]->desc);
        $this->assertEquals('photo 3', $photos[1]->desc);
        $this->assertEquals('photo 4', $photos[2]->desc);
        $this->assertEquals($id, $photos[2]->id);
        $this->assertEquals(1, $photos[2]->photoable_id);
        $this->assertEquals('App\Phone', $photos[2]->photoable_type);
    }

    // test order of MorphMany save
    // 1. create a photo
    // 2. save the photo
    // 3. create a phone
    // 4. save the phone
    // 5. add the photo to the phone
    public function testMorphOneToManySaveOrder2()
    {
        $photo = new Photo;
        $photo->desc = 'photo 4';
        $photo->photoable_id= 0;
        $photo->photoable_type = '';
        $photo->save();
        $id = $photo->id;
        $photo = Photo::find($id);
        $this->assertEquals('photo 4', $photo->desc);

        $phone = new Phone();
        $phone->number = '555';
        $phone->user_id = 3;
        $phone->save();
        $phone->photos()->save($photo);
        $phone_id = $phone->id;

        // test results with the current model
        $photos = $phone->photos;
        $this->assertEquals('photo 4', $photos[0]->desc);
        $this->assertEquals($id, $photos[0]->id);
        $this->assertEquals($phone_id, $photos[0]->photoable_id);
        $this->assertEquals('App\Phone', $photos[0]->photoable_type);

        // test results with the retrieved model from DB
        $phone = Phone::find($phone_id);
        $photos = $phone->photos;
        $this->assertEquals('photo 4', $photos[0]->desc);
        $this->assertEquals($id, $photos[0]->id);
        $this->assertEquals($phone->id, $photos[0]->photoable_id);
        $this->assertEquals('App\Phone', $photos[0]->photoable_type);
    }

    // test order of MorphMany save
    // 1. create a photo
    // 2. save the photo
    // 3. create a phone
    // 4. add the photo to the phone
    // 5. save the phone
    /**
     * @expectedException Illuminate\Database\QueryException
     */
    public function testMorphOneToManySaveOrder3()
    {
        $photo = new Photo;
        $photo->desc = 'photo 4';
        $photo->photoable_id= 0;
        $photo->photoable_type = '';
        $photo->save();
        $id = $photo->id;
        $photo = Photo::find($id);
        $this->assertEquals('photo 4', $photo->desc);

        $phone = new Phone();
        $phone->number = '555';
        $phone->user_id = 3;
        $phone->photos()->save($photo);
        $phone->save();
    }

    public function testMorphOneToManyUpdate()
    {
        $phone = Phone::find(1);
        $photos = $phone->photos;
        $photos[0]->desc = 'photo new';
        $photos[0]->save();

        // retrieve it again and see if everything is OK
        $photos = $phone->photos;
        $this->assertEquals('photo new', $photos[0]->desc);
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
