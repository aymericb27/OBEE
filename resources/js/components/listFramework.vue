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
                <div v-if="errors.errorsHoraire">
                    Conflit horaire :
                    <span> {{ errors.errorsHoraire.length }}</span> conflits
                    <router-link
                        :to="{
                            name: 'sheduleError',
                        }"
                    >
                        voir plus
                    </router-link>
                </div>
            </div>
        </div>
        <div class="p-3 border m-3 rounded bg-light">
            <div id="filter">
                <form @submit.prevent="submitFormFilter">
                    <div class="mb-2">
                        <i class="fa-solid fa-filter mr-2"></i>
                        <select
                            v-model="formFilter.displayElement"
                            class="mr-2 w-30 form-control d-inline-block"
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
                        </select>
                        <select class="mr-2 w-25 form-control d-inline-block">
                            <option disabled value="" selected>
                                -- Choisir le semestre --
                            </option>
                            <option value="UE" selected>1er semestre</option>
                            <option value="EC">2ème semestre</option>
                        </select>
                    </div>

                    <div class="mb-2 pl-2" style="margin-left: 20px">
                        <div
                            class="form-check d-inline-block align-middle mr-3"
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

                    <button type="submit" class="align-bottom btn btn-primary">
                        rechercher
                    </button>
                </form>
            </div>
        </div>
        <div id="listUniteEnseignement" v-if="listToDisplay === 'UE'">
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
        <div class="mt-3 container" v-if="listToDisplay === 'AAT'">
            <list
                :isBorder="true"
                routeGET="/aats/get"
                linkDetailed="aat-detail"
                typeList="AAT"
                :listColonne="['code', 'name']"
            />
        </div>
        <div class="mt-3 container" v-if="listToDisplay === 'AAV'">
            <list
                :isBorder="true"
                routeGET="/aavs/get"
                linkDetailed="aav-detail"
                typeList="AAV"
                :listColonne="['code', 'name']"
            />
        </div>
    </div>
</template>

<script>
import axios from "axios";
import list from "./list.vue";
export default {
    data() {
        return {
            errorsInProgram: false,
            errors: {},
            listToDisplay: "UE",
            formFilter: {
                displayElement: "UE",
            },
            reloadKey: 0, // ✅ Clé réactive pour forcer le rechargement
        };
    },
    components: {
        list,
    },
    methods: {
        submitFormFilter() {
            // ✅ Rafraîchit la liste (recrée le composant list)
            this.listToDisplay = this.formFilter.displayElement;
            this.reloadKey++;
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
