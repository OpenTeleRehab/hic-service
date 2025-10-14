<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MfaSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_type' => $this->user_type,
            'mfa_enforcement' => $this->mfa_enforcement,
            'mfa_expiration_duration' => $this->mfa_expiration_duration,
            'skip_mfa_setup_duration' => $this->skip_mfa_setup_duration,
            'job_status' => $this->jobTrackers
                ->sortByDesc('created_at')
                ->first(),
        ];
    }
}
