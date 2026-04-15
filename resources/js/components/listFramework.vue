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
                            <h5 class="m-0">Unités d'enseignement (UE)</h5>
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
                                Acquis d'apprentissage terminaux (AAT)
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
                            <h5 class="m-0">Acquis d'aprentissage visées (AAV)</h5>
                        </li>
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'PRE',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('PRE')"
                        >
                            <h5 class="m-0">Prérequis des unités d'enseignement en termes d'AAV</h5>
                        </li>
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'PREPRO',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('PREPRO')"
                        >
                            <h5 class="m-0">Prérequis des programmes</h5>
                        </li>
                        <li
                            class="p-2 program-item"
                            :class="{
                                active: formFilter.displayElement === 'PRO',
                            }"
                            style="list-style-type: none; cursor: pointer"
                            @click="selectProgram('PRO')"
                        >
                            <h5 class="m-0">Programmes</h5>
                        </li>
                    </ul>
                </div>
            </form>
        </div>
        <div class="col-md-9">
            <div class="border rounded bg-white pt-2 mb-2">
                <list
                    :isBorder="true"
                    :routeGET="routeGET"
                    :linkDetailed="linkDetailed"
                    :key="reloadKey"
                    :typeList="formFilter.displayElement"
                    :paramsRouteGET="formFilter"
                    :listColonne="listColonne"
                    :sortByCode="true"
                    :isResearch="true"
                    :actionButton="listActionButton"
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
import { currentProgramState } from "../stores/currentProgram";
const DISPLAY_ELEMENT_STORAGE_KEY = "listFramework.displayElement";
const ALLOWED_DISPLAY_ELEMENTS = ["UE", "AAT", "AAV", "PRE", "PREPRO", "PRO"];
export default {
    computed: {
        currentProgram() {
            return currentProgramState;
        },
    },
    watch: {
        "currentProgram.id"(newId, oldId) {
            if (newId === oldId) return;
            this.syncCurrentProgramFilter();
            this.reloadKey++;
        },
    },
    data() {
        return {
            errorsInProgram: false,
            isModalExportVisible: false,
            progs: [],
            errors: {},
            linkDetailed: "",
            routeGET: "",
            listColonne: [],
            listActionButton: null,
            formFilter: {
                displayElement: "UE",
                program: "",
                program_id: "",
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
        getSavedDisplayElement() {
            try {
                const savedElement = sessionStorage.getItem(
                    DISPLAY_ELEMENT_STORAGE_KEY,
                );
                if (ALLOWED_DISPLAY_ELEMENTS.includes(savedElement)) {
                    return savedElement;
                }
            } catch (error) {
                console.warn(
                    "Impossible de lire la liste sélectionnée depuis la session.",
                    error,
                );
            }

            return "UE";
        },
        persistDisplayElement(element) {
            try {
                sessionStorage.setItem(DISPLAY_ELEMENT_STORAGE_KEY, element);
            } catch (error) {
                console.warn(
                    "Impossible d'enregistrer la liste sélectionnée en session.",
                    error,
                );
            }
        },
        syncCurrentProgramFilter() {
            const programId = this.currentProgram.id || "";
            this.formFilter.program_id = programId;
            this.formFilter.program = programId;
        },
        selectProgram(element) {
            this.listColonne = ["code", "name"];
            this.listActionButton = null;

            this.formFilter.displayElement = element;
            this.syncCurrentProgramFilter();
            if (element === "UE") {
                this.routeGET = "/ues/get";
                this.linkDetailed = "ue-detail";
                this.listColonne = ["code", "name", "ects"];
                this.listActionButton = {
                    label: "+ ajout UE",
                    to: {
                        name: "createUE",
                        query: this.currentProgram.id
                            ? { programID: this.currentProgram.id }
                            : {},
                    },
                };
            } else if (element === "AAT") {
                this.routeGET = "/aat/get";
                this.linkDetailed = "aat-detail";
                this.listActionButton = {
                    label: "+ ajout AAT",
                    to: {
                        name: "createAAT",
                        query: this.currentProgram.id
                            ? { programID: this.currentProgram.id }
                            : {},
                    },
                };
            } else if (element === "AAV") {
                this.routeGET = "/aav/get";
                this.linkDetailed = "aav-detail";
                this.listActionButton = {
                    label: "+ ajout AAV",
                    to: {
                        name: "createAAV",
                        query: this.currentProgram.id
                            ? { programID: this.currentProgram.id }
                            : {},
                    },
                };
            } else if (element === "PRO") {
                this.routeGET = "/pro/get";
                this.linkDetailed = "pro-detail";
            } else if (element === "PRE") {
                this.routeGET = "/aav/prerequis/get";
                this.linkDetailed = "aav-detail";
            } else if (element === "PREPRO") {
                this.routeGET = "/aav/pro/prerequis/get";
                this.linkDetailed = "aav-detail";
            }
            this.persistDisplayElement(element);
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
            /* try {
                const response = await axios.get("/Error/UES");
                if (response.data.isError) {
                    this.errorsInProgram = true;
                }
                this.errors = response.data;

                console.log(response.data);
            } catch (error) {
                console.log(error);
            } */
        },
        async loadProgram() {
            const response = await axios.get("pro/get");
            this.progs = response.data;
        },
    },

    mounted() {
        //this.loadErrorInProgram();
        this.loadProgram();
        this.syncCurrentProgramFilter();
        this.selectProgram(this.getSavedDisplayElement());
    },
};
</script>
<style>
.current-program-banner {
    font-size: 0.95rem;
}
</style>


