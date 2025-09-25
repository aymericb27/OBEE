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
                <button class="ml-1 btn_fa" @click="toggleView('form')">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <h1>OBEE-tool</h1>
        </header>
        <div class="row h-100 m-0">
            <div class=" mainBody w-100">
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
                <div v-if="activeView === 'form'">
                    <addform></addform>
                </div>
            </div>
        </div>
    @endsection
