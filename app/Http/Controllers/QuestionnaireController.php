<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\QuestionnaireResource;
use App\Models\Answer;
use App\Models\File;
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
            $files = $request->allFiles();
            $data = json_decode($request->get('data'));
            $questionnaire = Questionnaire::create([
                'title' => $data->title,
                'description' => $data->description,
            ]);

            $questions = $data->questions;
            foreach ($questions as $index => $question) {
                $file = null;
                if (array_key_exists($index, $files)) {
                    $file = FileHelper::createFile($files[$index], File::QUESTIONNAIRE_PATH);
                }

                $newQuestion = Question::create([
                    'title' => $question->title,
                    'type' => $question->type,
                    'questionnaire_id' => $questionnaire->id,
                    'file_id' => $file ? $file->id : null,
                    'order' => $index,
                ]);

                if (isset($question->answers)) {
                    foreach ($question->answers as $answer) {
                        Answer::create([
                            'description' => $answer->description,
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
     * @param \App\Models\Questionnaire $questionnaire
     *
     * @return array
     * @throws \Exception
     */
    public function destroy(Questionnaire $questionnaire)
    {
        if (!$questionnaire->is_used) {
            $questionnaire->delete();

            return ['success' => true, 'message' => 'success_message.questionnaire_delete'];
        }
        return ['success' => false, 'message' => 'error_message.questionnaire_delete'];
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
            $files = $request->allFiles();
            $data = json_decode($request->get('data'));
            $noChangedFiles = $request->get('no_changed_files', []);
            $questionnaire->update([
                'title' => $data->title,
                'description' => $data->description
            ]);

            $questions = $data->questions;
            $questionIds = [];

            foreach ($questions as $index => $question) {
                $questionObj = Question::updateOrCreate(
                    [
                        'id' => isset($question->id) ? $question->id : null,
                    ],
                    [
                        'title' => $question->title,
                        'type' => $question->type,
                        'questionnaire_id' => $questionnaire->id,
                        'order' => $index,
                    ]
                );

                if (!in_array($questionObj->id, $noChangedFiles)) {
                    $oldFile = File::find($questionObj->file_id);
                    if ($oldFile) {
                        $oldFile->delete();
                    }
                    if (array_key_exists($index, $files)) {
                        $file = FileHelper::createFile($files[$index], File::QUESTIONNAIRE_PATH);
                        $questionObj->update(['file_id' => $file ? $file->id : null]);
                    }
                }

                $questionIds[] = $questionObj->id;
                $answerIds = [];
                if ($question->answers) {
                    foreach ($question->answers as $answer) {
                        $answerObj = Answer::updateOrCreate(
                            [
                                'id' => isset($answer->id) ? $answer->id : null,
                            ],
                            [
                                'description' => $answer->description,
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
