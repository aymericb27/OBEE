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
                Modification d'un acquis d'apprentissage visé
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
            <div class="form-group mb-3 mb-3">
                <textarea
                    class="form-control w-75 m-auto"
                    rows="3"
                    placeholder="description"
                    v-model="form.description"
                ></textarea>
            </div>
            <button class="btn btn-primary">
                Modifier l'acquis d'apprentissage visé
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
            },
        };
    },

    mounted() {
        this.loadAAV();
        // mode édition
        if (this.AATToEdit) {
            this.form = { ...this.AATToEdit };
        }
    },

    methods: {
        async loadAAV() {
            try {
                const response = await axios.get("/aav/get/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.form = response.data;
            } catch (error) {
                console.log(error);
            }
        },
        async saveProgram() {
            const url = this.form.id ? "/aat/update" : "/aat/store";

            const response = await axios.post("aav/update", this.form);
            // ✅ Redirection avec message (query param)
            this.$router.push({
                name: "aav-detail",
                params: { id: response.data.id },
                query: { message: response.data.message },
            });
        },
    },
};
</script>
