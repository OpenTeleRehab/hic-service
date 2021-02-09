<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Questionnaire extends Model
{
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['title', 'description', 'is_used'];

    /**
     * The attributes that are translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'description'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
