<?php

namespace App\Repositories;

use App\Enums\ProposalState;
use App\Events\ProposalReachedApprovalGoal;
use App\Data\Models\Proposal;
use App\Data\Models\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Mail;
use Session;

class ProposalsRepository
{
    private $searchColumns = ['name', 'problem', 'idea_exposition', 'response'];

    public function ofState($states)
    {
        return Proposal::ofState($states)->get();
    }

    public function all()
    {
        //return Proposal::orderBy('updated_at', 'desc')->get();
        //Verificar com o Antônio
        //
        return Proposal::whereNotNull('id')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function find($id)
    {
        return Proposal::findOrFail($id);
    }

    public function approve($id)
    {
        $proposal = $this->find($id);

        $user = Auth::user();

        $user_approvals = $user
            ->approvals()
            ->where('proposal_id', $id)
            ->get()
            ->count();

        if ($user_approvals > '0') {
            Session::flash('error_msg', 'Você já apoiou este projeto.');
        } else {
            $proposal->approvals()->save($user);
            Session::flash('flash_msg', 'Seu apoio foi incluído com sucesso.');
        }

        $total_approvals = $proposal
            ->approvals()
            ->where('proposal_id', $id)
            ->get()
            ->count();

        // Event Trigger
        // Condition: 20.000 approved this proposal + is not in_committee
        if (
            $total_approvals >= config('global.approvalGoal') &&
            $proposal->in_committee == false
        ) {
            // Set approval_goal flag
            $proposal->approval_goal = true;
            $proposal->save();

            // Fire Event
            event(new ProposalReachedApprovalGoal($proposal));
        }
    }

    public function toCommittee($id)
    {
        $proposal = $this->find($id);

        // Set in_committee flag
        $proposal->in_committee = true;
        $proposal->save();

        return $proposal;
    }

    public function setApprovalGoal($id)
    {
        $proposal = $this->find($id);

        // Set approval_goal flag
        $proposal->approval_goal = true;
        $proposal->save();

        return $proposal;
    }

    public function publish($id)
    {
        $proposal = $this->find($id);

        //Append Moderation Info only if never been Moderated before
        if (
            $proposal->approved_at == null &&
            $proposal->approved_by == null &&
            $proposal->disapproved_at == null &&
            $proposal->disapproved_by == null
        ) {
            $proposal->approved_at = Carbon::now();
            $proposal->approved_by = Auth::user()->id;
            //Save
            $proposal->save();
        }

        return $proposal;
    }

    public function notResponded()
    {
        return Proposal::whereNull('approved_by')
            ->whereNull('disapproved_by')
            ->whereNull('response')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function approved()
    {
        return Proposal::whereNotNull('approved_by')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function disapproved()
    {
        return Proposal::whereNotNull('disapproved_by')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function expired()
    {
        return Proposal::where('time_limit', true)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function approvalGoal()
    {
        return Proposal::where('approval_goal', true)
            ->where('in_committee', false)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function inCommittee()
    {
        return Proposal::where(function ($query) {
            $query
                ->where('approved_by', '<>', null)
                ->orwhere('disapproved_by', '<>', null);
        })
            ->where('in_committee', true)
            ->whereNull('approved_by_committee')
            ->whereNull('disapproved_by_committee')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function approvedByCommittee()
    {
        return Proposal::whereNotNull('approved_by_committee')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function disapprovedByCommittee()
    {
        return Proposal::whereNotNull('disapproved_by_committee')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function timeLimit()
    {
        // Get approveds Proposals
        $proposals_approveds = $this->approved();

        // Get today - 2015-12-19 00:00:00
        $today = Carbon::today();

        foreach ($proposals_approveds as $proposal_approved) {
            //If expired
            if (
                $proposal_approved->approved_at
                    ->addDays(config('global.timeLimit'))
                    ->diffInDays($today) < 0
            ) {
                $proposal_approved->time_limit = true;

                $proposal_approved->save();
            }
        }
    }

    public function storeBillProject()
    {
        $user = new BillProject();

        $uuid = Uuid::uuid4();

        $user->name = Input::get('name');
        $user->email = Input::get('email');
        $user->password = Hash::make(Input::get('password'));

        $user->uf = Input::get('uf');
        $user->role_id = Input::get('role_id');
        $user->cpf = Input::get('cpf');

        $user->uuid = $uuid;

        $user->save();
    }

    /**
     * Get all of the proposals for a given user.
     *
     * @param User $user
     *
     * @return Collection
     */
    public function forUser($user_id)
    {
        return Proposal::where('user_id', $user_id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get all of the responses proposals for a given user.
     *
     * @param User $user
     *
     * @return Collection
     */
    public function getResponsesForUser($user_id)
    {
        return Proposal::where('responder_id', $user_id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function sendProposalApprovalGoalNotification($proposal)
    {
        //dd($proposal);

        Mail::send(
            'emails.proposal-goal-notification',
            ['proposal' => $proposal],
            function ($message) use ($proposal) {
                $message->from(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->to($proposal->user->email, $proposal->user->name);
                $message->bcc(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->subject(
                    'e-democracia: Notificação - Sua Proposta atingiu o número necessário de Apoios'
                );
            }
        );
    }

    public function sendProposalApprovedByCommittee($proposal)
    {
        //dd($proposal);

        Mail::send(
            'emails.proposal-approval-by-committee',
            ['proposal' => $proposal],
            function ($message) use ($proposal) {
                $message->from(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->to($proposal->user->email, $proposal->user->name);
                $message->bcc(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->subject(
                    'e-democracia: Notificação - Proposta aprovada pelo Comitê'
                );
            }
        );
    }

    public function sendProposalClosedByCommittee($proposal)
    {
        //dd($proposal);

        Mail::send(
            'emails.proposal-closed-by-committee',
            ['proposal' => $proposal],
            function ($message) use ($proposal) {
                $message->from(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->to($proposal->user->email, $proposal->user->name);
                $message->bcc(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->subject(
                    'e-democracia: Notificação - Proposta encerrada pelo Comitê'
                );
            }
        );
    }

    public function sendProposalTimeLimit($proposal)
    {
        //dd($proposal);

        Mail::send(
            'emails.proposal-time-limit',
            ['proposal' => $proposal],
            function ($message) use ($proposal) {
                $message->from(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->to($proposal->user->email, $proposal->user->name);
                $message->bcc(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->subject(
                    'e-democracia: Notificação - Proposta aprovada pelo Comitê'
                );
            }
        );
    }

    public function sendProposalClosed($proposal)
    {
        //dd($proposal);

        Mail::send(
            'emails.proposal-closed',
            ['proposal' => $proposal],
            function ($message) use ($proposal) {
                $message->from(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->to($proposal->user->email, $proposal->user->name);
                $message->bcc(
                    config('mail.from.address'),
                    config('mail.from.name')
                );

                $message->subject(
                    'e-democracia: Notificação - Proposta encerrada'
                );
            }
        );
    }

    /**
     * @return mixed
     */
    public function filterProposals($q, $s)
    {
        if (empty($q)) {
            $q = 'All';
        }

        $query = Proposal::query();

        $query->ofState(ProposalState::getInstances()[$q]->value);

        $this->buildSearch($query, $s);
        $query->orderBy('created_at', 'desc');

        return $query;
    }

    public function buildSearch($sqlQuery, $search)
    {
        $sqlQuery->where(function ($sqlQuery) use ($search) {
            foreach ($this->searchColumns as $searchColumn) {
                $sqlQuery->orWhere(
                    DB::raw("lower({$searchColumn})"),
                    'LIKE',
                    '%' . strtolower($search) . '%'
                );
            }
        });
    }
}
