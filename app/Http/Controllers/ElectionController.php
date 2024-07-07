<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\ElectionLog;
use App\Models\Participant;
use Illuminate\Http\Request;
use App\Models\ElectionStatus;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ElectionController extends Controller
{
    public function index()
    {
        return view('elections.index', ['elections' => Election::all()]);
    }

    public function create()
    {
        return view('elections.create');
    }

    public function store(Request $request)
    {
        $election = Election::create([
            'name' => $request->name,
            'participants' => json_encode([]),
            'results' => json_encode([
                'délégué' => ['tour_1' => [], 'finish' => false],
                'suppléant' => ['tour_1' => [], 'finish' => false]
            ]),
            'status_id' => ElectionStatus::where('status', 'en attente')->first()->id,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('elections.show', $election);
    }

    public function show(Election $election)
    {
        $election->load('participants');
        $logs = ElectionLog::where('election_id', $election->id)->get();
        $results = json_decode($election->results, true);

        return view('elections.show', compact('election', 'logs', 'results'));
    }

    public function showJoinForm(Election $election)
    {
        return view('elections.join', compact('election'));
    }

    public function join(Request $request, Election $election)
    {
        $participant = Participant::create([
            'election_id' => $election->id,
            'name' => $request->name,
            'is_candidate' => $request->has('is_candidate'),
            'role' => $request->has('is_candidate') ? 'candidat' : null,
        ]);

        $participants = json_decode($election->participants, true);
        $participants[] = $participant->only(['id', 'name', 'is_candidate', 'role']);
        $election->update(['participants' => json_encode($participants)]);

        Session::put('participant_id', $participant->id);

        return redirect()->route('elections.waiting', $election);
    }

    public function waiting(Election $election)
    {
        $participant = Participant::find(Session::get('participant_id'));
        return view('elections.waiting', compact('election', 'participant'));
    }

    public function toggleCandidate(Election $election)
    {
        $participant = Participant::find(Session::get('participant_id'));

        if (!$participant || $participant->election_id != $election->id) {
            return redirect()->route('elections.waiting', $election)->with('error', 'Participant non valide.');
        }

        $participant->update([
            'is_candidate' => !$participant->is_candidate,
            'role' => $participant->is_candidate ? 'candidat' : null
        ]);

        $participants = json_decode($election->participants, true);
        foreach ($participants as &$p) {
            if ($p['id'] == $participant->id) {
                $p['is_candidate'] = $participant->is_candidate;
                $p['role'] = $participant->role;
                break;
            }
        }
        $election->update(['participants' => json_encode($participants)]);

        $this->logEvent($election->id, "Le participant {$participant->name} a changé son statut de candidature.");

        return redirect()->route('elections.waiting', $election)->with('success', 'Statut de candidature mis à jour.');
    }

    public function start(Election $election)
    {
        $candidates = $election->participants()->where('is_candidate', true)->count();

        if ($candidates < 2) {
            return redirect()->route('elections.show', $election)->with('error', 'L\'élection ne peut pas être démarrée sans au moins deux candidats.');
        }

        $status = ElectionStatus::where('status', 'en cours')->first();

        if (!$status) {
            Log::error('Le statut "en cours" est introuvable.');
            return redirect()->route('elections.show', $election)->with('error', 'Le statut "en cours" est introuvable.');
        }

        $election->update(['status_id' => $status->id]);

        $this->logEvent($election->id, 'L\'élection a commencé.');

        return redirect()->route('elections.show', $election)->with('success', 'L\'élection a commencé.');
    }

    public function vote(Election $election)
    {
        $participant_id = Session::get('participant_id');
        $participant = Participant::find($participant_id);

        if (!$participant) {
            return redirect()->route('home')->with('error', 'Participant non valide.');
        }

        $results = $this->getResults($election);
        $type = $this->getElectionType($results);

        if ($this->hasVoted($results[$type], $participant->id)) {
            return redirect()->route('elections.results', $election)->with('info', 'Vous avez déjà voté.');
        }

        return view('elections.vote', compact('election', 'type'));
    }

    public function submitVote(Request $request, Election $election)
    {
        $participant_id = Session::get('participant_id');
        $participant = Participant::find($participant_id);

        if (!$participant || $election->user_id == $participant->id) {
            return redirect()->route('elections.vote', $election)->with('error', 'Vous n\'êtes pas autorisé à voter.');
        }

        $results = $this->getResults($election);
        $type = $this->getElectionType($results);

        if ($this->hasVoted($results[$type], $participant->id)) {
            return redirect()->route('elections.results', $election)->with('info', 'Vous avez déjà voté.');
        }

        $results[$type]['tour_1'][] = ['vote' => $request->input('candidate'), 'participant_id' => $participant->id];
        $this->saveResults($election, $results);

        $this->logEvent($election->id, 'Un participant a voté.');

        if (count($results[$type]['tour_1']) >= $election->participants()->count() - 1) {
            return $this->endRound($election);
        }

        return redirect()->route('elections.results', $election)->with('success', 'Vote soumis avec succès.');
    }

    public function endRound(Election $election)
    {
        $results = $this->getResults($election);
        $type = $this->getElectionType($results);

        $voteCounts = $this->countVotes($results[$type]['tour_1']);
        $winnerId = $this->getWinnerId($voteCounts, $election);
        $winner = Participant::find($winnerId);

        if ($type == 'délégué') {
            $this->electParticipant($election, $winnerId, 'délégué');
            $this->logEvent($election->id, "Le participant {$winner->name} a été élu délégué.");
            $this->startSuppléantElection($election);
        } else {
            $this->electParticipant($election, $winnerId, 'suppléant');
            $this->logEvent($election->id, "Le participant {$winner->name} a été élu suppléant.");
        }

        return redirect()->route('elections.show', $election)->with('info', 'Le vote est terminé.');
    }

    private function getElectionType(array $results)
    {
        if (!$results['délégué']['finish']) {
            return 'délégué';
        }
        if (!$results['suppléant']['finish']) {
            return 'suppléant';
        }
        return 'terminé';
    }

    private function electParticipant(Election $election, int $winnerId, string $role)
    {
        $participant = Participant::find($winnerId);
        $participant->update(['role' => $role]);

        $participants = json_decode($election->participants, true);
        foreach ($participants as &$p) {
            if ($p['id'] == $winnerId) {
                $p['is_candidate'] = false;
                $p['role'] = $role;
                break;
            }
        }
        $election->update(['participants' => json_encode($participants)]);

        $results = $this->getResults($election);
        $results[$role]['finish'] = true;
        $this->saveResults($election, $results);
    }

    private function startSuppléantElection(Election $election)
    {
        $results = $this->getResults($election);
        $results['suppléant']['finish'] = false;
        $results['suppléant']['tour_1'] = [];

        $election->update([
            'results' => json_encode($results),
            'status_id' => ElectionStatus::where('status', 'en cours')->first()->id
        ]);

        $this->logEvent($election->id, 'Élection du suppléant commencée.');
    }

    private function hasVoted(array $results, int $participantId)
    {
        foreach ($results as $rounds) {
            if (is_array($rounds)) {
                foreach ($rounds as $votes) {
                    foreach ($votes as $vote) {
                        if (isset($vote['participant_id']) && $vote['participant_id'] == $participantId) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    private function countVotes(array $round)
    {
        $voteCounts = array_count_values(array_column($round, 'vote'));
        arsort($voteCounts);
        return $voteCounts;
    }

    private function getWinnerId(array $voteCounts, Election $election)
    {
        $winnerName = array_search(max($voteCounts), $voteCounts);
        return Participant::where('name', $winnerName)->where('election_id', $election->id)->first()->id;
    }

    private function saveResults(Election $election, array $results)
    {
        $election->update(['results' => json_encode($results)]);
    }

    private function logEvent(int $electionId, string $message)
    {
        ElectionLog::create(['election_id' => $electionId, 'message' => $message]);
    }

    public function results(Election $election)
    {
        $results = $this->getResults($election);
        return view('elections.results', compact('election', 'results'));
    }

    public function destroy(Election $election)
    {
        $election->delete();
        return redirect()->route('home')->with('success', 'Election supprimée avec succès.');
    }

    public function checkRoundStatus(Election $election)
    {
        $results = $this->getResults($election);
        $type = $this->getElectionType($results);
        return response()->json([
            'status' => $election->status->status,
            'roundIndex' => count($results[$type]['tour_1']) - 1,
            'type' => $type
        ]);
    }

    private function getResults(Election $election)
    {
        $results = json_decode($election->results, true);
        if (!$results) {
            $results = [
                'délégué' => ['tour_1' => [], 'finish' => false],
                'suppléant' => ['tour_1' => [], 'finish' => false]
            ];
        }
        return $results;
    }
}
