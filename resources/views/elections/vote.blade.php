@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Votez pour votre délégué</h1>

        <form action="{{ route('elections.submitVote', $election) }}" method="POST">
            @csrf
            @foreach (json_decode($election->participants, true) as $participant)
                @if ($participant['is_candidate'])
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="candidate" value="{{ $participant['name'] }}"
                            id="candidate{{ $participant['name'] }}">
                        <label class="form-check-label" for="candidate{{ $participant['name'] }}">
                            {{ $participant['name'] }}
                        </label>
                    </div>
                @endif
            @endforeach
            <button type="submit" class="btn btn-primary mt-3">Soumettre le vote</button>
        </form>
    </div>
@endsection
