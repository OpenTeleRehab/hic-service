<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Http\Resources\QuestionnaireResource;
use App\Models\Answer;
use App\Models\Exercise;
use App\Models\File;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\ExerciseHelper;

class QuestionnaireController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {
        $query = ExerciseHelper::generateFilterQuery($request, with(new Questionnaire));
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

            $email = !Auth::check() ? $data->email : Auth::user()->email;
            $first_name = !Auth::check() ? $data->first_name : Auth::user()->first_name;
            $last_name = !Auth::check() ? $data->last_name : Auth::user()->last_name;
            $hash = !Auth::check() ? $data->hash : null;
            $status = !Auth::check() ? Exercise::STATUS_DRAFT : Exercise::STATUS_PENDING;
            $contributor = ExerciseHelper::updateOrCreateContributor($first_name, $last_name, $email);

            if (!empty($data->copy_id)) {
                $questionnaire = Questionnaire::findOrFail($data->copy_id)->replicate(['is_used']);

                // Append (copy) label to all title translations.
                $titleTranslations = $questionnaire->getTranslations('title');
                $appendedTitles = array_map(function ($value) {
                    // TODO: translate copy label to each language.
                    return "$value (Copy)";
                }, $titleTranslations);
                $questionnaire->setTranslations('title', $appendedTitles);
                $questionnaire->save();

                // Update form elements.
                $questionnaire->update([
                    'title' => $data->title,
                    'description' => $data->description,
                    'status' => $status,
                    'hash' => $hash,
                    'uploaded_by' => $contributor ? $contributor->id : null,
                ]);
            } else {
                $questionnaire = Questionnaire::create([
                    'title' => $data->title,
                    'description' => $data->description,
                    'status' => $status,
                    'hash' => $hash,
                    'uploaded_by' => $contributor ? $contributor->id : null,
                ]);
            }

            // Attach category to questionnaire.
            $categories = $data->categories ?: [];
            foreach ($categories as $category) {
                $questionnaire->categories()->attach($category);
            }

            $questions = $data->questions;
            foreach ($questions as $index => $question) {
                $file = null;
                if (array_key_exists($index, $files)) {
                    $file = FileHelper::createFile($files[$index], File::QUESTIONNAIRE_PATH);
                } elseif (isset($question->file) && $question->file->id) {
                    // CLone files.
                    $originalFile = File::findOrFail($question->file->id);
                    $file = FileHelper::replicateFile($originalFile);
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
                'description' => $data->description,
                'status' => Exercise::STATUS_APPROVED,
                'reviewed_by' => Auth::id()
            ]);

            // Attach category to exercise.
            $categories = $data->categories ?: [];
            QuestionnaireCategory::where('questionnaire_id', $questionnaire->id)->delete();
            foreach ($categories as $category) {
                $questionnaire->categories()->attach($category);
            }

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
     * @param \App\Models\Questionnaire $questionnaire
     *
     * @return array
     */
    public function reject(Questionnaire $questionnaire)
    {
        $questionnaire->update(['status' => Questionnaire::STATUS_REJECTED, 'reviewed_by' => Auth::id()]);

        return ['success' => true, 'message' => 'success_message.questionnaire_reject'];
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

    /**
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function markAsUsed(Request $request)
    {
        $questionnaireIds = $request->get('questionnaire_ids', []);
        Questionnaire::where('is_used', false)
            ->whereIn('id', $questionnaireIds)
            ->update(['is_used' => true]);
    }
}
