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
        $elections = Election::all();
        return view('elections.index', compact('elections'));
    }

    public function create()
    {
        return view('elections.create');
    }

    public function store(Request $request)
    {
        $election = new Election();
        $election->name = $request->name;
        $election->participants = json_encode([]);
        $election->results = json_encode(['rounds' => [[]]]);
        $election->status_id = ElectionStatus::where('status', 'en attente')->first()->id;
        $election->user_id = Auth::id();
        $election->save();

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
        $participants[] = [
            'id' => $participant->id,
            'name' => $participant->name,
            'is_candidate' => $participant->is_candidate,
            'role' => $participant->role
        ];
        $election->participants = json_encode($participants);
        $election->save();

        Session::put('participant_id', $participant->id);

        return redirect()->route('elections.waiting', $election);
    }

    public function waiting(Election $election)
    {
        $participant_id = Session::get('participant_id');
        $participant = Participant::find($participant_id);

        return view('elections.waiting', compact('election', 'participant'));
    }

    public function toggleCandidate(Request $request, Election $election)
    {
        $participant_id = Session::get('participant_id');
        $participant = Participant::find($participant_id);

        if (!$participant || $participant->election_id != $election->id) {
            return redirect()->route('elections.waiting', $election)->with('error', 'Participant non valide.');
        }

        $participant->is_candidate = !$participant->is_candidate;
        $participant->role = $participant->is_candidate ? 'candidat' : null;
        $participant->save();

        // Mise à jour des participants dans l'élection
        $participants = json_decode($election->participants, true);
        foreach ($participants as &$p) {
            if ($p['id'] == $participant->id) {
                $p['is_candidate'] = $participant->is_candidate;
                $p['role'] = $participant->role;
                break;
            }
        }
        $election->participants = json_encode($participants);
        $election->save();

        $this->logEvent($election->id, "Le participant {$participant->name} a changé son statut de candidature.");

        return redirect()->route('elections.waiting', $election)->with('success', 'Statut de candidature mis à jour.');
    }

    public function start(Election $election)
    {
        try {
            $candidates = $election->participants()->where('is_candidate', true)->count();

            if ($candidates < 2) {
                return redirect()->route('elections.show', $election)->with('error', 'L\'élection ne peut pas être démarrée sans au moins deux candidats.');
            }

            $status = ElectionStatus::where('status', 'en cours')->first();

            if (!$status) {
                Log::error('Le statut "en cours" est introuvable.');
                return redirect()->route('elections.show', $election)->with('error', 'Le statut "en cours" est introuvable.');
            }

            $election->status_id = $status->id;
            $election->save();

            $this->logEvent($election->id, 'L\'élection a commencé.');

            return redirect()->route('elections.show', $election)->with('success', 'L\'élection a commencé.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage de l\'élection:', ['exception' => $e]);
            return redirect()->route('elections.show', $election)->with('error', 'Erreur lors du démarrage de l\'élection.');
        }
    }

    public function vote(Election $election)
    {
        $participant_id = Session::get('participant_id');
        $participant = Participant::find($participant_id);

        if (!$participant || $election->user_id == $participant->id) {
            return redirect()->route('home')->with('error', 'Vous n\'êtes pas autorisé à voter dans cette élection.');
        }

        $results = json_decode($election->results, true) ?? ['rounds' => [[]]];
        $rounds = $results['rounds'];
        $roundIndex = count($rounds) - 1;

        foreach ($rounds[$roundIndex] as $result) {
            if ($result['participant_id'] == $participant_id) {
                return redirect()->route('elections.results', $election)->with('info', 'Vous avez déjà voté.');
            }
        }

        return view('elections.vote', compact('election'));
    }

    public function submitVote(Request $request, Election $election)
    {
        $participant_id = Session::get('participant_id');
        $participant = Participant::find($participant_id);

        if (!$participant || $election->user_id == $participant->id) {
            return redirect()->route('elections.vote', $election)->with('error', 'Vous n\'êtes pas autorisé à voter.');
        }

        $results = json_decode($election->results, true) ?? ['rounds' => [[]]];
        $rounds = $results['rounds'];
        $roundIndex = count($rounds) - 1;

        foreach ($rounds[$roundIndex] as $result) {
            if ($result['participant_id'] == $participant_id) {
                return redirect()->route('elections.results', $election)->with('info', 'Vous avez déjà voté.');
            }
        }

        $vote = $request->input('candidate');
        $rounds[$roundIndex][] = ['vote' => $vote, 'participant_id' => $participant->id];

        $results['rounds'] = $rounds;
        $election->results = json_encode($results);
        $election->save();

        $this->logEvent($election->id, 'Un participant a voté.');

        $totalParticipants = $election->participants()->count();
        if (count($rounds[$roundIndex]) >= $totalParticipants - 1) {
            return $this->endRound($election);
        }

        return redirect()->route('elections.results', $election)->with('success', 'Vote soumis avec succès.');
    }

    public function endRound(Election $election)
    {
        try {
            Log::info('endRound method called for election ID: ' . $election->id);

            $results = $this->getResults($election);
            $roundIndex = $this->getCurrentRoundIndex($results);

            if ($this->isNoVotesRecorded($results, $roundIndex)) {
                return redirect()->route('elections.show', $election)->with('error', 'Aucun vote enregistré pour ce tour.');
            }

            $voteCounts = $this->countVotes($results, $roundIndex);
            $topVotes = array_values($voteCounts);
            $totalVotes = array_sum($topVotes);
            $totalParticipants = $election->participants()->count();

            $this->logEvent($election->id, "Nombre de votes: $totalVotes / Nombre de participants: $totalParticipants");

            $absoluteMajorityThreshold = $totalParticipants / 2;
            $absoluteMajority = $topVotes[0] > $absoluteMajorityThreshold;

            if ($absoluteMajority || $roundIndex > 0) {
                $winnerName = array_search($topVotes[0], $voteCounts);
                $winner = Participant::where('name', $winnerName)->where('election_id', $election->id)->first();
                if ($winner) {
                    $this->electParticipant($election, $winner->id, $absoluteMajority);
                } else {
                    throw new \Exception('Impossible de trouver le participant élu.');
                }
            } else {
                $results['rounds'][] = [];
                $this->saveResults($election, $results);
                $this->logEvent($election->id, 'Pas de majorité absolue, deuxième tour lancé.');

                // Redirection spécifique pour le créateur de l'élection
                if (Auth::id() == $election->user_id) {
                    return redirect()->route('elections.show', $election)->with('info', 'Deuxième tour lancé.');
                } else {
                    // Rediriger les participants (non créateurs) vers la page de vote
                    return redirect()->route('elections.vote', $election);
                }
            }

            $this->saveResults($election, $results);
            $this->notifyParticipants($election);

            return redirect()->route('elections.show', $election)->with('info', 'Le vote est terminé.');
        } catch (\Exception $e) {
            Log::error('Error in endRound method:', ['exception' => $e]);
            return redirect()->route('elections.show', $election)->with('error', 'Erreur lors de la fin du tour.');
        }
    }

    private function electParticipant(Election $election, int $winnerId, bool $absoluteMajority)
    {
        $electedParticipant = Participant::where('id', $winnerId)->where('election_id', $election->id)->first();
        Log::info('Elected participant', ['electedParticipant' => $electedParticipant]);

        if ($electedParticipant === null) {
            Log::error('Elected participant not found.');
            throw new \Exception('Impossible de trouver le participant élu.');
        }

        $electedParticipant->role = 'délégué';
        $electedParticipant->is_candidate = false;
        $electedParticipant->save();

        Log::info('Participant elected as delegate', ['electedParticipant' => $electedParticipant]);

        $participants = json_decode($election->participants, true);
        foreach ($participants as &$p) {
            if ($p['id'] == $electedParticipant->id) {
                $p['is_candidate'] = false;
                $p['role'] = 'délégué';
                break;
            }
        }
        $election->participants = json_encode($participants);
        $election->status_id = ElectionStatus::where('status', 'terminé')->first()->id;
        $election->save();

        $message = "Le participant {$electedParticipant->name} a été élu délégué.";
        if ($absoluteMajority) {
            $message .= " Il a été élu à la majorité absolue.";
        }
        $this->logEvent($election->id, $message);
    }

    private function getResults(Election $election)
    {
        $results = json_decode($election->results, true) ?? ['rounds' => [[]]];
        Log::info('Results decoded', ['results' => $results]);
        return $results;
    }

    private function getCurrentRoundIndex(array $results)
    {
        $roundIndex = count($results['rounds']) - 1;
        Log::info('Current round index', ['roundIndex' => $roundIndex]);
        return $roundIndex;
    }

    private function isNoVotesRecorded(array $results, int $roundIndex)
    {
        $noVotes = $roundIndex < 0 || empty($results['rounds'][$roundIndex]);
        if ($noVotes) {
            Log::info('No votes recorded for this round.');
        }
        return $noVotes;
    }

    private function countVotes(array $results, int $roundIndex)
    {
        $voteCounts = array_count_values(array_column($results['rounds'][$roundIndex], 'vote'));
        arsort($voteCounts);
        Log::info('Vote counts', ['voteCounts' => $voteCounts]);
        return $voteCounts;
    }

    private function saveResults(Election $election, array $results)
    {
        $election->results = json_encode($results);
        $election->save();
        Log::info('Results saved', ['results' => json_decode($election->results, true)]);
    }

    private function notifyParticipants(Election $election)
    {
        $participants = $election->participants()->get();
        foreach ($participants as $participant) {
            if ($participant->is_candidate && $participant->id !== $election->user_id) {
                Session::put('participant_id', $participant->id);
                // Utilisez WebSocket ou autre mécanisme pour notifier les participants
            }
        }
    }

    private function logEvent(int $electionId, string $message)
    {
        ElectionLog::create([
            'election_id' => $electionId,
            'message' => $message,
        ]);
    }

    public function results(Election $election)
    {
        $results = json_decode($election->results, true) ?? [];
        return view('elections.results', compact('election', 'results'));
    }

    public function destroy(Election $election)
    {
        $election->delete();
        return redirect()->route('home')->with('success', 'Election supprimée avec succès.');
    }

    public function checkRoundStatus(Election $election)
    {
        $results = json_decode($election->results, true) ?? ['rounds' => [[]]];
        $roundIndex = count($results['rounds']) - 1;
    
        return response()->json([
            'status' => $election->status->status,
            'roundIndex' => $roundIndex
        ]);
    }      
}
