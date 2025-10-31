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
        <div class="p-3 border m-3 rounded bg-light">
            <FormFilter
                @submit="submitFormFilter"
                @isModalExportVisible="handleExportModal"
            />
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
                routeGET="/aavs/get"
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
        @close="isModalExportVisible = false"
    />
</template>

<script>
import axios from "axios";
import list from "./list.vue";
import FormFilter from "./form/formFilter.vue";
import modalExport from "./modalExport.vue";
export default {
    data() {
        return {
            errorsInProgram: false,
            isModalExportVisible: false,

            errors: {},
            formFilter: {
                displayElement: "UE",
            },
            reloadKey: 0, // ✅ Clé réactive pour forcer le rechargement
        };
    },
    components: {
        list,
        FormFilter,
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
    },

    mounted() {
        this.loadErrorInProgram();
    },
};
</script>
