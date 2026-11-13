<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class QuestionnaireListResource extends JsonResource
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
            'fallback' => [
                'title' => $this->getTranslation('title', config('app.fallback_locale')),
            ],
            'questions' => QuestionListResource::collection($this->questions),
            'slug' => $this->slug,
            'status' => $this->status
        ];

        if (Auth::check()) {
            $data['uploaded_by_email'] = $this->getContributorEmail();
            $data['uploaded_by'] = $this->getContributorName();
            $data['reviewed_by'] = $this->getReviewerName();
            $data['editing_by'] = $this->getEditorName();
        }

        return $data;
    }
}
