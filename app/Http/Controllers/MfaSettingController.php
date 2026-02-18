<?php

namespace App\Http\Controllers;

use App\Enums\MfaEnforcement;
use App\Http\Resources\MfaSettingResource;
use App\Jobs\UpdateFederatedUsersMfaJob;
use App\Models\MfaSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class MfaSettingController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->all();
        $query = MfaSetting::query();
        if (isset($data['search_value'])) {
            $query->where(function ($query) use ($data) {
                $query->where('user_type', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('mfa_enforcement', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('mfa_expiration_duration', 'like', '%' . $data['search_value'] . '%')
                    ->orWhere('skip_mfa_setup_duration', 'like', '%' . $data['search_value'] . '%');
            });
        }

        if (isset($data['filters'])) {
            $filters = $request->get('filters');
            $query->where(function ($query) use ($filters) {
                foreach ($filters as $filter) {
                    $filterObj = json_decode($filter);
                    if ($filterObj->columnName === 'user_type') {
                        $query->whereJsonContains('user_type', $filterObj->value);
                    } elseif ($filterObj->columnName === 'mfa_enforcement') {
                        $query->where('mfa_enforcement', $filterObj->value);
                    } else {
                        $query->where($filterObj->columnName, 'like', '%' . $filterObj->value . '%');
                    }
                }
            });
        }

        $mfaSettings = $query->paginate($data['page_size']);
        $info = [
            'current_page' => $mfaSettings->currentPage(),
            'total_count' => $mfaSettings->total(),
        ];
        return ['success' => true, 'data' => MfaSettingResource::collection($mfaSettings), 'info' => $info];
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'user_type' => 'required|array',
            'user_type.*' => 'string',
            'mfa_enforcement' => ['required', new Enum(MfaEnforcement::class)],
            'mfa_expiration_duration' => 'nullable|integer',
            'skip_mfa_setup_duration' => 'nullable|integer',
        ]);

        try {
            $mfaSetting = MfaSetting::findOrFail($id);

            if ($validatedData['mfa_enforcement'] === MfaEnforcement::DISABLED->value) {
                $validatedData['mfa_expiration_duration'] = null;
                $validatedData['skip_mfa_setup_duration'] = null;
            } else if ($validatedData['mfa_enforcement'] === MfaEnforcement::ENFORCE->value) {
                $validatedData['skip_mfa_setup_duration'] = null;
            }

            $mfaSetting->update($validatedData);

            $job = new UpdateFederatedUsersMfaJob($mfaSetting);

            dispatch($job);

            return [
                'success' => true,
                'data' => $job->jobId,
                'message' => 'success_message.mfa.update',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
