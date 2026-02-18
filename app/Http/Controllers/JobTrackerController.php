<?php

namespace App\Http\Controllers;

use App\Models\JobTracker;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobTrackerController extends Controller
{
    public function show($jobId)
    {
        return new StreamedResponse(function () use ($jobId) {
            while (true) {
                $status = JobTracker::where('job_id', $jobId)->first();

                $data = $status
                    ? ['status' => $status->status, 'message' => $status->message]
                    : ['status' => 'not_found'];

                echo "data: " . json_encode($data) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                if (in_array($data['status'], ['completed', 'failed', 'not_found'])) {
                    break;
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
