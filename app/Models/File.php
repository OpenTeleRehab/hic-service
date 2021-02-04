<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    const EXERCISE_PATH = 'exercise';
    const EDUCATION_MATERIAL_PATH = 'education_material';
    const EXERCISE_THUMBNAIL_PATH = self::EXERCISE_PATH . '/thumbnail';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['filename', 'path', 'content_type', 'metadata', 'thumbnail'];
}
