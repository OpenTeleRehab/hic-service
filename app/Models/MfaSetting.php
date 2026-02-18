<?php

namespace App\Models;

use App\Enums\MfaEnforcement;
use Illuminate\Database\Eloquent\Model;

class MfaSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_type', 'mfa_enforcement', 'mfa_expiration_duration', 'skip_mfa_setup_duration'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_type' => 'array',
        'mfa_enforcement' => MfaEnforcement::class,
        'mfa_expiration_duration' => 'integer',
        'skip_mfa_setup_duration' => 'integer',
    ];

    /**
     * Get all job statuses associated with this model.
     *
     * This defines a polymorphic one-to-many relationship, allowing
     * the model (e.g., MfaSetting) to have multiple JobStatus records.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function jobTrackers()
    {
        return $this->morphMany(JobTracker::class, 'trackable');
    }
}
