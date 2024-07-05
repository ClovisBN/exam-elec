<!-- resources/views/elections/create.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Créer une nouvelle élection</h1>
        <form action="{{ route('elections.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Nom de l'élection</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Créer l'élection</button>
        </form>
    </div>
@endsection
