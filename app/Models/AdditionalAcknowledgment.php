<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalAcknowledgment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'hide_contributors'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var boolean
     */
    public $timestamps = false;
}
