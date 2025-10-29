<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="container">
            <div v-if="formErrors" class="alert alert-danger mt-3">
                <i
                    class="fa-solid fa-triangle-exclamation mr-2"
                    style="color: crimson; font-size: 24px;"
                ></i>
                <span> {{ formErrors }} </span>
            </div>
            <form @submit.prevent="submitFormElementConstitutif">
                <div class="p-4 border rounded bg-light mt-3">
                    <div class="mb-4 d-flex align-items-center">
                        <h3
                            class="box_code UE pl-2 d-inline-block pr-2 mr-2 mb-0"
                        >
                            {{ ue.code }}
                        </h3>
                        <span class="d-inline-block w-75 flex-grow-1">
                            <input
                                type="text"
                                class="form form-control"
                                v-model="ue.name"
                            />
                        </span>
                    </div>
                    <p class="mb-4">
                        <quill-editor
                            v-model:content="ue.description"
                            content-type="html"
                            theme="snow"
                        ></quill-editor>
                    </p>
                    <div class="row mb-4 align-items-center">
                        <label class="col-md-2 mb-0 primary_color"
                            >Nombre d'ects :
                        </label>
                        <input
                            type="number"
                            v-model="ue.ects"
                            class="col-md-2 form form-control"
                        />
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <span class="primary_color">date de début :</span>
                            <input
                                type="date"
                                class="form form-control mt-2"
                                v-model="ue.date_begin"
                            />
                        </div>
                        <div class="col-md-4">
                            <span class="primary_color">date de fin :</span>
                            <input
                                type="date"
                                class="form form-control mt-2"
                                v-model="ue.date_end"
                            />
                        </div>
                    </div>
                    <div class="listComponent mb-4">
                        <div class="mb-2">
                            <h5 class="d-inline-block primary_color">
                                Liste des acquis d'apprentissages visé
                            </h5>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-9 p-2">Nom</div>
                        </div>

                        <div
                            v-for="(aav, index) in ue.aavvise"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeAAVise(aav.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                            <div class="col-md-10 p-2">{{ aav.name }}</div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary mt-2"
                            @click="openModal('aavvise')"
                        >
                            ajouter un acquis d'apprentissage visé
                        </button>
                    </div>
                    <div class="listComponent mb-4">
                        <div class="mb-2">
                            <h5 class="d-inline-block primary_color">
                                Liste des prérequis
                            </h5>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-9 p-2">Nom</div>
                        </div>

                        <div
                            v-for="(aav, index) in ue.aavprerequis"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeAAVprerequis(aav.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                            <div class="col-md-10 p-2">{{ aav.name }}</div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary mt-2"
                            @click="openModal('aavprerequis')"
                        >
                            ajouter un prérequis
                        </button>
                    </div>
                    <button
                        type="button"
                        @click="$router.back()"
                        class="mr-2 btn btn-secondary"
                    >
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Modifier l'unité d'enseignement
                    </button>
                </div>
            </form>
        </div>
    </div>
    <modalList
        v-if="showModal"
        :visible="showModal"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aavToExclude"
        type="AAV"
        @close="showModal = false"
        @selected="handleSelected"
    />
</template>

<script>
import modalList from "../modalList.vue";
import axios from "axios";
import { QuillEditor } from "@vueup/vue-quill";

export default {
    props: {
        csrfform: String,
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: { modalList },

    data() {
        return {
            activeForm: null,
            showModal: false,
            modalRoute: "",
            modalTitle: "",
            aavToExclude: [],
            modalTarget: "", // 'aavvise' ou 'aavprerequis'
            ue: {
                aavvise: [],
                aavprerequis: [],
                name: "",
                description: "",
                code: "",
                ects: "",
                aavs: {},
                ecs: {},
            },
            formErrors: null,
        };
    },
    methods: {
        openModal(type) {
            this.modalTarget = type;
            this.modalTitle =
                type === "aavvise"
                    ? "Ajouter des acquis d’apprentissage visés"
                    : "Ajouter des prérequis";
            this.modalRoute = "/aavs/get";
            this.aavToExclude =
                type === "aavvise" ? this.ue.aavvise : this.ue.aavprerequis;
            this.showModal = true;
        },
        handleSelected(selectedItems) {
            if (this.modalTarget === "aavvise") {
                this.ue.aavvise.push(...selectedItems);
            } else if (this.modalTarget === "aavprerequis") {
                this.ue.aavprerequis.push(...selectedItems);
            }
        },
        async submitFormElementConstitutif() {
            try {
                const response = await axios.put("/ue/update", {
                    id: this.ue.id,
                    name: this.ue.name,
                    ects: this.ue.ects,
                    description: this.ue.description,
                    date_begin: this.ue.date_begin,
                    date_end: this.ue.date_end,
                    aavprerequis: this.ue.aavprerequis,
                    aavvise: this.ue.aavvise,
                });

                // ✅ Si tout s’est bien passé
                if (response.data.success) {
                    // ✅ Redirection avec message (query param)
                    this.$router.push({
                        name: "ue-detail",
                        params: { id: this.ue.id },
                        query: { message: "Élément bien modifié" },
                    });
                }
            } catch (error) {
                // ⚠️ Gestion des erreurs
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.errors
                ) {
                    // Laravel renvoie souvent { errors: { name: ['Le champ name est requis.'], ... } }
                    this.formErrors = Object.values(error.response.data.errors)
                        .flat()
                        .join(" ");
                } else if (error.response && error.response.data.message) {
                    this.formErrors = error.response.data.message;
                } else {
                    this.formErrors = "Une erreur inconnue est survenue.";
                }
                console.error(error);
            }
        },
        async loadUE() {
            try {
                const responseUE = await axios.get("/UEGet/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue = responseUE.data;
                const responseAAVvise = await axios.get("/ue/aavvise/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue.aavvise = responseAAVvise.data;
                const responseAAVprerequis = await axios.get(
                    "/ue/aavprerequis/get",
                    {
                        params: {
                            id: this.id,
                        },
                    }
                );
                this.ue.aavprerequis = responseAAVprerequis.data;
                console.log(responseAAVprerequis);
            } catch (error) {
                console.log(error);
            }
        },
        removeAAVise(aavId) {
            if (!this.ue || !Array.isArray(this.ue.aavvise)) return;

            // Option 1: mutation (préserve la même référence d'array)
            const i = this.ue.aavvise.findIndex((a) => a.id === aavId);
            if (i !== -1) this.ue.aavvise.splice(i, 1);
        },
        removeAAVprerequis(aavId) {
            if (!this.ue || !Array.isArray(this.ue.aavvise)) return;

            // Option 1: mutation (préserve la même référence d'array)
            const i = this.ue.aavprerequis.findIndex((a) => a.id === aavId);
            if (i !== -1) this.ue.aavprerequis.splice(i, 1);
        },
    },

    mounted() {
        this.loadUE();
    },
};
</script>
