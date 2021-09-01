<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TermAndCondition extends Model
{
    use HasTranslations;

    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['version', 'content', 'status', 'published_date', 'title'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'published_date' => 'datetime:d-m-Y',
    ];

    /**
     * The attributes that are translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'content'];

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('published_date', 'desc');
        });
    }
}
