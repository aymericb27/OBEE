@extends('template')

@section('content')
    <div id="app" class=" h-100" data-csrf="{{ csrf_token() }}" data-route-calendar="{{ route('calendar.store') }}">

        <header>
            <div class="listBtn m-3">
                <router-link to="/list">
                    <button class="btn_fa">
                        <i class="fa-solid fa-list"></i>
                    </button> </router-link>
                <router-link to="/calendar">
                    <button class="ml-1 btn_fa">
                        <i class="fa-solid fa-calendar"></i>
                    </button>
                </router-link>
                <router-link to="/form">
                    <button class="ml-1 btn_fa">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </router-link>
            </div>
            <h1>OBEE-tool</h1>
        </header>
        <div class="row h-100 m-0">
            <div class=" mainBody w-100">
                <router-view />
            </div>
        </div>
    @endsection
