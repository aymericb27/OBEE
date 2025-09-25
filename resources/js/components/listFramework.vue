<template>
    <div class="container" v-if="!selectedUE">
        <div class="p-3 border mb-3 rounded bg-light">
            <div id="filter">
                <form @submit.prevent="submitFormFilter">
                    <i class="fa-solid fa-filter mr-2"></i>
                    <select
                        v-model="formFilter.displayElelement"
                        class="mr-2 w-25 form-control d-inline-block"
                    >
                        <option disabled value="" selected>
                            -- Affichage par --
                        </option>
                        <option value="UE" selected>
                            Unité d'enseignement
                        </option>
                        <option value="EC">éléments constitutifs</option>
                    </select>
                    <select class="mr-2 w-25 form-control d-inline-block">
                        <option disabled value="" selected>
                            -- Choisir le semestre --
                        </option>
                        <option value="UE" selected>1er semestre</option>
                        <option value="EC">2ème semestre</option>
                    </select>
                    <button type="submit" class="align-bottom btn btn-primary">
                        rechercher
                    </button>
                </form>
            </div>
        </div>

        <div id="listUniteEnseignement" class="mt-3">
            <ul>
                <li v-for="ue in ues" :key="ue.id" class="mb-3">
                    <div class="row mb-2">
                        <div class="box_code" @click="openDetailed(ue.UEid)">
                            {{ ue.UEcode }}
                        </div>
                        <h3 class="primary_color ml-2 mb-0">
                            <a href="#" @click="openDetailed(ue.UEid)">{{
                                ue.UEname
                            }}</a>
                        </h3>
                    </div>
                    <p>{{ ue.UEdescription }}</p>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <a
                                href="#"
                                class="linkedbtn"
                                @click="openDataUE('listEC', ue.UEid)"
                            >
                                <i
                                    class="fa-solid fa-chevron-right"
                                    v-if="!openedUE.includes(ue.UEid)"
                                ></i>
                                <i
                                    class="fa-solid fa-chevron-down"
                                    v-if="openedUE.includes(ue.UEid)"
                                ></i>
                                voir les éléments constitutifs</a
                            >
                        </div>
                        <div class="col-md-4 mb-2">
                            <a
                                href="#"
                                class="linkedbtn"
                                @click="openDataUE('listEC', ue.UEid)"
                            >
                                <i class="fa-solid fa-chevron-right"></i>
                                <i
                                    class="fa-solid fa-chevron-down"
                                    v-if="false"
                                ></i>
                                voir les acquis d'apprentissages</a
                            >
                        </div>
                        <div class="col-md-4 mb-2">
                            <a
                                href="#"
                                class="linkedbtn"
                                @click="openDataUE('listEC', ue.UEid)"
                            >
                                <i class="fa-solid fa-chevron-right"></i>
                                <i
                                    class="fa-solid fa-chevron-down"
                                    v-if="false"
                                ></i>
                                voir les prérequis</a
                            >
                        </div>
                    </div>
                    <div class="listChildUE" v-if="openedUE.includes(ue.UEid)">
                        <ul class="p-0">
                            <li v-for="ec in ue.ecs">
                                <div class="row mb-2">
                                    <div class="box_code">{{ ec.ECcode }}</div>
                                    <div class="col-md-9">
                                        <h4 class="primary_color mb-0">
                                            {{ ec.ECname }}
                                        </h4>
                                    </div>
                                </div>
                                <p>{{ ec.ECdescription }}</p>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <UEDetail v-if="selectedUE" :ueid="selectedUE" @close="selectedUE = null" />
</template>

<script>
import axios from "axios";
import UEDetail from "./UEDetailed.vue";

export default {
    components: { UEDetail },

    data() {
        return {
            ues: [], // liste des UE
            showData: false,
            openedUE: [],
            detailed: false,
            selectedUE: null, // l’UE sélectionnée

            formFilter: {
                displayElelement: "",
            },
        };
    },
    methods: {
        openDetailed(ueid) {
            this.selectedUE = ueid;
        },
        async submitFormFilter() {},
        async loadUEs() {
            try {
                const response = await axios.get("/UEGet", {
                    params: {
                        withUE: true,
                    },
                });
                this.ues = response.data;
            } catch (error) {
                console.log(error);
            }
        },
        openDataUE(listData, ueID) {
            if (this.openedUE.includes(ueID)) {
                // déjà dedans → on le retire
                this.openedUE = this.openedUE.filter((id) => id !== ueID);
            } else {
                // pas dedans → on l’ajoute
                this.openedUE.push(ueID);
            }
            console.log(this.openedUE);
        },
    },
    mounted() {
        this.loadUEs();
    },
};
</script>
