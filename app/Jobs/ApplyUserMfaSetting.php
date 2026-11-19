<?php

namespace App\Jobs;

use App\Models\MfaSetting;
use App\Models\User;
use App\Services\MfaSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Apply the existing MFA settings to a single user (e.g. a newly created user),
 * using the same flow as UpdateFederatedUsersMfaJob.
 */
class ApplyUserMfaSetting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 30;

    protected int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(MfaSettingService $mfaSettingService): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $mfaSettings = MfaSetting::whereJsonContains('user_type', $user->type)->get();

        foreach ($mfaSettings as $mfaSetting) {
            $users = $mfaSettingService->getUsers($mfaSetting->user_type, $user->id);

            foreach ($users as $internalUser) {
                $mfaSettingService->apply($internalUser->email, $mfaSetting);
            }
        }
    }
}
