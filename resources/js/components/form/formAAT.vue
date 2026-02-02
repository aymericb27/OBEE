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
            <div class="mb-3">
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
            <div class="form-group mb-3">
                <h5 class="primary_color">
                    description
                </h5>
                <quill-editor
                    v-model:content="form.description"
                    content-type="html"
                    theme="snow"
                    style="height: 175px"
                    required
                ></quill-editor>
            </div>
            <div class="mb-3">
                <h5 class="primary_color">
                    Nombre de niveau de contribution
                    <strong class="text-danger">*</strong>
                </h5>
                <select
                    v-model="form.level_contribution"
                    class="form-control w-50"
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
            form: {
                id: null,
                name: "",
                description: "",
                level_contribution: 3,
            },
        };
    },

    mounted() {
        // mode édition
        if (this.id) {
            this.form.id = this.id;
            this.loadAAT();
        }
    },

    methods: {
        async loadAAT() {
            const response = await axios.get("/aat/get/detailed", {
                params: {
                    id: this.id,
                },
            });
            this.form = response.data;
        },
        async saveProgram() {
            const url = this.form.id ? "/aat/update" : "/aat/store";
            const route = this.form.id ? "aat-detail" : "levels";

            const response = await axios.post(url, this.form);
            // ✅ Redirection avec message (query param)
            this.$router.push({
                name: route,
                params: { id: response.data.id },
                query: { message: response.data.message },
            });
        },
    },
};
</script>
