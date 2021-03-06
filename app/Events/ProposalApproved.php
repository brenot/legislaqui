<?php

namespace App\Events;

use App\Data\Models\Proposal;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class ProposalChanged
 * @package App\Events
 */
class ProposalApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Proposal
     */
    public $proposal;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($proposal)
    {
        $this->proposal = $proposal;
    }
}
