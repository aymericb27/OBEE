@extends('template')

@section('content')
    <div id="app" data-csrf="{{ csrf_token() }}" style="min-height: 100%">
        <router-view />
    </div>
@endsection
