<template>
    <div v-if="show" class="p-3 border mt-2 rounded bg-light">
        <form @submit.prevent="submitForm" class="w-75">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input
                        placeholder="Nom de l'élément constitutif"
                        type="text"
                        class="form-control"
                        v-model="nom"
                        id="ECName"
                        required
                    />
                </div>
                <div class="col-md-3">
                    <input
                        placeholder="code"
                        type="text"
                        class="form-control"
                        v-model="code"
                        id="code"
                        required
                    />
                </div>
                <div class="col-md-3">
                    <input
                        placeholder="nombre de cours total"
                        type="text"
                        class="form-control"
                        v-model="volume_horaire"
                        id="volume_horaire"
                        required
                    />
                </div>
            </div>
            <div class="row mb-3 ">
                <div class="col-md-6">
                    <select
                        v-model="selectedUE"
                        class="form-select form-control"
                        required
                    >
                        <option disabled value="">
                            -- Choisir une unité d'enseignement --
                        </option>
                        <option v-for="ue in ues" :key="ue.id" :value="ue.id">
                            {{ ue.nom }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <textarea
                        placeholder="description"
                        class="form-control"
                        v-model="description"
                        id="description"
                        required
                    >
                    </textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                Ajouter l'élément constitutif
            </button>
						<button onclick="toggle">

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
            nom: "",
            selectedUE: "", // id de l’UE choisi
            code: "",
            volume_horaire: "",
            description: "",
            ues: [], // liste des UE
        };
    },
    methods: {
        async loadUEs() {
            try {
                const response = await axios.get("/UEGet");
                console.log(response.data);
                this.ues = response.data;
                console.log("ues chargé");
            } catch (error) {
                console.error("Erreur chargement UE :", error);
            }
        },
        async submitForm() {
            try {
                await axios.post(this.route, {
                    nom: this.nom,
                    code: this.code,
                    selectedUE: this.selectedUE,
                    volume_horaire: this.volume_horaire,
                    description: this.description,
                    _token: this.csrf,
                });

                this.nom = "";
                this.$emit("submitted");
                this.$emit("refresh");
            } catch (error) {
                console.error(error);
                alert("Erreur lors de l'ajout de l'EC.");
            }
        },
    },
    mounted() {
        this.loadUEs();
    },

    watch: {
        show(newVal) {
            if (newVal) {
                this.loadUEs();
            }
        },
    },
};
</script>
