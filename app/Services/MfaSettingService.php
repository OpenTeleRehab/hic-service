<?php

namespace App\Services;

use App\Helpers\KeycloakHelper;
use App\Models\MfaSetting;
use App\Models\User;
use Carbon\Carbon;

class MfaSettingService
{
    /**
     * Fetch internal (non-federated) users of the given types to apply MFA setting
     */
    public function getUsers(array $userTypes, ?int $userId = null)
    {
        $federatedDomains = array_map(fn($d) => strtolower(trim($d)), explode(',', env('FEDERATED_DOMAINS', '')));

        return User::query()
            ->when($userId, fn($query) => $query->where('id', $userId))
            ->where(function ($query) use ($federatedDomains) {
                foreach ($federatedDomains as $domain) {
                    $query->whereRaw('LOWER(email) NOT LIKE ?', ['%' . strtolower($domain)]);
                }
            })
            ->whereIn('type', $userTypes)
            ->get();
    }

    /**
     * Apply the MFA setting to the Keycloak user
     */
    public function apply(string $email, MfaSetting $mfaSetting): bool
    {
        $keycloakUser = KeycloakHelper::getUserByUsername($email);

        if (!$keycloakUser) {
            return false;
        }

        $existingAttributes = $keycloakUser['attributes'] ?? null;

        $payload = [
            'mfaEnforcement' => $mfaSetting->mfa_enforcement,
            'trustedDeviceMaxAge' => $mfaSetting->mfa_expiration_duration,
            'skipMfaMaxAge' => $mfaSetting->skip_mfa_setup_duration,
        ];

        if (isset($existingAttributes['skipMfaUntil'])) {
            $date = Carbon::parse($existingAttributes['skipMfaUntil'][0]);

            $now = Carbon::now();

            $futureDate = $now->copy()->addSeconds($mfaSetting->skip_mfa_setup_duration);

            $isoString = $futureDate->format('Y-m-d\TH:i:s.u\Z');

            if (!$date->isPast()) {
                $payload['skipMfaUntil'] = $isoString;
            }
        }

        KeycloakHelper::setUserAttributes(
            $email,
            $payload,
        );

        return true;
    }
}
