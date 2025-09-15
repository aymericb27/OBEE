@extends('template')

@section('content')
    <div id="app" class="container h-100">
        <div class="row h-100">
            <div class="col-md-3 menu pt-3">
                <button @click="showForm = !showForm" class="btn btn-primary mb-3">
                    @{{ showForm ? 'Masquer le formulaire' : 'Créer unité d\'enseignement' }}
                </button>

            </div>
            <div class="col-md-9 mainBody pt-3">
                <div class="listBtn">
                    <button>
                        <i class="fa-solid fa-list"></i>
                    </button>
                    <button class="ml-1">
                        <i class="fa-solid fa-calendar"></i>
                    </button>

                </div>
                <form-ue :show="showForm" csrf="{{ csrf_token() }}" route="{{ route('UE.store') }}"
                    @refresh="loadUEs">
                </form-ue>
                <ul>
                    <li v-for="ue in ues" :key="ue.id">
                        @{{ ue.name }}
                    </li>
                </ul>
            </div>
        </div>
    @endsection
