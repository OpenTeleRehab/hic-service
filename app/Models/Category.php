<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations, HasFactory;
    use SoftDeletes;

    const TYPE_EXERCISE = 'exercise';
    const TYPE_EDUCATION_MATERIAL = 'education';
    const TTYPE_QUESTIONNAIRE = 'questionnaire';

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
    protected $fillable = ['title', 'type', 'parent_id'];

    /**
     * The attributes that are translatable
     *
     * @var string[]
     */
    public $translatable = ['title'];

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
            $builder->orderBy('title');
        });

        // Remove children objects.
        self::deleting(function ($category) {
            $category->children()->each(function ($subCat) {
                $subCat->delete();
            });
        });
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function exercises()
    {
        return $this->belongsToMany(Exercise::class, 'exercise_categories');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function educationMaterials()
    {
        return $this->belongsToMany(EducationMaterial::class, 'education_material_categories');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function questionnaires()
    {
        return $this->belongsToMany(Questionnaire::class, 'questionnaire_categories');
    }

    /**
     * @return bool
     */
    public function isUsed()
    {
        $isUsed = false;
        if ($this->type === self::TYPE_EXERCISE) {
            $isUsed = $this->exercises->count() > 0;
        }

        if ($this->type === self::TYPE_EDUCATION_MATERIAL) {
            $isUsed = $this->educationMaterials->count() > 0;
        }

        if ($this->type === self::TTYPE_QUESTIONNAIRE) {
            $isUsed = $this->questionnaires->count() > 0;
        }

        if (!$isUsed && $this->children->count()) {
            foreach ($this->children as $subCat) {
                if ($subCat->isUsed()) {
                    return true;
                }
            }
        }
        return $isUsed;
    }
}
