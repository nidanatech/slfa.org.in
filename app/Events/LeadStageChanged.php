<?php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contact;
    public $oldStageId;
    public $newStageId;

    public function __construct(Contact $contact, $oldStageId, $newStageId)
    {
        $this->contact = $contact;
        $this->oldStageId = $oldStageId;
        $this->newStageId = $newStageId;
    }
}
