<?php

namespace App\Http\Resources;

use App\Models\Questionnaire;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class QuestionnaireResource extends JsonResource
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
            'description' => $this->description,
            'questions' => QuestionResource::collection($this->questions),
            'categories' => $this->categories ? $this->categories->pluck('id') : [],
            'auto_translated' => $this->auto_translated,
            'uploaded_date' => $this->created_at->format(config('settings.date_format')),
            'uploaded_by' => $this->getContributorName(),
            'editing_by' => $this->getEditorName(),
            'blocked_editing' => $this->blockedEditing(),
            'reviewed_by' => $this->getReviewerName(),
            'status' => $this->status,
            'edit_translations' => Questionnaire::where('edit_translation', $this->id)->get(),
            'fallback' => [
                'title' => $this->getTranslation('title', config('app.fallback_locale')),
                'description' => $this->getTranslation('description', config('app.fallback_locale')),
            ],
            'slug' => $this->slug
        ];

        if (Auth::check()) {
            $data['uploaded_by_email'] = $this->getContributorEmail();
        }

        return $data;
    }
}
