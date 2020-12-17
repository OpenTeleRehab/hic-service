<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{

    /**
     * Indicates if the model should be timestamped.
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'value', 'platform'
    ];
}
