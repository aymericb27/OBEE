@extends('template')

@section('content')
    <div id="app" class=" h-100">
        <div class="row h-100">
            <div class="col-md-2 menu pt-3">
                <button @click="toggleForm('UE')" class="btn btn-primary mb-3">
                    Créer unité d'enseignement
                </button>
                <button @click="toggleForm('EC') && loadUEs()" class="btn btn-primary mb-3">
                    Créer élément constitutif
                </button>
            </div>
            <div class="col-md-10 mainBody pt-3">
                <div class="listBtn">
                    <button class="btn_fa">
                        <i class="fa-solid fa-list"></i>
                    </button>
                    <button class="ml-1 btn_fa">
                        <i class="fa-solid fa-calendar"></i>
                    </button>

                </div>
                <form-ue :show="activeForm === 'UE'" @submitted="hideForm" csrf="{{ csrf_token() }}"
                    route="{{ route('UE.store') }}" @refresh="loadUEs">
                </form-ue>
                <form-ec :show="activeForm === 'EC'" @submitted="hideForm" csrf="{{ csrf_token() }}"
                    route="{{ route('EC.store') }}" @refresh="loadECs">
                </form-ec>
                <ul>
                    <li v-for="ue in ues" :key="ue.id">
                        <p>@{{ ue.nom }}</p>
                        <p>@{{ ue.code }}</p>
                        <p>@{{ ue.description}}</p>


                    </li>
                </ul>
            </div>
        </div>
    @endsection
