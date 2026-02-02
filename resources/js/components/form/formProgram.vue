<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left"></i> Retour
        </a>
    </div>
    <div class="container">
        <div v-if="$route.query.message" class="alert alert-success mt-3">
            <i
                class="fa-solid fa-check green mr-2"
                style="color: darkgreen"
            ></i>
            <span> {{ $route.query.message }} </span>
        </div>
        <form @submit.prevent="saveProgram" class="border p-4 rounded bg-white">
            <h3 class="primary_color mb-4 text-center">
                {{ form.id ? "Modification" : "Création" }} d'un programme
            </h3>
            <div class="row mb-4">
                <div class="col-md-3">
                    <h5 class="primary_color">Nombre de semestres</h5>
                    <select
                        v-model="form.semestre"
                        class="form-control m-auto"
                        required
                    >
                        <option :value="2">2 semestres</option>
                        <option :value="4">4 semestres</option>
                        <option :value="6">6 semestres</option>
                        <option :value="8">8 semestres</option>
                        <option :value="10">10 semestres</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <h5 class="primary_color">
                        Intitulé du programme
                        <strong class="text-danger">*</strong>
                    </h5>

                    <input
                        placeholder="Libellé du programme"
                        type="text"
                        v-model="form.name"
                        class="form-control m-auto"
                        required
                    />
                </div>
            </div>

            <div class="mb-4" v-if="form.semestre">
                <h5 class="primary_color mb-3">
                    Répartition des crédits par semestre
                </h5>

                <div
                    v-for="n in Number(form.semestre)"
                    :key="n"
                    class="row align-items-center mb-2"
                >
                    <div class="col-md-3 text-end">
                        <h6 class="secondary_color">
                            Semestre {{ n }}
                            <strong class="text-danger">*</strong>
                        </h6>
                    </div>
                    <div class="col-md-3 pr-0">
                        <input
                            required
                            type="number"
                            min="0"
                            class="form-control"
                            v-model.number="form.semestresCredits[n]"
                            placeholder="Crédits"
                        />
                    </div>
                </div>
            </div>
            <div class="mt-3 w-50">
                <div class="d-flex justify-content-between border-top pt-2">
                    <span
                        ><h6 class="primary_color">
                            Total crédits semestres
                        </h6></span
                    >
                    <span
                        ><h6>{{ semestreCreditsTotal }}</h6></span
                    >
                </div>
            </div>

            <button class="btn btn-primary mt-3">
                {{ form.id ? "Modifier le" : "Créer le" }} Programme
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
                ects: "",
                semestre: 6, // valeur par défaut
                semestresCredits: {}, // { 1: 30, 2: 30, ... }
            },
        };
    },
    watch: {
        "form.semestre"(newVal) {
            const count = Number(newVal);
            const credits = { ...this.form.semestresCredits };

            // créer les semestres manquants
            for (let i = 1; i <= count; i++) {
                if (credits[i] === undefined) credits[i] = 0;
            }

            // supprimer les semestres en trop
            Object.keys(credits).forEach((key) => {
                if (key > count) delete credits[key];
            });

            this.form.semestresCredits = credits;
        },
        semestreCreditsTotal(val) {
            this.form.ects = val;
        },
    },

    mounted() {
        // mode édition
        if (this.id) {
            this.form.id = this.id;
            this.loadPRO();
        }
    },
    computed: {
        semestreCreditsTotal() {
            const credits = this.form.semestresCredits || {};
            return Object.values(credits).reduce(
                (sum, v) => sum + (Number(v) || 0),
                0,
            );
        },
    },

    methods: {
        async loadPRO() {
            try {
                const response = await axios.get("/pro/get/detailed", {
                    params: { id: this.id },
                });

                const data = response.data;

                // transformer la liste semester[] en objet { 1: ects, 2: ects }
                const semestresCredits = {};
                data.semester.forEach((s) => {
                    semestresCredits[s.semester] = s.ects;
                });

                this.form = {
                    id: data.id,
                    name: data.name,
                    ects: data.ects,
                    semestre: data.semester.length, // nombre de semestres
                    semestresCredits,
                };
            } catch (error) {
                console.log(error);
            }
        },

        async saveProgram() {
            console.log(this.form);
            const url = this.form.id
                ? "/programme/update"
                : "/programme/create";

            const response = await axios.post(url, this.form);
            // ✅ Redirection avec message (query param)
            this.$router.push({
                name: "pro-detail",
                params: { id: response.data.id },
                query: { message: response.data.message },
            });
        },
    },
};
</script>
