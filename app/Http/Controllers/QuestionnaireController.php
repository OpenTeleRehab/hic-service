<?php

namespace App\Http\Controllers;

use App\Events\ApplyExerciseAutoTranslationEvent;
use App\Events\ApplyQuestionnaireAutoTranslationEvent;
use App\Helpers\FileHelper;
use App\Http\Resources\QuestionnaireResource;
use App\Models\Answer;
use App\Models\Exercise;
use App\Models\File;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
            $email = !Auth::check() ? $request->get('email') : Auth::user()->email;
            $first_name = !Auth::check() ? $request->get('first_name') : Auth::user()->first_name;
            $last_name = !Auth::check() ? $request->get('last_name') : Auth::user()->last_name;
            $edit_translation = !Auth::check() ? json_decode($request->get('edit_translation')) : false;
            $hash = !Auth::check() ? $request->get('hash') : null;
            $included_in_acknowledgment = !Auth::check() ? $request->boolean('included_in_acknowledgment') : 1;
            $status = !Auth::check() ? Exercise::STATUS_DRAFT : Exercise::STATUS_PENDING;

            $contributor = ExerciseHelper::updateOrCreateContributor($first_name, $last_name, $email, $included_in_acknowledgment);
            $questionnaire = Questionnaire::create([
                'title' => $data->title,
                'description' => $data->description,
                'status' => $status,
                'hash' => $hash,
                'uploaded_by' => $contributor ? $contributor->id : null,
                'edit_translation' => $edit_translation ? $request->get('id') : null
            ]);

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
        if (Auth::check()) {
            $currentDataTime = Carbon::now();
            if (!$questionnaire->editing_by || $currentDataTime->gt($questionnaire->editing_at->addMinutes(3))) {
                $questionnaire->update(['editing_by' => Auth::id(), 'editing_at' => $currentDataTime]);
            }
        }
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
        if ($questionnaire->blockedEditing()) {
            return ['success' => false, 'message' => 'error_message.questionnaire_update'];
        }

        DB::beginTransaction();
        try {
            $files = $request->allFiles();
            $data = json_decode($request->get('data'));
            $noChangedFiles = $request->get('no_changed_files', []);
            $questionnaire->update([
                'title' => $data->title,
                'description' => $data->description,
                'status' => Exercise::STATUS_APPROVED,
                'reviewed_by' => Auth::id(),
                'editing_by' => null,
                'editing_at' => null,
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

            // Add automatic translation for Exercise.
            event(new ApplyQuestionnaireAutoTranslationEvent($questionnaire));

            DB::commit();
            return ['success' => true, 'message' => 'success_message.questionnaire_update'];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Questionnaire $questionnaire
     *
     * @return array
     */
    public function approveEditTranslation(Request $request, Questionnaire $questionnaire)
    {
        $data = json_decode($request->get('data'));

        $questionnaire->update([
            'title' => $data->title,
            'description' => $data->description,
            'auto_translated' => false
        ]);

        $questions = $data->questions;

        foreach ($questions as $index => $question) {
            $questionObj = Question::updateOrCreate(
                [
                    'id' => $questionnaire->questions[$index]->id
                ],
                [
                    'title' => $question->title,
                    'type' => $question->type,
                    'questionnaire_id' => $questionnaire->id,
                    'order' => $index,
                ]
            );

            if ($question->answers) {
                foreach ($question->answers as $answerIndex => $answer) {
                    Answer::updateOrCreate(
                        [
                            'id' => $questionnaire->questions[$index]->answers[$answerIndex]->id
                        ],
                        [
                            'description' => $answer->description,
                            'question_id' => $questionObj->id,
                        ]
                    );
                }
            }
        }

        // Update submitted translation status
        Questionnaire::find($data->id)->update([
            'status' => Questionnaire::STATUS_APPROVED,
            'title' => $questionnaire->title
        ]);

        // Remove submitted translation remaining
        Questionnaire::whereNotNull('title->' . App::getLocale())
            ->where('edit_translation', $questionnaire->id)
            ->whereNotIn('id', [$data->id])
            ->delete();

        return ['success' => true, 'message' => 'success_message.questionnaire_update'];
    }

    /**
     * @param \App\Models\Exercise $questionnaire
     *
     * @return array
     */
    public function cancelEditing(Questionnaire $questionnaire)
    {
        if ($questionnaire->editing_by === Auth::id()) {
            $questionnaire->update(['editing_by' => null, 'editing_at' => null]);
            return ['success' => true, 'message' => 'success_message.questionnaire_cancel_editing'];
        }
        return ['success' => false, 'message' => 'error_message.questionnaire_cancel_editing'];
    }

    /**
     * @param \App\Models\Exercise $questionnaire
     *
     * @return array
     */
    public function continueEditing(Questionnaire $questionnaire)
    {
        if ($questionnaire->editing_by === Auth::id()) {
            $questionnaire->update(['editing_at' => Carbon::now()]);
            return ['success' => true, 'message' => 'success_message.questionnaire_continue_editing'];
        }
        return ['success' => false, 'message' => 'error_message.questionnaire_continue_editing'];
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
     *
     * @return \App\Http\Resources\QuestionnaireResource
     */
    public function getBySlug(Request $request)
    {
        $slug = $request->get('slug');
        $questionnaire = Questionnaire::where('slug', $slug)->whereNull('edit_translation')->first();
        return new QuestionnaireResource($questionnaire);
    }
}
