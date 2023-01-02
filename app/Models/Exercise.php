<?php

namespace App\Models;

use App\Events\ApplyExerciseAutoTranslationEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;
use Cviebrock\EloquentSluggable\Sluggable;

class Exercise extends Model
{
    use HasTranslations;
    use Sluggable;
    use SoftDeletes;

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
        'created_at',
        'uploaded_by',
        'reviewed_by',
        'auto_translated',
        'editing_by',
        'editing_at',
        'edit_translation',
        'slug',
        'global',
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
    public $translatable = ['title', 'auto_translated'];

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

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

            if ($exercise->status === Exercise::STATUS_APPROVED) {
                $exercise_title = DB::table('exercises')->where('id', $exercise->id)->pluck('title');
                $titles = explode(':', $exercise_title)[0];
                $locale = preg_replace('/[^\p{L}\p{N}\s]/u', '', $titles);

                $resource = Exercise::find($exercise->edit_translation);

                // Update auto translated status.
                $resource->update([
                    'auto_translated' => [
                        $locale => true
                    ],
                ]);

                // Add automatic translation for Exercise.
                event(new ApplyExerciseAutoTranslationEvent($resource));
            }
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
