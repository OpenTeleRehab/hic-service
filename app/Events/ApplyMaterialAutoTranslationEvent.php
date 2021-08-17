<?php

namespace App\Events;

use App\Models\EducationMaterial;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplyMaterialAutoTranslationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \App\Models\EducationMaterial
     */
    public $educationMaterial;

    /**
     * @param \App\Models\EducationMaterial $educationMaterial
     *
     * @return void
     */
    public function __construct(EducationMaterial $educationMaterial)
    {
        $this->educationMaterial = $educationMaterial;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
