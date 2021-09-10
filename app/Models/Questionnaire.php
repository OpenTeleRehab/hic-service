<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Builder;

class Questionnaire extends Model
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
        'description',
        'status',
        'hash',
        'uploaded_by',
        'reviewed_by',
        'editing_by',
        'editing_at',
        'edit_translation',
        'slug'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'auto_translated' => 'boolean',
        'editing_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'description', 'auto_translated'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // Set default ordering.
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('title->' . App::getLocale());
        });

        // Remove related objects.
        self::deleting(function ($questionnaire) {
            $questionnaire->questions()->each(function ($question) {
                $question->delete();
            });
            $questionnaire->where('edit_translation', $questionnaire->id)->delete();
        });

        //creat slug
        static::creating(function ($questionnaire) {
            // produce a slug based on the activity title
            $slug = Str::slug($questionnaire->title);

            // check to see if any other slugs exist that are the same & count them
            $count = static::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

            // if other slugs exist that are the same, append the count to the slug
            $questionnaire->slug = $count ? "{$slug}-{$count}" : $slug;
        });

        //update slug
        static::updating(function ($questionnaire) {
            // produce a slug based on the activity title
            $slug = Str::slug($questionnaire->title);

            // check to see if any other slugs exist that are the same & count them
            $count = static::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->whereNotIn('id', [$questionnaire->id])->count();

            // if other slugs exist that are the same, append the count to the slug
            if (App::getLocale() === 'en') {
                $questionnaire->slug = $count ? "{$slug}-{$count}" : $slug;
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'questionnaire_categories', 'questionnaire_id', 'category_id');
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function editingBy()
    {
        return $this->belongsTo(User::class, 'editing_by');
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

    /**
     * @return string
     */
    public function getEditorName()
    {
        return $this->editingBy ? $this->editingBy->getFullName() : '';
    }

    /**
     * @return boolean
     */
    public function blockedEditing()
    {
        return $this->editing_by && $this->editing_by !== Auth::id();
    }
}
