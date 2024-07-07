@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Votez pour votre {{ $type == 'délégué' ? 'délégué' : 'suppléant' }}</h1>

        <form action="{{ route('elections.submitVote', $election) }}" method="POST">
            @csrf
            @foreach (json_decode($election->participants, true) as $participant)
                @if ($participant['is_candidate'])
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="candidate" value="{{ $participant['name'] }}"
                            id="candidate{{ $participant['id'] }}">
                        <label class="form-check-label" for="candidate{{ $participant['id'] }}">
                            {{ $participant['name'] }}
                        </label>
                    </div>
                @endif
            @endforeach
            <button type="submit" class="btn btn-primary mt-3">Soumettre le vote</button>
        </form>
    </div>
@endsection
