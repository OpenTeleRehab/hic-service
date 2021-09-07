<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Contributor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'included_in_acknowledgment'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Set default order by status (active/inactive), last name, and first name.
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('last_name');
            $builder->orderBy('first_name');
        });
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return bool
     */
    public function isModerator()
    {
        $countModerator = User::where('email', '=', $this->email)->count();

        return $countModerator > 0;
    }
}
