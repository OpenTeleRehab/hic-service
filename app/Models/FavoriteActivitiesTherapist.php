<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteActivitiesTherapist extends Model
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
     * @var string[]
     */
    protected $fillable = [
        'activity_id',
        'therapist_id',
        'type',
        'is_favorite'
    ];
}
