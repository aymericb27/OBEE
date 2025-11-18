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
                    style="color: crimson; font-size: 24px"
                ></i>
                <span> {{ formErrors }} </span>
            </div>
            <form @submit.prevent="submitFormElementConstitutif">
                <div class="p-4 border rounded bg-light mt-3">
                    <div class="mb-4 d-flex align-items-center">
                        <span
                            class="pr-2 mr-2 mb-0"
                        >
                            <input
                                type="text"
                                class="form form-control"
                                v-model="ue.ects"
                                placeholder="Nombre d'ects"
                                required
                            />
                        </span>
                        <span class="w-75 flex-grow-1">
                            <input
                                type="text"
                                class="form form-control"
                                placeholder="libellé"
                                v-model="ue.name"
                                required
                            />
                        </span>
                    </div>
                    <p class="mb-4">
                        <quill-editor
                            v-model:content="ue.description"
                            placeholder="description"
                            content-type="html"
                            theme="snow"
                            style="height: 175px"
                            required
                        ></quill-editor>
                    </p>
                    <div class="listComponent mb-4">
                        <div class="mb-2">
                            <h5 class="d-inline-block primary_color">
                                Liste des programmes liés
                            </h5>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1 p-2"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-8 p-2">Libellé</div>
                            <div class="col-md-2 p-2">Semestre</div>
                        </div>

                        <div
                            v-for="(pro, index) in ue.pro"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removePRO(pro.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 PRO">{{ pro.code }}</div>
                            <div class="col-md-8 p-2">{{ pro.name }}</div>
                            <div class="col-md-2 p-2"><input type="number" class="form form-control"></input></div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary mt-2"
                            @click="openModalPro()"
                        >
                            ajouter un programme
                        </button>
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
                    <button
                        type="submit"
                        class="btn btn-primary"
                        v-if="this.id"
                    >
                        Modifier l'unité d'enseignement
                    </button>
                    <button type="submit" class="btn btn-primary" v-else>
                        Créer l'unité d'enseignement
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
    <modalList
        v-if="showModalPro"
        :visible="showModalPro"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="proToExclude"
        type="PRO"
        @close="showModalPro = false"
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
        },
        isCreate: {
            type: Boolean,
            default: false,
        },
		SemesterID : {type: Number},
		ProgramID: {type: Number},
    },
    components: { modalList },

    data() {
        return {
            activeForm: null,
            showModal: false,
            showModalPro: false,
            modalRoute: "",
            modalTitle: "",
            proToExclude: [],
            aavToExclude: [],
            modalTarget: "", // 'aavvise' ou 'aavprerequis'
            ue: {
                aavvise: [],
                aavprerequis: [],
                pro: [],
                name: "",
                description: "",
                semestre: 1,
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
        openModalPro() {
            this.modalTarget = "pro";
            this.modalRoute = "/pro/get";
            this.modalTitle = "Ajouter des programmes";
            this.proToExclude = this.ue.pro;
            this.showModalPro = true;
        },
        handleSelected(selectedItems) {
            if (this.modalTarget === "aavvise") {
                this.ue.aavvise.push(...selectedItems);
            } else if (this.modalTarget === "aavprerequis") {
                this.ue.aavprerequis.push(...selectedItems);
            } else if (this.modalTarget === "pro") {
                this.ue.pro.push(...selectedItems);
            }
        },
        async submitFormElementConstitutif() {
            try {
                if (this.id) {
                    const response = await axios.put("/ue/update", {
                        id: this.ue.id,
                        code: this.ue.code,
                        name: this.ue.name,
                        ects: this.ue.ects,
                        description: this.ue.description,
                        aavprerequis: this.ue.aavprerequis,
                        aavvise: this.ue.aavvise,
                        pro: this.ue.pro,
                        semestre: this.ue.semestre,
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
                } else {
                    const response = await axios.post("/ue/store", {
                        name: this.ue.name,
                        code: this.ue.code,
                        ects: this.ue.ects,
                        description: this.ue.description,
                        aavprerequis: this.ue.aavprerequis,
                        aavvise: this.ue.aavvise,
                        pro: this.ue.pro,
                        semestre: this.ue.semestre,
                    });
                    console.log(response.data);
                    // ✅ Si tout s’est bien passé
                    if (response.data.success) {
                        // ✅ Redirection avec message (query param)
                        this.$router.push({
                            name: "ue-detail",
                            params: { id: response.data.id },
                            query: { message: response.data.message },
                        });
                    }
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
                const responsePro = await axios.get("/ue/pro/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue.pro = responsePro.data;
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
            } catch (error) {
                console.log(error);
            }
        },
        removePRO(proId) {
            if (!this.ue || !Array.isArray(this.ue.aavvise)) return;

            // Option 1: mutation (préserve la même référence d'array)
            const i = this.ue.pro.findIndex((a) => a.id === proId);
            if (i !== -1) this.ue.pro.splice(i, 1);
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
		    console.log("Semestre :", this.$route.query.semesterID);
    console.log("Programme :", this.$route.query.programID);
        if (this.id) {
            this.loadUE();
        }
    },
};
</script>
