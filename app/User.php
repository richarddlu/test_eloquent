<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function phone()
    {
        return $this->hasOne('App\Phone');
    }

    public function photo()
    {
        return $this->morphOne('App\Photo', 'photoable');
    }

    public function posts()
    {
        // by convention, the foreign key is snake case name of the owning model and suffix it with _id
        // local key is id
        return $this->hasMany('App\Post');
    }
}
