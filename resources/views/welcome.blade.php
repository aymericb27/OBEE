@extends('template')

@section('content')
    <div id="app" class=" h-100">
        <header>
            <div class="listBtn m-3">
                <button class=" btn_fa" @click="toggleView('calendar')">
                    <i class="fa-solid fa-calendar"></i>
                </button>
                <button class="ml-1 btn_fa" @click="toggleView('list')">
                    <i class="fa-solid fa-list"></i>
                </button>
                <button class="ml-1 btn_fa" @click="toggleView('tree')">
                    <i class="fa-solid fa-folder-tree"></i>
                </button>
            </div>
            <h1>OBEE-tool</h1>
        </header>
        <div class="row h-100 m-0">
            <div class="col-md-2 menu pt-3" v-if="activeView !== 'tree'">
                <button @click="toggleFormListFramework('AAT') " class="btn btn-primary mb-3" v-if="activeView === 'list'">
                    Créer acquis d'apprentissage terminaux
                </button>
                <button @click="toggleFormListFramework('UE')" class="btn btn-primary mb-3" v-if="activeView === 'list'">
                    Créer unité d'enseignement
                </button>
                <button @click="toggleFormListFramework('EC') && loadUEs()" class="btn btn-primary mb-3"
                    v-if="activeView === 'list'">
                    Créer élément constitutif
                </button>
                <button @click="toggleFormListFramework('AAV') " class="btn btn-primary mb-3" v-if="activeView === 'list'">
                    Créer acquis d'apprentissage visé
                </button>
                <button @click="toggleFormListFramework('PR')" class="btn btn-primary mb-3" v-if="activeView === 'list'">
                    Créer prérequis
                </button>
                <button class="btn btn-primary m-1" @click="openFormAddLesson" v-if="activeView === 'calendar'">
                    Ajouter un cours
                </button>
            </div>
            <div class="col-md-10 mainBody pt-3">
                <list-framework v-if="activeView === 'list'" ref="listFrameworkComp" csrfform="{{ csrf_token() }}"
                    ueroutestore="{{ route('UE.store') }}" ecroutestore="{{ route('EC.store') }}"> </list-framework>
                <div id="calendar" v-if="activeView === 'calendar'" class="mt-3">
                    <calendarv2 csrf="{{ csrf_token() }}" ref="calendarComp"
                        :route-calendar="'{{ route('calendar.store') }}'" route="{{ route('calendar.store') }}">
                    </calendarv2>
                </div>
                <div v-if="activeView === 'tree'">
                    <listtree></listtree>
                </div>
            </div>
        </div>
    @endsection
