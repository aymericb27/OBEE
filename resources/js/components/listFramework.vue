<template>
    <div class="row w-100 mt-3">
        <!--         <div v-if="errorsInProgram" class="row alert alert-danger mt-3">
            <div class="col-md-1">
                <i
                    class="fa-solid fa-triangle-exclamation"
                    style="color: #f3aa24; font-size: 48px"
                ></i>
            </div>
            <div class="col-md-11">
                Une erreur est survenue dans le chargement du programme.
                <ul>
                    <li v-if="errors.errorsHoraire">
                        Conflit horaire entre des unités d'enseignement :
                        <span> {{ errors.errorsHoraire.length }}</span> conflits
                        <router-link
                            :to="{
                                name: 'sheduleError',
                            }"
                        >
                            voir plus
                        </router-link>
                    </li>
                    <li v-if="errors.errorECTS">
                        Pas assez ou trop de crédits attribué :
                        <span> {{ errors.errorECTS.length }}</span> programmes
                        concernés
                        <router-link
                            :to="{
                                name: 'programmeError',
                            }"
                        >
                            voir plus
                        </router-link>
                    </li>
                </ul>
            </div>
        </div> -->
        <div class="col-md-3">
            <form
                class="ml-3 p-3 border rounded bg-white"
                @submit.prevent="submitFormFilter"
            >
                <div>
                    <label>
                        <h2 class="secondary_color">
                            <i
                                class="fa-solid fa-clipboard-list primary_color"
                            ></i>
                            Liste des éléments
                        </h2>
                    </label>
                    <ul class="secondary_color">
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'UE',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('UE')"
                        >
                            <h5 class="m-0">Unité d'enseignement</h5>
                        </li>
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'AAT',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('AAT')"
                        >
                            <h5 class="m-0">
                                Acquis d'apprentissages terminaux
                            </h5>
                        </li>
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'AAV',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('AAV')"
                        >
                            <h5 class="m-0">Acquis d'aprentissages visées</h5>
                        </li>
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'PRE',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('PRE')"
                        >
                            <h5 class="m-0">Prérequis</h5>
                        </li>
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'PRO',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('PRO')"
                        >
                            <h5 class="m-0">Programme</h5>
                        </li>
                    </ul>
                </div>

                <!--                 <div>
                    <label><h5>Pour le semestre</h5> </label>
                    <select
                        class="mr-2 form-control d-inline-block"
                        v-model="formFilter.semestre"
                    >
                        <option value="" selected>
                            -- Tout les semestres --
                        </option>
                        <option value="1">1er semestre</option>
                        <option value="2">2ème semestre</option>
                    </select>
                </div>
                <div v-if="formFilter.displayElement === 'UE'">
                    <label><h5>Faisant partie du programme</h5> </label>

                    <select
                        class="mr-2 form-control d-inline-block"
                        v-model="formFilter.program"
                    >
                        <option value="">-- Tous les programmes --</option>
                        <option v-for="prog in this.progs" :value="prog.id">
                            {{ prog.name }}
                        </option>
                    </select>
                </div> -->
                <!--                 <div class="col-md-12 mb-3">
                    <div
                        class="form-check d-inline-block align-middle mr-3 ml-3"
                    >
                        <input
                            type="checkbox"
                            id="filterError"
                            class="form-check-input"
                            v-model="formFilter.onlyErrors"
                        />
                        <label for="filterError" class="form-check-label">
                            uniquement les éléments avec une erreur
                        </label>
                    </div>
                </div> -->
                <div class="text-right">
                    <button
                        type="button"
                        @click="isModalExportVisible = true"
                        class="align-bottom btn btn-lg btn-success"
                    >
                        exporter sous .csv
                    </button>
                    <!--                     <diV>
                        <button
                            type="submit"
                            class="align-bottom btn btn-lg btn-primary"
                        >
                            rechercher
                        </button>
                    </div> -->
                </div>
            </form>
        </div>
        <div class="col-md-9">
            <div class="border rounded bg-white pt-2">
                <list
                    :isBorder="true"
                    :routeGET="routeGET"
                    :linkDetailed="linkDetailed"
                    :key="reloadKey"
                    :typeList="formFilter.displayElement"
                    :paramsRouteGET="formFilter"
                    :listColonne="listColonne"
                    :isResearch="true"
                />
            </div>
        </div>
    </div>
    <modal-export
        :visible="isModalExportVisible"
        :filter="formFilter"
        @close="isModalExportVisible = false"
    />
</template>

<script>
import axios from "axios";
import list from "./list.vue";
import modalExport from "./modalExport.vue";
export default {
    data() {
        return {
            errorsInProgram: false,
            isModalExportVisible: false,
            progs: [],
            errors: {},
            linkDetailed: "",
            routeGET: "",
            listColonne: [],
            formFilter: {
                displayElement: "UE",
                program: "",
                semestre: "",
            },

            reloadKey: 0, // ✅ Clé réactive pour forcer le rechargement
        };
    },
    components: {
        list,
        modalExport,
    },
    methods: {
        selectProgram(element) {
            this.listColonne = ["code", "name"];

            this.formFilter.displayElement = element;
            if (element === "UE") {
                this.routeGET = "/ues/get";
                this.linkDetailed = "ue-detail";
                this.listColonne = ["code", "name", "ects"];
            } else if (element === "AAT") {
                this.routeGET = "/aat/get";
                this.linkDetailed = "aat-detail";
            } else if (element === "AAV") {
                this.routeGET = "/aav/get";
                this.linkDetailed = "aav-detail";
            } else if (element === "PRO") {
                this.routeGET = "/pro/get";
                this.linkDetailed = "pro-detail";
            } else if (element === "PRE") {
                this.routeGET = "/aav/pre/get";
                this.linkDetailed = "aav-detail";
            } 
            this.reloadKey++; // force la liste à se recharger
        },
        handleExportModal(value) {
            this.isModalExportVisible = value;
            console.log("Valeur reçue :", value);
        },

        submitFormFilter(filters) {
            Object.assign(this.formFilter, filters); // ✅ mise à jour réactive
            console.log(this.formFilter);
            this.reloadKey++; // force la liste à se recharger
        },

        async loadErrorInProgram() {
            try {
                const response = await axios.get("/Error/UES");
                if (response.data.isError) {
                    this.errorsInProgram = true;
                }
                this.errors = response.data;

                console.log(response.data);
            } catch (error) {
                console.log(error);
            }
        },
        async loadProgram() {
            const response = await axios.get("pro/get");
            this.progs = response.data;
        },
    },

    mounted() {
        //this.loadErrorInProgram();
        this.loadProgram();
        this.selectProgram("UE");
    },
};
</script>
