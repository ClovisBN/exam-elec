<!-- resources/views/elections/results.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Résultats pour {{ $election->name }}</h1>

        <h3>Résultats pour le délégué:</h3>
        @if (isset($results['délégué']))
            @foreach ($results['délégué'] as $round => $votes)
                @if (is_array($votes))
                    <h4>{{ ucfirst(str_replace('_', ' ', $round)) }}:</h4>
                    <ul>
                        @foreach ($votes as $vote)
                            <li>{{ $vote['vote'] }} (Participant ID: {{ $vote['participant_id'] }})</li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        @else
            <p>Aucun vote enregistré pour le moment.</p>
        @endif

        <h3>Résultats pour le suppléant:</h3>
        @if (isset($results['suppléant']))
            @foreach ($results['suppléant'] as $round => $votes)
                @if (is_array($votes))
                    <h4>{{ ucfirst(str_replace('_', ' ', $round)) }}:</h4>
                    <ul>
                        @foreach ($votes as $vote)
                            <li>{{ $vote['vote'] }} (Participant ID: {{ $vote['participant_id'] }})</li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        @else
            <p>Aucun vote enregistré pour le moment.</p>
        @endif
    </div>

    <script>
        function checkElectionStatus() {
            fetch('{{ route('elections.checkRoundStatus', $election) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'en cours' && data.type === 'suppléant') {
                        window.location.href = '{{ route('elections.vote', [$election->id, 'suppléant']) }}';
                    }
                })
                .catch(error => console.error('Error fetching election status:', error));
        }

        setInterval(checkElectionStatus, 5000); // Check every 5 seconds
    </script>
@endsection
