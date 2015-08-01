<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $fillable = ['content'];

    public function user()
    {
        // by convention, the local key is the name of the relationship method and suffixing the method name with _id
        // foreign key is id
        return $this->belongsTo('App\User');
    }

    public function scopeFindID3($query)
    {
        return $query->where('id', '=', 3);
    }

    public function scopeFindIDAndContent($query, $id, $content)
    {
        return $query->where('id', '=', $id)->where('content', '=', $content);
    }
}
