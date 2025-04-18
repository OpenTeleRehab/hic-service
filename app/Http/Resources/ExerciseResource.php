<?php

namespace App\Http\Resources;

use App\Models\Exercise;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ExerciseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'uploaded_date' => $this->created_at->format(config('settings.date_format')),
            'uploaded_by' => $this->getContributorName(),
            'reviewed_by' => $this->getReviewerName(),
            'editing_by' => $this->getEditorName(),
            'blocked_editing' => $this->blockedEditing(),
            'status' => $this->status,
            'files' => FileResource::collection($this->files()->orderBy('order')->get()),
            'categories' => $this->categories ? $this->categories->pluck('id') : [],
            'additional_fields' => AdditionalFieldResource::collection($this->additionalFields),
            'auto_translated' => $this->auto_translated,
            'edit_translations' => Exercise::where('edit_translation', $this->id)->get(),
            'fallback' => [
                'title' => $this->getTranslation('title', config('app.fallback_locale')),
            ],
            'slug' => $this->slug
        ];

        if (Auth::check()) {
            $data['uploaded_by_email'] = $this->getContributorEmail();
        }

        return $data;
    }
}
