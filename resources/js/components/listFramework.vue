<template>
    <div class="container">
        <div v-if="errorsInProgram" class="row alert alert-danger mt-3">
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
        </div>
        <div class="p-3 border m-3 rounded bg-white">
            <form class="row" @submit.prevent="submitFormFilter">
                <div class="row mb-2 col-md-12">
                    <div class="col-md-4">
                        <label><h5>Liste à afficher</h5> </label>
                        <select
                            v-model="formFilter.displayElement"
                            class="mr-2 form-control d-inline-block"
                        >
                            <option disabled value="" selected>
                                -- Affichage par --
                            </option>
                            <option value="UE" selected>
                                Unité d'enseignement
                            </option>
                            <option value="AAT">
                                acquis d'apprentissages terminaux
                            </option>
                            <option value="AAV">
                                acquis d'apprentissages visés
                            </option>
                            <option value="PRO">programme</option>
                        </select>
                    </div>

                    <div class="col-md-4">
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
                    <div
                        class="col-md-4"
                        v-if="formFilter.displayElement === 'UE'"
                    >
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
                    </div>
                </div>
                <div class="col-md-12 mb-3">
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
                </div>
                <div class="row col-md-12">
                    <div class="col-md-6">
                        <button
                            type="button"
                            @click="isModalExportVisible = true"
                            class="align-bottom btn btn-lg btn-success"
                        >
                            exporter sous .csv
                        </button>
                    </div>
                    <div class="col-md-6 text-right">
                        <button
                            type="submit"
                            class="align-bottom btn btn-lg btn-primary"
                        >
                            rechercher
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div
            id="listUniteEnseignement"
            v-if="formFilter.displayElement === 'UE'"
        >
            <list
                :isBorder="true"
                routeGET="/ues/get"
                linkDetailed="ue-detail"
                :key="reloadKey"
                typeList="UE"
                :paramsRouteGET="formFilter"
                :listColonne="['code', 'name', 'ects', 'semestre']"
            />
        </div>
        <div class="mt-3 container" v-if="formFilter.displayElement === 'AAT'">
            <list
                :isBorder="true"
                routeGET="/aats/get"
                linkDetailed="aat-detail"
                typeList="AAT"
                :key="reloadKey"
                :listColonne="['code', 'name']"
            />
        </div>
        <div class="mt-3 container" v-if="formFilter.displayElement === 'AAV'">
            <list
                :isBorder="true"
                routeGET="/aav/get"
                linkDetailed="aav-detail"
                typeList="AAV"
                :key="reloadKey"
                :listColonne="['code', 'name']"
            />
        </div>
        <div class="mt-3 container" v-if="formFilter.displayElement === 'PRO'">
            <list
                :isBorder="true"
                routeGET="/pro/get"
                linkDetailed="pro-detail"
                typeList="PRO"
                :key="reloadKey"
                :listColonne="['code', 'name', 'ects']"
            />
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
        this.loadErrorInProgram();
        this.loadProgram();
    },
};
</script>
