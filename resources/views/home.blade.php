@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Dashboard') }}</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        <p>{{ __('You are logged in!') }}</p>

                        <h3>Vos élections</h3>
                        <a href="{{ route('elections.create') }}" class="btn btn-primary mb-3">Créer une nouvelle
                            élection</a>
                        <ul class="list-group">
                            @foreach ($elections as $election)
                                <li class="list-group-item">
                                    <a href="{{ route('elections.show', $election) }}">{{ $election->name }}</a>
                                    <a href="{{ route('elections.start', $election) }}"
                                        class="btn btn-sm btn-success float-right ml-2">Démarrer</a>
                                    <form action="{{ route('elections.destroy', $election) }}" method="POST"
                                        class="float-right mr-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
