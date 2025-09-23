<template>
    <div v-if="show" class="p-4 border mt-3 rounded bg-light">
        <form @submit.prevent="submitForm" class="w-75">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Nom de l'unité d'enseignement"
                        v-model="name"
                        id="nom"
                        required
                    />
                </div>
                <div class="col-md-3">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="code"
                        v-model="code"
                        id="code"
                        required
                    />
                </div>
                <div class="col-md-3">
                    <input
                        type="number"
                        class="form-control"
                        placeholder="ects"
                        v-model="ects"
                        id="ects"
                        required
                    />
                </div>
            </div>
            <div class="mb-3">
                <textarea
                    class="form-control"
                    v-model="description"
                    placeholder="description"
                    id="description"
                    required
                >
                </textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                Ajouter l'unité d'enseignement
            </button>
        </form>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        show: Boolean,
        csrf: String,
        route: String,
    },
    data() {
        return {
            name: "",
        };
    },
    methods: {
        async submitForm() {
            try {
                await axios.post(this.route, {
                    name: this.name,
                    description: this.description,
                    code: this.code,
                    ects: this.ects,
                    _token: this.csrf,
                });

                this.name = "";
                this.description = "";
                this.code = "";
                this.ects = "";
                this.$emit("submitted");
                this.$emit("refresh");
            } catch (error) {
                console.error(error);
                alert("Erreur lors de l'ajout de l'UE.");
            }
        },
    },
};
</script>
