<!-- resources/views/elections/index.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Élections</h1>
        <a href="{{ route('elections.create') }}" class="btn btn-primary">Créer une nouvelle élection</a>
        <ul class="list-group mt-3">
            @foreach ($elections as $election)
                <li class="list-group-item">
                    <a href="{{ route('elections.show', $election) }}">{{ $election->name }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
