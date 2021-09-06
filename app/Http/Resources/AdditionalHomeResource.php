<?php

namespace App\Http\Resources;

use App\Models\EducationMaterial;
use App\Models\Exercise;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdditionalHomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'display_quick_stat' =>  $this->display_quick_stat,
            'display_feature_resource' =>  $this->display_feature_resource,
            'resources' => json_decode($this->resource),
            'featured_resources' => $this->getFeaturedResources(json_decode($this->resource['resources']))
        ];
    }

    protected function getFeaturedResources($resources)
    {
        $data = [];
        foreach ($resources as $resource) {
            $featuredResources = [];
            foreach ($resource as $res) {
                $resArr = (array)$res;
                $obj = explode('-', $resArr['id']);
                $model = $obj[0];
                $id = $obj[1];

                $featuredResources[$model][] = $id;
            }

            if (array_key_exists('exercises', $featuredResources)) {
                $exercises = Exercise::whereIn('id', $featuredResources['exercises'])->get();
                $data['exercises'] = ExerciseResource::collection($exercises);
            }

            if (array_key_exists('questionnaires', $featuredResources)) {
                $questionnaires = Questionnaire::whereIn('id', $featuredResources['questionnaires'])->get();
                $data['questionnaires'] = QuestionnaireResource::collection($questionnaires);
            }

            if (array_key_exists('education_materials', $featuredResources)) {
                $education_materials = EducationMaterial::whereIn('id', $featuredResources['education_materials'])->get();
                $data['education_materials'] = EducationMaterialResource::collection($education_materials);
            }
        }

        return $data;
    }
}
