<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationMaterialCategory extends Model
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
        'education_material_id',
        'category_id',
    ];
}
