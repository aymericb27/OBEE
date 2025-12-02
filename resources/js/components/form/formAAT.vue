<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container">
        <form
            @submit.prevent="saveProgram"
            class="text-center border p-4 rounded bg-white"
        >
            <h3 class="primary_color mb-4">
                Création d'un acquis d'apprentissage terminal
            </h3>
            <div class="mb-3">
                <input
                    placeholder="Libellé de l'acquis d'apprentissage terminal"
                    type="text"
                    v-model="form.name"
                    class="form-control w-50 m-auto"
                    required
                />
            </div>
            <div class="form-group mb-3  mb-3">
                <textarea
                    class="form-control w-75 m-auto"
                    rows="3"
					placeholder="description"
                    v-model="form.description"
                ></textarea>
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
        AATToEdit: { type: Object, default: null },
    },

    data() {
        return {
            form: {
                id: null,
                name: "",
                description: "",
            },
        };
    },

    mounted() {
        // mode édition
        if (this.AATToEdit) {
            this.form = { ...this.AATToEdit };
        }
    },

    methods: {
        async saveProgram() {
            const url = this.form.id
                ? "/aat/update"
                : "/aat/store";

            const response = await axios.post(url, this.form);
            // ✅ Redirection avec message (query param)
            this.$router.push({
                name: "levels",
                params: { id: response.data.id },
                query: { message: response.data.message },
            });
        },
    },
};
</script>
