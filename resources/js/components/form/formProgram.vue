<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left"></i> Retour
        </a>
    </div>
    <div class="container pb-3">
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
            <div class="mt-3 mb-5 w-50">
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
            <div class="listComponent mb-5">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        Liste des prérequis
                    </h5>
                    <button
                        type="button"
                        class="btn btn-primary ml-2 mb-2"
                        @click="openModalPrerequis()"
                    >
                        ajouter un prérequis
                    </button>
                </div>
                <div class="border rounded">
                    <div class="row border-bottom m-0">
                        <div class="col-md-1"></div>
                        <div class="col-md-1 p-2">Code</div>
                        <div class="col-md-10 p-2">Nom</div>
                    </div>
                    <div
                        v-if="!form.aavprerequis.length"
                        class="p-2 text-center"
                    >
                        aucune donnée à afficher
                    </div>
                    <div
                        v-else
                        v-for="(aav, index) in form.aavprerequis"
                        class="row m-0"
                        :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                    >
                        <div class="col-md-1 text-right p-2">
                            <i
                                @click="removeElement('aavprerequis', aav.id)"
                                class="text-danger fa fa-close pr-0"
                                style="cursor: pointer"
                            ></i>
                        </div>
                        <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                        <div class="col-md-10 p-2">{{ aav.name }}</div>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary mt-3">
                {{ form.id ? "Modifier le" : "Créer le" }} Programme
            </button>
        </form>
    </div>
    <div
        v-if="showModalCreateAAV"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
    >
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title primary_color">
                        Introduire un prérequis
                    </h5>
                    <button
                        type="button"
                        class="close btn"
                        @click="closeModalAAV"
                    >
                        <span>&times;</span>
                    </button>
                </div>

                <!-- BODY -->
                <div class="modal-body p-4">
                    <div v-if="formAAvErrors" class="alert alert-danger mt-3">
                        <i
                            class="fa-solid fa-triangle-exclamation mr-2"
                            style="color: crimson; font-size: 24px"
                        ></i>
                        <span> {{ formAAvErrors }} </span>
                    </div>
                    <div class="form-group mb-3">
                        <h6 class="primary_color">
                            Libellé
                            <strong class="text-danger">*</strong>
                        </h6>
                        <input
                            type="text"
                            class="form-control"
                            required
                            v-model="aavForm.name"
                        />
                    </div>
                    <div class="form-group mb-3">
                        <h6 class="primary_color">Description</h6>
                        <textarea
                            class="form-control"
                            rows="3"
                            v-model="aavForm.description"
                        ></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <h6 class="primary_color">
                            Acquis d'apprentissage Terminal
                        </h6>
                        <select
                            class="form-control"
                            v-model="aavForm.selectedAATId"
                            @change="addAAT"
                        >
                            <option value="" disabled>
                                — Sélectionner un AAT —
                            </option>
                            <option
                                v-for="aat in listAAT"
                                :key="aat.id"
                                :value="aat.id"
                                :disabled="isAATAlreadySelected(aat.id)"
                            >
                                {{ aat.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="aavForm.aatSelected.length" class="mt-4">
                        <h6 class="mb-3 primary_color">AAT sélectionnés</h6>

                        <div
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                            v-for="(aat, index) in aavForm.aatSelected"
                            :key="aat.id"
                            class="d-flex align-items-center rounded p-2"
                        >
                            <!-- SUPPRIMER -->
                            <button
                                class="btn btn-sm me-3"
                                @click="removeAAT(aat.id)"
                            >
                                <i
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </button>

                            <!-- NOM -->
                            <div class="flex-grow-1">
                                {{ aat.name }}
                            </div>
                            <!-- CONTRIBUTION -->
                            <select
                                class="form-control w-25 ms-3"
                                v-model="aat.contribution"
                            >
                                <option
                                    v-for="level in aat.level_contribution"
                                    :key="level"
                                    :value="level"
                                >
                                    {{ level }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button
                        class="btn btn-secondary"
                        type="button"
                        @click="closeModalAAV"
                    >
                        Annuler
                    </button>
                    <button
                        class="btn btn-primary"
                        type="submit"
                        @click="submitAAV"
                    >
                        Créer
                    </button>
                </div>
            </div>
        </div>

        <!-- BACKDROP -->
        <div class="modal-backdrop fade show"></div>
    </div>
    <modalList
        v-if="showModalPrerequis"
        :visible="showModalPrerequis"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aavPrerequisToExclude"
        :btnAddModal="true"
        @btnAddElementModal="handleNewAAV"
        btnAddElementMessage="Créer un prérequis"
        type="AAV"
        @close="showModalPrerequis = false"
        @selected="handleSelected"
    />
</template>
<script>
import modalList from "../modalList.vue";

import axios from "axios";

export default {
    components: { modalList },

    props: {
        id: {
            type: [String, Number],
        },
    },
    data() {
        return {
            formAAvErrors: null,
            formErrors: null,
            showModalPrerequis: false,
            showModalCreateAAV: false,
            listAAT: [],
            form: {
                aavprerequis: [],
                id: null,
                name: "",
                ects: "",
                semestre: 6, // valeur par défaut
                semestresCredits: {}, // { 1: 30, 2: 30, ... }
            },
            aavForm: {
                selectedAATId: "",
                name: "",
                description: "",
                aatSelected: [], // [{ id, name, contribution }]
                contribution: "",
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
        addAAT() {
            if (!this.aavForm.selectedAATId) return;

            const aat = this.listAAT.find(
                (a) => a.id === this.aavForm.selectedAATId,
            );

            if (!aat) return;

            this.aavForm.aatSelected.push({
                id: aat.id,
                name: aat.name,
                contribution: 1, // valeur par défaut
                level_contribution: aat.level_contribution,
            });

            this.aavForm.selectedAATId = "";
        },
        async submitAAV() {
            this.formAAvErrors = null;
            if (!this.aavForm.name) {
                this.formAAvErrors = "Le champs libellé doit être présent";
                return;
            }
            const payload = {
                name: this.aavForm.name,
                description: this.aavForm.description,
                aat: this.aavForm.aatSelected.map((a) => ({
                    id: a.id,
                    contribution: a.contribution,
                })),
            };
            try {
                const response = await axios.post("/aav/store", payload);
                const createdAAV = response.data.aav;

                this.form.aavprerequis.push(createdAAV);

                this.closeModalAAV();
            } catch (error) {
                console.error(error);
                alert("Erreur lors de la création.");
            }
        },
        removeAAT(id) {
            this.aavForm.aatSelected = this.aavForm.aatSelected.filter(
                (a) => a.id !== id,
            );
        },
        isAATAlreadySelected(id) {
            return this.aavForm.aatSelected.some((a) => a.id === id);
        },
        handleSelected(selectedItems) {
            if (this.modalTarget === "aavprerequis") {
                this.form.aavprerequis.push(...selectedItems);
            }
        },
        removeElement(type, id) {
            if (
                !this.form ||
                !this.form[type] ||
                !Array.isArray(this.form[type])
            )
                return;

            const index = this.form[type].findIndex((item) => item.id === id);

            if (index !== -1) {
                this.form[type].splice(index, 1);
            }
        },
        handleNewAAV() {
            this.loadAAT();
            this.showModalCreateAAV = true;
        },
        async loadAAT() {
            const response = await axios.get("/aat/get");
            this.listAAT = response.data;
        },
        openModalPrerequis() {
            this.modalTarget = "aavprerequis";
            this.modalRoute = "/aav/pro/prerequis/get";
            this.modalTitle = "Ajouter des prérequis";
            this.aavPrerequisToExclude = [...this.form.aavprerequis];
            this.showModalPrerequis = true;
        },
        closeModalAAV() {
            this.showModalCreateAAV = false;
            this.resetAAVForm();
        },

        resetAAVForm() {
            this.aavForm = {
                selectedAATId: "",
                name: "",
                description: "",
                aatSelected: [],
                contribution: "",
            };
        },
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

				const prerequis = await axios.get('/pro/pre/get', {
					params: {id : this.id},
				})


                this.form = {
                    id: data.id,
                    name: data.name,
                    ects: data.ects,
                    semestre: data.semester.length, // nombre de semestres
                    aavprerequis: prerequis.data,
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
