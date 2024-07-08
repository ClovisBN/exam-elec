@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>User Details</h1>
        <ul>
            <li>Name: {{ $user->name }}</li>
            <li>Email: {{ $user->email }}</li>
            <li>Role: {{ $user->role->name }}</li>
            <li>
                Profile Image:
                @if ($user->profile_image)
                    <img src="{{ asset('storage/' . $user->profile_image) }}" alt="Profile Image" width="100">
                @else
                    No image
                @endif
            </li>
        </ul>

        <h2>Elections</h2>
        <ul>
            @foreach ($elections as $election)
                <li>
                    {{ $election->name }}<br>
                    Délégué:
                    {{ $election->results['délégué']['finish'] ? $election->results['délégué']['elected'] : 'Pas encore élu' }}<br>
                    Suppléant:
                    {{ $election->results['suppléant']['finish'] ? $election->results['suppléant']['elected'] : 'Pas encore élu' }}
                </li>
            @endforeach
        </ul>
    </div>
@endsection
