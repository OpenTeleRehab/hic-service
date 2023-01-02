<?php

namespace App\Console\Commands;

use App\Helpers\KeycloakHelper;
use App\Models\Answer;
use App\Models\Category;
use App\Models\File;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireCategory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncQuestionnaireData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hi:sync-questionnaire-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync questionnaires data from global to open library';

    /**
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle()
    {
        // Sync questionnaire data.
        $globalQuestionnaires = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-questionnaires-for-open-library'));
        $questionnaires = Questionnaire::withTrashed()->where('global', true)->get();
        // Remove data before import.
        if ($questionnaires) {
            foreach ($questionnaires as $questionnaire) {
                $questions = Question::where('questionnaire_id', $questionnaire->id)->get();
                // Remove files.
                $removeFileIDs = $questions->pluck('file_id')->toArray();
                foreach ($removeFileIDs as $removeFileID) {
                    $removeFile = File::find($removeFileID);
                    if ($removeFile) {
                        $removeFile->delete();
                    }
                }
            }
        }

        // Import global questionnaires to library.
        $this->output->progressStart(count($globalQuestionnaires));
        $globalQuestionnaireIds = [];
        $globalQuestionIds = [];
        $globalAnswerIds = [];
        foreach ($globalQuestionnaires as $globalQuestionnaire) {
            $this->output->progressAdvance();
            $globalQuestionnaireIds[] = $globalQuestionnaire->id;
            Questionnaire::updateOrCreate(
                [
                    'global_questionnaire_id' => $globalQuestionnaire->id,
                    'global' => true,
                ],
                [
                    'title' => json_encode($globalQuestionnaire->title),
                    'description' => json_encode($globalQuestionnaire->description),
                    'global_questionnaire_id' => $globalQuestionnaire->id,
                    'status' => 'approved',
                    'global' => true,
                    'auto_translated' => json_encode($globalQuestionnaire->auto_translated),
                    'slug' => Str::slug($globalQuestionnaire->title->en),
                    'deleted_at' => $globalQuestionnaire->deleted_at ? Carbon::parse($globalQuestionnaire->deleted_at) : $globalQuestionnaire->deleted_at,
                ]
            );
            $newQuestionnaire = Questionnaire::withTrashed()->where('global_questionnaire_id', $globalQuestionnaire->id)->where('global', true)->first();
            $questions = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-questionnaire-questions', ['questionnaire_id' => $globalQuestionnaire->id]));
            if (!empty($questions)) {
                foreach ($questions as $question) {
                    $globalQuestionIds[] = $question->id;
                    $file = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-question-file', ['question_id' => $question->id]));
                    $record = null;
                    if (!empty($file)) {
                        $file_url = env('GLOBAL_ADMIN_SERVICE_URL') . '/file/' . $file->id;
                        $file_path = File::QUESTIONNAIRE_PATH . '/' . $file->filename;

                        try {
                            $file_content = file_get_contents($file_url);
                            $record = File::create([
                                'filename' => $file->filename,
                                'path' => $file_path,
                                'content_type' => $file->content_type,
                            ]);

                            // Save file to storage.
                            Storage::put($file_path, $file_content);
                        } catch (\Exception $e) {
                            Log::debug($e->getMessage());
                        }
                    }
                    // Add questions.
                    Question::updateOrCreate(
                        [
                            'global_question_id' => $question->id,
                            'questionnaire_id' => $newQuestionnaire->id,
                        ],
                        [
                            'title' => json_encode($question->title),
                            'type' => $question->type,
                            'questionnaire_id' => $newQuestionnaire->id,
                            'file_id' => $record ? $record->id : null,
                            'order' => $question->order,
                            'global_question_id' => $question->id,
                         ]
                    );
                    // Add answers.
                    $newQuestion = Question::where('questionnaire_id', $newQuestionnaire->id)->where('global_question_id', $question->id)->first();
                    $answers = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-question-answers', ['question_id' => $question->id]));
                    if (!empty($answers)) {
                        foreach ($answers as $answer) {
                            $globalAnswerIds[] = $answer->id;
                            Answer::updateOrCreate(
                                [
                                    'global_answer_id' => $answer->id,
                                    'question_id' => $newQuestion->id,
                                ],
                                [
                                    'description' => json_encode($answer->description),
                                    'question_id' => $newQuestion->id,
                                    'global_answer_id' => $answer->id,
                                ]
                            );
                        }
                    }
                }
            }

            // Create/Update questionnaire categories.
            QuestionnaireCategory::where('questionnaire_id', $newQuestionnaire->id)->delete();
            $globalQuestionnaireCategories = json_decode(Http::withToken(KeycloakHelper::getGAdminKeycloakAccessToken())->get(env('GLOBAL_ADMIN_SERVICE_URL') . '/get-questionnaire-categories-for-open-library', ['id' => $globalQuestionnaire->id]));
            foreach ($globalQuestionnaireCategories as $globalQuestionnaireCategory) {
                $category = Category::where('global_category_id', $globalQuestionnaireCategory->category_id)->first();
                if ($category) {
                    QuestionnaireCategory::create([
                        'questionnaire_id' => $newQuestionnaire->id,
                        'category_id' => $category->id,
                    ]);
                }
            }
        }

        // Remove the previous global synced.
        Questionnaire::where('global_questionnaire_id', '<>', null)
            ->whereNotIn('global_questionnaire_id', $globalQuestionnaireIds)->delete();

        Question::where('global_question_id', '<>', null)
            ->whereNotIn('global_question_id', $globalQuestionIds)->delete();

        Answer::where('global_answer_id', '<>', null)
            ->whereNotIn('global_answer_id', $globalAnswerIds)->delete();

        $this->output->progressFinish();

        $this->info('Questionnaire data has been sync successfully');
    }
}
