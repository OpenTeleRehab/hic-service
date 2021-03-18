<?php

namespace App\Helpers;

use App\Models\FavoriteActivitiesTherapist;

/**
 * @package App\Helpers
 */
class FavoriteActivityHelper
{
    /**
     * @param boolean $favorite
     * @param integer $therapistId
     * @param \App\Models\Exercise|\App\Models\EducationMaterial|\App\Models\Questionnaire $activity
     *
     * @return void
     */
    public static function flagFavoriteActivity($favorite, $therapistId, $activity)
    {
        FavoriteActivitiesTherapist::updateOrCreate(
            [
                'type' => $activity->getTable(),
                'activity_id' => $activity->id,
                'therapist_id' => $therapistId,
            ],
            [
                'is_favorite' => $favorite,
            ]
        );
    }

    /**
     * @param mixed $activity
     * @param integer $therapistId
     * @return int
     */
    public static function getFavoriteActivity($activity, $therapistId)
    {
        $favorite = FavoriteActivitiesTherapist::where('is_favorite', true)
            ->where('therapist_id', $therapistId)
            ->where('activity_id', $activity->id)
            ->where('type', $activity->getTable())
            ->count();

        return $favorite;
    }
}
