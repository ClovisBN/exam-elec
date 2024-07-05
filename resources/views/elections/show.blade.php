@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $election->name }}</h1>

        @if (count(json_decode($election->participants, true)) > 0)
            <h3>Participants:</h3>
            <ul>
                @foreach (json_decode($election->participants, true) as $participant)
                    <li>{{ $participant['name'] }} @if ($participant['is_candidate'])
                            (Candidat)
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p>En attente de participants...</p>
        @endif

        @if ($election->status->status === 'en attente')
            <form action="{{ route('elections.start', $election) }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-primary">Démarrer l'élection</button>
            </form>
        @elseif ($election->status->status === 'en cours')
            <form action="{{ route('elections.endRound', $election) }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-warning">Terminer le vote</button>
            </form>
        @endif

        <div class="mt-4">
            <label for="shareLink">Lien à partager :</label>
            <input type="text" id="shareLink" class="form-control" value="{{ route('elections.joinForm', $election) }}"
                readonly>
        </div>

        <h3>Journal des événements:</h3>
        <ul>
            @foreach ($logs as $log)
                <li>{{ $log->message }}</li>
            @endforeach
        </ul>
    </div>
@endsection
