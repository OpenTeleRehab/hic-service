<?php

namespace App\Jobs;

use App\Enums\MfaEnforcement;
use Throwable;
use App\Models\User;
use App\Models\JobTracker;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Helpers\KeycloakHelper;
use Carbon\Carbon;
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
    public function handle(): void
    {
        JobTracker::where('job_id', $this->jobId)->update(['status' => 'running']);

        try {
            $federatedDomains = array_map(fn($d) => strtolower(trim($d)), explode(',', env('FEDERATED_DOMAINS', '')));

            $internalUsers = User::query()
                ->where(function ($query) use ($federatedDomains) {
                    foreach ($federatedDomains as $domain) {
                        $query->whereRaw('LOWER(email) NOT LIKE ?', ['%' . strtolower($domain)]);
                    }
                })
                ->whereIn('type', $this->mfaSetting->user_type)
                ->get();

            foreach ($internalUsers as $user) {
                if ($this->mfaSetting->mfa_enforcement === MfaEnforcement::DISABLED) {
                    KeycloakHelper::deleteUserCredentialByType($user->email, 'otp');
                }

                $keycloakUser = KeycloakHelper::getUserByUsername($user->email);

                if (!$keycloakUser) {
                    continue;
                }

                $existingAttributes = $keycloakUser['attributes'] ?? null;

                $payload = [
                    'mfaEnforcement' => $this->mfaSetting->mfa_enforcement,
                    'trustedDeviceMaxAge' => $this->mfaSetting->mfa_expiration_duration,
                    'skipMfaMaxAge' => $this->mfaSetting->skip_mfa_setup_duration,
                ];

                if (isset($existingAttributes['skipMfaUntil'])) {
                    $date = Carbon::parse($existingAttributes['skipMfaUntil'][0]);

                    $now = Carbon::now();

                    $futureDate = $now->copy()->addSeconds($this->mfaSetting->skip_mfa_setup_duration);

                    $isoString = $futureDate->format('Y-m-d\TH:i:s.u\Z');

                    if (!$date->isPast()) {
                        $payload['skipMfaUntil'] = $isoString;
                    }
                }

                KeycloakHelper::setUserAttributes(
                    $user->email,
                    $payload,
                );
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
