<template>
    <div class="m-3 border p-4 bg-white rounded">
        <h5 class="d-inline-block">Niveau de contribution :</h5>
        <span><span class="strong_mapping ml-2 mr-1">3</span>forte </span>
        <span><span class="medium_mapping ml-2 mr-1">2</span>moyenne </span>
        <span><span class="weak_mapping ml-2 mr-1">1</span>faible </span>
    </div>
    <div class="row mr-0">
        <div class="col-md-3 pr-0">
            <div class="border m-3 p-3 bg-white rounded">
                <h2 class="secondary_color">
                    <i class="fa-brands fa-gg-circle primary_color"></i> Acquis
                    d'apprentissage terminaux
                </h2>
                <ul class="secondary_color" v-if="aats.length">
                    <li
                        class="p-2 aat-item"
                        :class="{ active: selectedAATId === aat.id }"
                        style="list-style-type: none; cursor: pointer"
                        v-for="aat in aats"
                        :key="aat.id"
                        @click="selectAAT(aat.id)"
                    >
                        <h5 class="m-0">{{ aat.name }}</h5>
                    </li>
                </ul>
                <div v-else class="p-2">
                    Aucune acquis d'apprentissage terminaux dans le programme
                </div>
                <div class="text-right">
                    <router-link
                        :to="{
                            name: 'createAAT',
                        }"
                    >
                        <button class="btn btn-lg btn-primary ml-auto mb-2">
                            + ajout AAT
                        </button>
                    </router-link>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="border m-3 p-4 bg-white rounded secondary_color">
                <!--                 <div>
                    <ul class="p-0 mb-3">
                        <li
                            class="d-inline-block p-3 selectList cursor_pointer"
                            :class="{ active: selectedList === 'UE' }"
                            @click="selectedList = 'UE'"
                        >
                            <h5>par unité d'enseignement</h5>
                        </li>
                        <li
                            class="ml-2 d-inline-block p-3 selectList cursor_pointer"
                            :class="{ active: selectedList === 'AAV' }"
                            @click="selectedList = 'AAV'"
                        >
                            <h5>par acquis d'apprentissage visé</h5>
                        </li>
                    </ul>
                </div> -->
                <div class="bg-primary rounded p-4" v-if="aat.id">
                    <h5>
                        <i
                            class="fa-solid fa-book-bookmark"
                            style="
                                font-size: 24px;
                                color: rgb(90 171 255) !important;
                            "
                        ></i>
                        <router-link
                            v-if="aat.id"
                            :to="{
                                name: 'aat-detail',
                                params: { id: aat.id },
                            }"
                            class="AATLink"
                        >
                            {{ aat.code }}
                        </router-link>

                        {{ aat.name }}
                    </h5>
                </div>
                <div
                    class="ml-4 mt-2"
                    v-for="ue in aat.ues"
                    v-if="selectedList === 'UE'"
                >
                    <div class="rounded p-4 bg-green">
                        <h5 class="d-inline-block">
                            <i
                                class="fa-solid fa-arrow-right"
                                style="color: #3ad55d"
                            ></i>
                            <router-link
                                v-if="ue.id"
                                :to="{
                                    name: 'ue-detail',
                                    params: { id: ue.id },
                                }"
                                class="UELink"
                            >
                                {{ ue.code }}
                            </router-link>
                            {{ ue.name }}
                        </h5>
                    </div>
                    <div class="ml-4 mt-2" v-for="aav in ue.aavvise">
                        <div class="p-4 rounded bg-grey">
                            <div class="row">
                                <h5 class="col-md-11">
                                    <i
                                        class="fa-solid fa-arrow-right"
                                        style="color: rgb(167 167 167)"
                                    ></i>
                                    <router-link
                                        v-if="aav.id"
                                        :to="{
                                            name: 'aav-detail',
                                            params: { id: aav.id },
                                        }"
                                        class="childUELink"
                                    >
                                        {{ aav.code }}
                                    </router-link>
                                    {{ aav.name }}
                                </h5>
                                <div class="col-md-1">
                                    <span
                                        :class="{
                                            strong_mapping:
                                                aav.contribution === 3,
                                            medium_mapping:
                                                aav.contribution === 2,
                                            weak_mapping:
                                                aav.contribution === 1,
                                        }"
                                        class="ml-2 mr-1"
                                        >{{ aav.contribution }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ml-4 mt-2" v-for="child in ue.children">
                        <div class="p-4 rounded bg-grey">
                            <div>
                                <h5 class="d-inline-block">
                                    <i
                                        class="fa-solid fa-arrow-right"
                                        style="color: rgb(167 167 167)"
                                    ></i>
                                    <router-link
                                        v-if="child.id"
                                        :to="{
                                            name: 'ue-detail',
                                            params: { id: child.id },
                                        }"
                                        class="childUELink"
                                    >
                                        {{ child.code }}
                                    </router-link>
                                    {{ child.name }}
                                </h5>
                                <span
                                    :class="{
                                        strong_mapping:
                                            child.contribution === 3,
                                        medium_mapping:
                                            child.contribution === 2,
                                        weak_mapping: child.contribution === 1,
                                    }"
                                    class="float-right ml-2 mr-1"
                                    >{{ child.contribution }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="!aat.id">
                    <h5 class="p-4">
                        Aucun acquis d'apprentissage terminal sélectionné
                    </h5>
                </div>
                <div v-if="selectedList == 'UE' && !aat.ues.length">
                    <h5 class="p-4">Aucune unité d'enseignement lié</h5>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";

export default {
    data() {
        return {
            aats: [],
            selectedAATId: "",
            selectedList: "UE",
            aat: {
                ues: [],
                aavs: [],
            },
        };
    },
    components: {},

    methods: {
        selectAAT(id) {
            this.selectedAATId = id;
            this.loadAATTree(id);
        },
        async loadAATTree(id) {
            const responseAAT = await axios.get("aat/get/tree", {
                params: { id },
            });
            console.log(this.aat);

            this.aat = responseAAT.data;
        },
        async loadAAT() {
            const responseAAT = await axios.get("aat/get");
            this.aats = responseAAT.data;
            this.selectAAT(this.aats[0].id);
        },
    },
    mounted() {
        this.loadAAT();
    },
};
</script>
<style>
.childUELink,
.childUELink:hover {
    color: rgb(167 167 167);
}
.UELink,
.UELink:hover,
.AAVLink,
.AAVLink:hover {
    color: #3ad55d;
}
.bg-primary {
    background-color: #d2e6fb !important;
}
.AATLink {
    color: #52a6ff;
}
.AATLink:hover {
    color: #52a6ff;
}
.aat-item,
.selectList {
    border-radius: 5px;
    transition: 0.2s;
}

.aat-item:hover,
.selectList:hover {
    background: #f3f3f3;
}

.aat-item.active,
.selectList.active {
    background: rgba(42, 113, 205, 0.89);
    color: white;
}
.bg-green {
    background-color: #deffe9 !important;
}
.bg-grey {
    background-color: #f5f5f5 !important;
}
</style>
