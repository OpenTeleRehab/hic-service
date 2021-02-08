<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuestionnaireResource;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class QuestionnaireController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $query = Questionnaire::select();
        $filter = json_decode($request->get('filter'), true);

        if (!empty($filter['search_value'])) {
            $locale = App::getLocale();
            $query->whereRaw("JSON_EXTRACT(LOWER(title), \"$.$locale\") LIKE ?", ['%' . strtolower($filter['search_value']) . '%']);
        }
        $questionnaires = $query->paginate($request->get('page_size'));

        $info = [
            'current_page' => $questionnaires->currentPage(),
            'total_count' => $questionnaires->total(),
        ];
        return [
            'success' => true,
            'data' => QuestionnaireResource::collection($questionnaires),
            'info' => $info,
        ];
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $questionnaire = Questionnaire::create([
                'title' => $request->get('title'),
                'description' => $request->get('description'),
            ]);

            $questions = $request->get('questions');
            foreach ($questions as $question) {
                $newQuestion = Question::create([
                    'title' => $question['title'],
                    'type' => $question['type'],
                    'questionnaire_id' => $questionnaire->id,
                ]);

                if (isset($question['answers'])) {
                    foreach ($question['answers'] as $answer) {
                        Answer::create([
                            'description' => $answer['description'],
                            'question_id' => $newQuestion->id,
                        ]);
                    }
                }
            }

            DB::commit();
            return ['success' => true, 'message' => 'success_message.questionnaire_create'];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param \App\Models\Questionnaire $questionnaire
     *
     * @return \App\Http\Resources\EducationMaterialResource
     */
    public function show(Questionnaire $questionnaire)
    {
        return new QuestionnaireResource($questionnaire);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Questionnaire $questionnaire
     *
     * @return array
     */
    public function update(Request $request, Questionnaire $questionnaire)
    {
        DB::beginTransaction();
        try {
            $questionnaire->update([
                'title' => $request->get('title'),
                'description' => $request->get('description')
            ]);

            $questions = $request->get('questions');
            $questionIds = [];

            foreach ($questions as $question) {
                $questionObj = Question::updateOrCreate(
                    [
                        'id' => isset($question['id']) ? $question['id'] : null,
                    ],
                    [
                        'title' => $question['title'],
                        'type' => $question['type'],
                        'questionnaire_id' => $questionnaire->id,
                    ]
                );

                $questionIds[] = $questionObj->id;
                $answerIds = [];
                if (isset($question['answers'])) {
                    foreach ($question['answers'] as $answer) {
                        $answerObj = Answer::updateOrCreate(
                            [
                                'id' => isset($answer['id']) ? $answer['id'] : null,
                            ],
                            [
                                'description' => $answer['description'],
                                'question_id' => $questionObj->id,
                            ]
                        );

                        $answerIds[] = $answerObj->id;
                    }
                }

                // Remove deleted answers.
                Answer::where('question_id', $questionObj->id)
                    ->whereNotIn('id', $answerIds)
                    ->delete();
            }

            // Remove deleted questions.
            Question::where('questionnaire_id', $questionnaire->id)
                ->whereNotIn('id', $questionIds)
                ->delete();

            DB::commit();
            return ['success' => true, 'message' => 'success_message.questionnaire_update'];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getByIds(Request $request)
    {
        $questionnaireIds = $request->get('questionnaire_ids', []);
        $questionnaires = Questionnaire::whereIn('id', $questionnaireIds)->get();
        return QuestionnaireResource::collection($questionnaires);
    }
}
