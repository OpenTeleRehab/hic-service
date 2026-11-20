<?php

namespace App\Jobs;

use Throwable;
use App\Models\JobTracker;
use App\Services\MfaSettingService;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateFederatedUsersMfaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $mfaSetting;
    public $uniqueId;
    public $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($mfaSetting)
    {
        $this->mfaSetting = $mfaSetting;
        $this->uniqueId = 'update-mfa-' . $mfaSetting->id;
        $this->jobId = Str::uuid()->toString();

        JobTracker::createForTrackable($this->jobId, $mfaSetting, 'queued', null);
    }

    /**
     * Execute the job.
     */
    public function handle(MfaSettingService $mfaSettingService): void
    {
        JobTracker::where('job_id', $this->jobId)->update(['status' => 'running']);

        try {
            $internalUsers = $mfaSettingService->getUsers($this->mfaSetting->user_type);

            foreach ($internalUsers as $user) {
                $mfaSettingService->apply($user->email, $this->mfaSetting);
            }

            JobTracker::where('job_id', $this->jobId)->update(['status' => 'completed']);
        } catch (Throwable $e) {
            JobTracker::where('job_id', $this->jobId)->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
