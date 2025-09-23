@extends('template')

@section('content')
    <div id="app" class=" h-100">
        <div class="row h-100 m-0">
            <div class="col-md-2 menu pt-3">
                <button @click="toggleForm('UE')" class="btn btn-primary mb-3">
                    Créer unité d'enseignement
                </button>
                <button @click="toggleForm('EC') && loadUEs()" class="btn btn-primary mb-3">
                    Créer élément constitutif
                </button>
                <button @click="toggleForm('AAV') && loadUEs()" class="btn btn-primary mb-3">
                    Créer acuis d'apprentissage visé
                </button>
            </div>
            <div class="col-md-10 mainBody pt-3">
                <div class="listBtn">
                    <button class=" btn_fa" @click="toggleView('calendar')">
                        <i class="fa-solid fa-calendar"></i>
                    </button>
                    <button class="ml-1 btn_fa" @click="toggleView('list')">
                        <i class="fa-solid fa-list"></i>
                    </button>
                </div>
                <form-ue :show="activeForm === 'UE'" @submitted="hideForm" csrf="{{ csrf_token() }}"
                    route="{{ route('UE.store') }}" @refresh="loadUEs">
                </form-ue>
                <form-ec :show="activeForm === 'EC'" @submitted="hideForm" csrf="{{ csrf_token() }}"
                    route="{{ route('EC.store') }}" @refresh="loadECs">
                </form-ec>
                <list-framework v-if="activeView === 'list'"> </list-framework>
                <div id="calendar" v-if="activeView === 'calendar'" class="mt-3" >
                    <calendar csrf="{{ csrf_token() }}" :route-calendar="'{{ route('calendar.store') }}'" route="{{ route('calendar.store') }}"></calendar>
                </div>
                <div>
                </div>
            </div>
        </div>
    @endsection
