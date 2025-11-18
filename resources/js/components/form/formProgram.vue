<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left"></i> Retour
        </a>
    </div>
    <div class="container">
        <form
            @submit.prevent="saveProgram"
            class="text-center border p-4 rounded bg-white"
        >
            <h3 class="primary_color mb-4">Création d'un programme</h3>
            <div class="mb-3">
                <input
                    placeholder="Libellé du programme"
                    type="text"
                    v-model="form.name"
                    class="form-control w-50 m-auto"
                    required
                />
            </div>

            <div class="mb-3">
                <input
                    placeholder="Nombre de crédit"
                    type="number"
                    v-model="form.ects"
                    class="form-control w-50 m-auto"
                    min="0"
                    required
                />
            </div>

            <div class="mb-3">
                <input
                    placeholder="Nombre de semestre"
                    type="number"
                    v-model="form.semestre"
                    class="form-control w-50 m-auto"
                    min="1"
                    required
                />
            </div>

            <button class="btn btn-primary">
                {{ form.id ? "Modifier le" : "Créer le" }} Programme
            </button>
        </form>
    </div>
</template>
<script>
import axios from "axios";

export default {
    props: {
        programToEdit: { type: Object, default: null },
    },

    data() {
        return {
            form: {
                id: null,
                name: "",
                ects: "",
                semestre: "",
            },
        };
    },

    mounted() {
        // mode édition
        if (this.programToEdit) {
            this.form = { ...this.programToEdit };
        }
    },

    methods: {
        async saveProgram() {
            const url = this.form.id
                ? "/programme/update"
                : "/programme/create";

            const response = await axios.post(url, this.form);
            // ✅ Redirection avec message (query param)
            this.$router.push({
                name: "tree",
                params: { id: response.data.id },
                query: { message: response.data.message },
            });
        },
    },
};
</script>
