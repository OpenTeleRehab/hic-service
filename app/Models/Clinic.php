<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
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
        'name', 'country_id', 'region', 'province', 'city'
    ];
}
