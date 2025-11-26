<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()" class="primary_color">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="container p-4">
            <div v-if="formErrors" class="alert alert-danger mt-3">
                <i
                    class="fa-solid fa-triangle-exclamation mr-2"
                    style="color: crimson; font-size: 24px"
                ></i>
                <span> {{ formErrors }} </span>
            </div>
            <form @submit.prevent="submitFormUE">
                <div class="p-4 border rounded bg-white mt-3">
                    <h3 class="mb-4 primary_color">
                        Création d'une unité d'enseignement
                    </h3>
                    <div class="mb-3" v-if="$route.query.UEParentId">
                        Cette unité d'enseignement sera l'élément constitutif de
                        <span class="UE">{{ ueParent.code }}</span>
                        <span class="ml-1 font-weight-bold secondary_color">{{
                            ueParent.name
                        }}</span>
                        <div class="mt-2 mb-3">
                            <span>Contribution : </span>
                            <select
                                class="form form-control w-25 d-inline-block ml-2"
                                v-model="ueParent.contribution"
                            >
                                <option value="1" selected>faible</option>
                                <option value="2">modéré</option>
                                <option value="3">forte</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center">
                        <span class="pr-2 mr-2 mb-0 w-75 flex-grow-1">
                            <input
                                type="text"
                                class="form form-control"
                                placeholder="libellé"
                                v-model="ue.name"
                                required
                            />
                        </span>
                        <span class="">
                            <input
                                type="number"
                                class="form form-control"
                                v-model="ue.ects"
                                placeholder="Nombre d'ects"
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
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h4 class="d-inline-block primary_color">
                                Liste des acquis d'apprentissages terminaux
                                <button
                                    type="button"
                                    class="btn btn-primary ml-2 mb-2"
                                    @click="openModalTerminal()"
                                >
                                    ajouter un acquis d'apprentissage terminal
                                </button>
                            </h4>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-8 p-2">Nom</div>
                            <div class="col-md-2 p-2">Contribution</div>
                        </div>
                        <div
                            v-if="!ue.aat || !ue.aat.length"
                            class="p-2 text-center"
                        >
                            aucune donnée à afficher
                        </div>

                        <div
                            v-for="(aat, index) in ue.aat"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeElement('aat', aat.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAT">{{ aat.code }}</div>
                            <div class="col-md-8 p-2">{{ aat.name }}</div>
                            <div class="col-md-2 p-2">
                                <select
                                    class="form form-control"
                                    v-model="aat.contribution"
                                >
                                    <option value="1" selected>faible</option>
                                    <option value="2">modéré</option>
                                    <option value="3">forte</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h4 class="d-inline-block mb-0 primary_color">
                                Liste des programmes liés
                                <button
                                    type="button"
                                    class="btn btn-primary ml-2 mb-2"
                                    @click="openModalPro()"
                                >
                                    ajouter un programme
                                </button>
                            </h4>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1 p-2"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-8 p-2">Libellé</div>
                            <div class="col-md-2 p-2">Semestre</div>
                        </div>
                        <div v-if="!ue.pro.length" class="p-2 text-center">
                            aucune donnée à afficher
                        </div>

                        <div
                            v-for="(pro, index) in ue.pro"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeElement('pro', pro.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 PRO">{{ pro.code }}</div>
                            <div class="col-md-8 p-2">{{ pro.name }}</div>
                            <div class="col-md-2 p-1">
                                <input
                                    required
                                    type="number"
                                    min="1"
                                    v-model.number="pro.semester"
                                    :max="pro.nbrSemester"
                                    class="form form-control"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h4 class="d-inline-block primary_color">
                                Liste des acquis d'apprentissages visé
                                <button
                                    type="button"
                                    class="btn btn-primary ml-2 mb-2"
                                    @click="openModalVise()"
                                >
                                    ajouter un acquis d'apprentissage visé
                                </button>
                            </h4>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-9 p-2">Nom</div>
                        </div>
                        <div v-if="!ue.aavvise.length" class="p-2 text-center">
                            aucune donnée à afficher
                        </div>

                        <div
                            v-for="(aav, index) in ue.aavvise"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeElement('aavvise', aav.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                            <div class="col-md-10 p-2">{{ aav.name }}</div>
                        </div>
                    </div>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h4 class="d-inline-block primary_color">
                                Liste des prérequis
                            </h4>
                            <button
                                type="button"
                                class="btn btn-primary ml-2 mb-2"
                                @click="openModalPrerequis()"
                            >
                                ajouter un prérequis
                            </button>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-9 p-2">Nom</div>
                        </div>
                        <div
                            v-if="!ue.aavprerequis.length"
                            class="p-2 text-center"
                        >
                            aucune donnée à afficher
                        </div>
                        <div
                            v-else
                            v-for="(aav, index) in ue.aavprerequis"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="
                                        removeElement('aavprerequis', aav.id)
                                    "
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                            <div class="col-md-10 p-2">{{ aav.name }}</div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$router.back()"
                        class="mr-2 btn btn-lg btn-secondary"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="btn btn-lg btn-primary"
                        v-if="this.id"
                    >
                        Modifier l'unité d'enseignement
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary" v-else>
                        Créer l'unité d'enseignement
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- MODAL — Création AAV -->
    <!-- MODAL CRÉATION AAV -->
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
                    <h5 class="modal-title">
                        Créer un acquis d'apprentissage visé
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
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Libellé</label>
                        <input
                            type="text"
                            class="form-control"
                            required
                            v-model="aavForm.name"
                        />
                    </div>

                    <div class="form-group mb-3">
                        <label>Description</label>
                        <textarea
                            class="form-control"
                            rows="3"
                            v-model="aavForm.description"
                        ></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label>Acquis d'apprentissage Terminal</label>
                        <select
                            class="form-control"
                            required
                            v-model="aavForm.fk_AAT"
                        >
                            <option value="" disabled>
                                — Sélectionner un type —
                            </option>
                            <option
                                v-for="aat in listAAT"
                                :key="aat.id"
                                :value="aat.id"
                            >
                                {{ aat.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeModalAAV">
                        Annuler
                    </button>
                    <button class="btn btn-primary" @click="submitAAV">
                        Créer
                    </button>
                </div>
            </div>
        </div>

        <!-- BACKDROP -->
        <div class="modal-backdrop fade show"></div>
    </div>

    <modalList
        v-if="showModalVise"
        :visible="showModalVise"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aavViseToExclude"
        type="AAV"
        :btnAddModal="true"
        @btnAddElementModal="handleNewAAV"
        btnAddElementMessage="Créer un acquis d'aprentissage"
        @close="showModalVise = false"
        @selected="handleSelected"
    />
    <modalList
        v-if="showModalPrerequis"
        :visible="showModalPrerequis"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aavPrerequisToExclude"
        type="AAV"
        @close="showModalPrerequis = false"
        @selected="handleSelected"
    />
    <modalList
        v-if="showModalAAT"
        :visible="showModalAAT"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aatPrerequisToExclude"
        type="AAT"
        @close="showModalAAT = false"
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
        SemesterID: { type: Number },
        ProgramID: { type: Number },
        UEParentId: { type: Number, default: null },
    },
    components: { modalList },

    data() {
        return {
            activeForm: null,
            showModalVise: false,
            showModalPrerequis: false,
            showModalCreateAAV: false,
            showModalAAT: false,
            showModalPro: false,
            modalRoute: "",
            modalTitle: "",
            aatPrerequisToExclude: [],
            proToExclude: [],
            aavViseToExclude: [],
            aavPrerequisToExclude: [],
            modalTarget: "", // 'aavvise' ou 'aavprerequis'
            listAAT: [],
            aavForm: {
                name: "",
                description: "",
                fk_AAT: "",
            },
            ueParent: {
                contribution: "",
            },
            ue: {
                aavvise: [],
                aavprerequis: [],
                pro: [],
                aat: [],
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
        handleNewAAV() {
            this.loadAAT();
            this.showModalCreateAAV = true;
        },

        closeModalAAV() {
            this.showModalCreateAAV = false;
            this.resetForm();
        },

        resetForm() {
            this.aavForm = {
                code: "",
                name: "",
                description: "",
                fk_AAT: "",
            };
        },
        openModalTerminal() {
            this.modalTarget = "aat";
            this.modalRoute = "/aat/get";
            this.modalTitle = "Ajouter des acquis d’apprentissage terminaux";
            this.aatPrerequisToExclude = this.ue.aat;
            this.showModalAAT = true;
        },
        openModalVise() {
            this.modalTarget = "aavvise";
            this.modalRoute = "/aav/get";
            this.modalTitle = "Ajouter des acquis d’apprentissage visés";
            this.aavViseToExclude = [
                ...this.ue.aavvise,
                ...this.ue.aavprerequis,
            ];
            this.showModalVise = true;
        },
        openModalPrerequis() {
            this.modalTarget = "aavprerequis";
            this.modalRoute = "/aav/prerequis/get";
            this.modalTitle = "Ajouter des prérequis";
            this.aavPrerequisToExclude = [
                ...this.ue.aavvise,
                ...this.ue.aavprerequis,
            ];
            this.showModalPrerequis = true;
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
            } else if (this.modalTarget === "aat") {
                const itemsWithContribution = selectedItems.map((item) => ({
                    ...item,
                    contribution: 1,
                }));
                this.ue.aat.push(...itemsWithContribution);
            }
        },
        async submitFormUE() {
            try {
                if (this.id) {
                    const response = await axios.put("/ue/update", {
                        id: this.ue.id,
                        name: this.ue.name,
                        ects: this.ue.ects,
                        description: this.ue.description,
                        aavprerequis: this.ue.aavprerequis,
                        aavvise: this.ue.aavvise,
                        pro: this.ue.pro,
                        aat: this.ue.aat,
                        ueParentContribution: this.ueParent.contribution,
                        ueParent: this.ueParent,
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
                        ects: this.ue.ects,
                        description: this.ue.description,
                        aavprerequis: this.ue.aavprerequis,
                        aavvise: this.ue.aavvise,
                        pro: this.ue.pro,
                        aat: this.ue.aat,
                        ueParentID: this.ueParent.id,
                        ueParentContribution: this.ueParent.contribution,
                    });
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
        async loadAAT() {
            const response = await axios.get("/aat/get");
            console.log(response.data);
            this.listAAT = response.data;
        },
        async submitAAV() {
            try {
                const response = await axios.post("/aav/store", this.aavForm);
                const createdAAV = response.data.aav;

                // Ajout dans la bonne liste
                if (this.modalTarget === "aavvise") {
                    this.ue.aavvise.push(createdAAV);
                } else if (this.modalTarget === "aavprerequis") {
                    this.ue.aavprerequis.push(createdAAV);
                }

                this.closeModalAAV();
            } catch (error) {
                console.error(error);
                alert("Erreur lors de la création.");
            }
        },
        async loadProgram() {
            try {
                const programId = this.$route.query.programID;
                const semesterNumber = this.$route.query.semesterNumber;

                if (!programId) {
                    return;
                }

                const response = await axios.get("/pro/get/detailed", {
                    params: { id: programId },
                });

                if (response.data) {
                    const alreadyLinked = this.ue.pro.some(
                        (pro) => pro.id === response.data.id
                    );
                    const responseProgram = response.data;
                    responseProgram.semester = parseInt(semesterNumber, 10);
                    if (!alreadyLinked) {
                        this.ue.pro.push(responseProgram);
                    }
                }
            } catch (error) {
                console.log(error);
                this.formErrors =
                    "Impossible de charger le programme sélectionné.";
            }
        },

        async loadUEParent(idParent) {
            try {
                const responseUE = await axios.get("/UEGet/detailed", {
                    params: {
                        id: idParent,
                    },
                });
                this.ueParent = responseUE.data;
                this.ueParent.contribution = 1;
                console.log(responseUE.data);
            } catch (error) {
                console.log(error);
            }
        },
        async loadUE() {
            try {
                const responseUE = await axios.get("/UEGet/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                // fusionne les données existantes et les données API
                this.ue = {
                    ...this.ue,
                    ...responseUE.data,
                };
                const responseAAT = await axios.get("/ue/aat/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue.aat = responseAAT.data;
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
        removeElement(type, id) {
            console.log(id);
            if (!this.ue || !this.ue[type] || !Array.isArray(this.ue[type]))
                return;

            const index = this.ue[type].findIndex((item) => item.id === id);

            if (index !== -1) {
                this.ue[type].splice(index, 1);
            }
        },
    },

    mounted() {
        if (this.$route.query.programID && this.$route.query.semesterNumber) {
            this.loadProgram();
        }
        if (this.$route.query.UEParentId) {
            this.loadUEParent(this.$route.query.UEParentId);
        }
        if (this.id) {
            console.log("ok");
            this.loadUE();
        }
    },
};
</script>
<style>
.modal-backdrop {
    z-index: 1040;
}

.modal-dialog {
    z-index: 1050;
}

.modal.fade.show.d-block {
    background: rgba(0, 0, 0, 0.45);
}
</style>
