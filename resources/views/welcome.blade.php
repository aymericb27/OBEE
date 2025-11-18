@extends('template')

@section('content')
    <div id="app" class=" h-100" data-csrf="{{ csrf_token() }}">
        <router-view />
    </div>
@endsection
