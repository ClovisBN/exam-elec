<!-- resources/views/elections/join.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Rejoindre l'élection : {{ $election->name }}</h1>
        <form action="{{ route('elections.join', $election) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Nom</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <input type="checkbox" id="is_candidate" name="is_candidate">
                <label for="is_candidate">Je veux être candidat</label>
            </div>
            <button type="submit" class="btn btn-primary">Soumettre</button>
        </form>
    </div>
@endsection
