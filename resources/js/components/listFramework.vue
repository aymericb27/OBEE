<template>
    <div class="container">
        <div class="p-3 border m-3 rounded bg-light">
            <div id="filter">
                <form @submit.prevent="submitFormFilter">
                    <i class="fa-solid fa-filter mr-2"></i>
                    <select
                        v-model="formFilter.displayElelement"
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
                    <button type="submit" class="align-bottom btn btn-primary">
                        rechercher
                    </button>
                </form>
            </div>
        </div>
        <div id="listUniteEnseignement" v-if="listToDisplay === 'UE'">
            <list
                routeGET="/ues/get"
                linkDetailed="ue-detail"
                typeList="UE"
                :listColonne="['code', 'name', 'ects', 'semestre']"
            />
        </div>
        <div class="mt-3 container border" v-if="listToDisplay === 'AAT'">
            <list
                routeGET="/aats/get"
                linkDetailed="aat-detail"
                typeList="AAT"
                :listColonne="['code', 'name']"
            />
        </div>
        <div class="mt-3 container border" v-if="listToDisplay === 'AAV'">
            <list
                routeGET="/aavs/get"
                linkDetailed="aav-detail"
                typeList="AAV"
                :listColonne="['code', 'name']"
            />
        </div>
    </div>
</template>

<script>
import list from "./list.vue";

export default {
    data() {
        return {
            listToDisplay: "UE",
            formFilter: {
                displayElelement: "UE",
            },
        };
    },
    components: {
        list,
    },
    methods: {
        async submitFormFilter() {
            this.listToDisplay = this.formFilter.displayElelement;
        },
    },
    mounted() {},
};
</script>
