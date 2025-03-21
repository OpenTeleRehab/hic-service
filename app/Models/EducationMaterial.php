<?php

namespace App\Models;

use App\Events\ApplyMaterialAutoTranslationEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Cviebrock\EloquentSluggable\Sluggable;

class EducationMaterial extends Model
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
        'file_id',
        'status',
        'hash',
        'created_at',
        'uploaded_by',
        'reviewed_by',
        'auto_translated',
        'editing_by',
        'editing_at',
        'edit_translation',
        'global',
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
    public $translatable = ['title', 'file_id', 'auto_translated'];

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

        // Set default order by title.
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('title->' . App::getLocale());
        });

        // Remove related objects.
        self::deleting(function ($educationMaterial) {
            $educationMaterial->where('edit_translation', $educationMaterial->id)->delete();

            if ($educationMaterial->status === EducationMaterial::STATUS_APPROVED) {
                $education_title = DB::table('education_materials')->where('id', $educationMaterial->id)->pluck('title');
                $titles = explode(':', $education_title)[0];
                $locale = preg_replace('/[^\p{L}\p{N}\s]/u', '', $titles);

                $resource = EducationMaterial::find($educationMaterial->edit_translation);

                // Update auto translated status.
                $resource->update([
                    'auto_translated' => [
                        $locale => true
                    ],
                ]);

                // Add automatic translation for Exercise.
                event(new ApplyMaterialAutoTranslationEvent($resource));
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'education_material_categories', 'education_material_id', 'category_id');
    }

    /**
     * @return mixed|string
     */
    public function getFileIdNoFallbackAttribute()
    {
        return $this->getTranslation('file_id', App::getLocale(), false);
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
