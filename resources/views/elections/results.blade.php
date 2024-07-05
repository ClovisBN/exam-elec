@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Résultats pour {{ $election->name }}</h1>

        <h3>Votes:</h3>
        <ul>
            @if (isset($results['rounds'][0]))
                @foreach ($results['rounds'][0] as $result)
                    <li>{{ $result['vote'] }}</li>
                @endforeach
            @else
                <li>Aucun vote enregistré pour le premier tour.</li>
            @endif
        </ul>

        @if (isset($results['rounds'][1]))
            <h3>Deuxième Tour:</h3>
            <ul>
                @foreach ($results['rounds'][1] as $result)
                    <li>{{ $result['vote'] }}</li>
                @endforeach
            </ul>
        @endif

        @if ($election->status->status === 'terminé')
            <p>Le délégué élu est : {{ $election->participants()->where('role', 'délégué')->first()->name }}</p>
        @endif
    </div>

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
                });
        }

        setInterval(checkElectionStatus, 5000); // Vérifie toutes les 5 secondes
    </script>
@endsection
