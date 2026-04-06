<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container">
        <form @submit.prevent="saveProgram" class="border p-4 rounded bg-white">
            <h3 class="primary_color mb-4 text-center">
                {{ form.id ? "Modification" : "Création" }} d'un acquis
                d'apprentissage terminal
            </h3>
            <div class="row">
                <div class="mb-3 col-md-3">
                    <h5 class="primary_color">Sigle</h5>
                    <input
                        type="text"
                        v-model="form.code"
                        class="form-control"
                        placeholder="Ex: AAT001"
                    />
                </div>
                <div class="mb-3 col-md-9">
                    <h5 class="primary_color">
                        Libellé
                        <strong class="text-danger">*</strong>
                    </h5>
                    <input
                        type="text"
                        v-model="form.name"
                        class="form-control"
                        required
                    />
                </div>
            </div>
            <div class="row">
                <div class="mb-3 col-md-7">
                    <h5 class="primary_color">
                        Programme
                        <strong class="text-danger">*</strong>
                    </h5>
                    <select
                        v-model.number="form.fk_programme"
                        class="form-control"
                        required
                    >
                        <option :value="null" disabled>
                            Sélectionner un programme
                        </option>
                        <option
                            v-for="programme in listProgramme"
                            :key="programme.id"
                            :value="programme.id"
                        >
                            {{ programme.code }} - {{ programme.name }}
                        </option>
                    </select>
                </div>
                <div class="mb-3 col-md-5">
                    <h5 class="primary_color">
                        Nombre de niveaux de contribution
                        <strong class="text-danger">*</strong>
                    </h5>
                    <select
                        v-model="form.level_contribution"
                        class="form-control"
                        required
                    >
                        <option :value="3">3</option>
                        <option :value="4">4</option>
                        <option :value="5">5</option>
                        <option :value="6">6</option>
                        <option :value="7">7</option>
                        <option :value="8">8</option>
                        <option :value="9">9</option>
                        <option :value="10">10</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-3">
                <h5 class="primary_color">description</h5>
                <quill-editor
                    v-model:content="form.description"
                    content-type="html"
                    theme="snow"
                    style="height: 175px"
                    required
                ></quill-editor>
            </div>

            <button class="btn btn-primary">
                {{ form.id ? "Modifier l'" : "Créer l'" }} acquis
                d'apprentissage terminal
            </button>
        </form>
    </div>
</template>
<script>
import axios from "axios";

export default {
    props: {
        id: {
            type: [String, Number],
        },
    },

    data() {
        return {
            listProgramme: [],
            form: {
                id: null,
                code: "",
                name: "",
                description: "",
                level_contribution: 3,
                fk_programme: null,
            },
        };
    },

    async mounted() {
        await this.loadProgrammes();

        // mode edition
        if (this.id) {
            this.form.id = this.id;
            await this.loadAAT();
        } else {
            this.form.fk_programme = this.getDefaultProgrammeId();
        }
    },

    methods: {
        getDefaultProgrammeId() {
            const routeProgramId = Number(this.$route.query.programID);
            if (Number.isInteger(routeProgramId) && routeProgramId > 0) {
                return routeProgramId;
            }

            if (this.listProgramme.length === 1) {
                return this.listProgramme[0].id;
            }

            return null;
        },
        async loadProgrammes() {
            const response = await axios.get("/pro/get");
            this.listProgramme = response.data || [];
        },
        async loadAAT() {
            const response = await axios.get("/aat/get/detailed", {
                params: {
                    id: this.id,
                },
            });
            this.form = {
                ...response.data,
                fk_programme:
                    response.data?.fk_programme ?? this.getDefaultProgrammeId(),
            };
        },
        async saveProgram() {
            const url = this.form.id ? "/aat/update" : "/aat/store";
            const route = this.form.id ? "aat-detail" : "levels";

            const response = await axios.post(url, this.form);
            // Redirection avec message (query param)
            this.$router.push({
                name: route,
                params: { id: response.data.id },
                query: { message: response.data.message },
            });
        },
    },
};
</script>
