<!-- resources/views/elections/start.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Démarrer l'élection : {{ $election->name }}</h1>
        <p>Vous pouvez maintenant envoyer le lien de vote aux participants et démarrer l'élection.</p>
        <a href="{{ route('elections.vote', $election) }}" class="btn btn-primary">Commencer à voter</a>
    </div>
@endsection
