<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Spatie\Translatable\HasTranslations;

class Exercise extends Model
{
    use HasTranslations;

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_APPROVED = 'approved';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'sets',
        'reps',
        'status',
        'hash',
        'uploaded_by',
        'reviewed_by',
        'auto_translated',
        'edit_translation'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'auto_translated' => 'boolean',
    ];

    /**
     * The attributes that are translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'auto_translated'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function files()
    {
        return $this->belongsToMany(File::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function additionalFields()
    {
        return $this->hasMany(AdditionalField::class);
    }

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Set default order by title.
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('title->' . App::getLocale());
        });

        // Remove related objects.
        self::deleting(function ($exercise) {
            $exercise->files()->each(function ($file) {
                $file->delete();
            });
            $exercise->where('edit_translation', $exercise->id)->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'exercise_categories', 'exercise_id', 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uploadedBy()
    {
        return $this->belongsTo(Contributor::class, 'uploaded_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return string
     */
    public function getContributorName()
    {
        return $this->uploadedBy ? $this->uploadedBy->getFullName() : '';
    }

    /**
     * @return string
     */
    public function getContributorEmail()
    {
        return $this->uploadedBy ? $this->uploadedBy->email : '';
    }

    /**
     * @return string
     */
    public function getReviewerName()
    {
        return $this->reviewedBy ? $this->reviewedBy->getFullName() : '';
    }
}
