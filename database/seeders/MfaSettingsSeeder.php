<?php

namespace Database\Seeders;

use App\Enums\MfaEnforcement;
use App\Enums\UserGroup;
use App\Models\MfaSetting;
use Illuminate\Database\Seeder;

class MfaSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MfaSetting::create([
            'user_type' => [UserGroup::MODERATOR],
            'mfa_enforcement' => MfaEnforcement::DISABLED,
            'mfa_expiration_duration' => 1800,
            'skip_mfa_setup_duration' => 1800,
        ]);
    }
}
