<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use App\User;
use App\Phone;
use App\Photo;
use App\Post;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call(UserTableSeeder::class);
        $user1 = new User;
        $user1->name = "Sheryl";
        $user1->save();

        $user2 = new User;
        $user2->name = "Sherry";
        $user2->save();

        $user3 = new User;
        $user3->name = "Hilary";
        $user3->save();

        $phone1 = new Phone;
        $phone1->number = '434524';
        $phone1->user_id = 1;
        $phone1->save();

        $phone2 = new Phone;
        $phone2->number = '434524';
        $phone2->user_id = 2;
        $phone2->save();

        $phone3 = new Phone;
        $phone3->number = '434524';
        $phone3->user_id = 'NULL';
        $phone3->save();

        $post1 = new Post;
        $post1->content = 'post 1';
        $post1->user_id = 1;
        $post1->save();

        $post2 = new Post;
        $post2->content = 'post 2';
        $post2->user_id = 1;
        $post2->save();

        $post3 = new Post;
        $post3->content = 'post 3';
        $post3->user_id = 2;
        $post3->save();

        $photo = new Photo;
        $photo->desc = 'photo 1';
        $user1->photo()->save($photo);

        $photo = new Photo;
        $photo->desc = 'photo 2';
        $phone1->photos()->save($photo);

        $photo = new Photo;
        $photo->desc = 'photo 3';
        $phone1->photos()->save($photo);

        Model::reguard();
    }
}
