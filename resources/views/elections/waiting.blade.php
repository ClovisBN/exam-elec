@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>En attente du début de l'élection pour {{ $election->name }}</h1>

        @if ($participant)
            <p>Bienvenue, {{ $participant->name }}.</p>
            <p>Statut actuel: {{ $election->status->status }}</p>
        @else
            <p>Participant non trouvé.</p>
        @endif

        <script>
            function checkElectionStatus() {
                fetch('{{ route('elections.checkRoundStatus', $election) }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'en cours' && !data.isFinished) {
                            window.location.href =
                                '{{ route('elections.vote', ['election' => $election->id, 'type' => 'délégué']) }}';
                        } else if (data.status === 'en cours' && data.type === 'suppléant' && !data.isFinished) {
                            window.location.href =
                                '{{ route('elections.vote', ['election' => $election->id, 'type' => 'suppléant']) }}';
                        } else if (data.isFinished) {
                            window.location.href = '{{ route('elections.results', $election) }}';
                        }
                    });
            }

            setInterval(checkElectionStatus, 5000); // Vérifie toutes les 5 secondes
        </script>
    </div>
@endsection
