<template>
    <form class="row" @submit.prevent="submitFormFilter">
        <div class="row mb-2 col-md-12">
            <div class="col-md-4">
                <label> Liste à afficher</label>
                <select
                    v-model="formFilter.displayElement"
                    class="mr-2 form-control d-inline-block"
                >
                    <option disabled value="" selected>
                        -- Affichage par --
                    </option>
                    <option value="UE" selected>Unité d'enseignement</option>
                    <option value="AAT">
                        acquis d'apprentissages terminaux
                    </option>
                    <option value="AAV">acquis d'apprentissages visés</option>
                    <option value="PRO">programme</option>
                </select>
            </div>

            <div class="col-md-4">
                <label> Le semestre</label>

                <select class="mr-2 form-control d-inline-block">
                    <option value="" selected>-- Choisir le semestre --</option>
                    <option value="UE" selected>1er semestre</option>
                    <option value="EC">2ème semestre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label> Faisant partie du programme</label>

                <select
                    class="mr-2 form-control d-inline-block"
                    v-model="formFilter.program"
                >
                    <option selected value="all">
                        -- Tout les programmes --
                    </option>
                    <option v-for="prog in this.prog" :value="prog.id">
                        {{ prog.name }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="form-check d-inline-block align-middle mr-3 ml-3">
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
                    @click="$emit('isModalExportVisible', true)"
                    class="align-bottom btn btn-success"
                >
                    exporter sous .csv
                </button>
            </div>
            <div class="col-md-6 text-right">
                <button type="submit" class="align-bottom btn btn-primary">
                    rechercher
                </button>
            </div>
        </div>
    </form>
</template>
<script>
import axios from "axios";

export default {
    name: "FormFilter",
    data() {
        return {
            formFilter: {
                displayElement: "UE",
                // autres filtres
            },
            prog: [],
        };
    },
    methods: {
        submitFormFilter() {
            this.$emit("submit", this.formFilter);
        },
        async loadProgram() {
            console.log("r");
            const response = await axios.get("pro/get");
            this.prog = response.data;
            console.log(response.data);
        },
    },
    mounted() {
        this.loadProgram();
    },
};
</script>
