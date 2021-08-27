<?php

namespace App\Models;

use App\Http\Resources\AdditionalHomeResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    use HasTranslations;

    const PAGE_TYPE_HOMEPAGE = 'homepage';
    const PAGE_TYPE_ABOUT_US = 'about-us';
    const PAGE_TYPE_ACKNOWLEDGMENT = 'acknowledgment';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['title', 'content', 'url_path_segment', 'file_id', 'partner_content', 'additional_home_id'];

    /**
     * The attributes that are translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'content', 'partner_content'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('title->' . App::getLocale());
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function additionalHome()
    {
        return $this->belongsTo(AdditionalHome::class, 'additional_home_id');
    }
}
