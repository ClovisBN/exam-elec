<!-- resources/views/elections/waiting.blade.php -->
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
                        if (data.status === 'en cours') {
                            window.location.href = '{{ route('elections.vote', $election) }}';
                        } else if (data.status === 'terminé') {
                            window.location.href = '{{ route('elections.results', $election) }}';
                        }
                    })
                    .catch(error => console.error('Error fetching election status:', error));
            }

            setInterval(checkElectionStatus, 5000); // Vérifie toutes les 5 secondes
        </script>
    </div>
@endsection
