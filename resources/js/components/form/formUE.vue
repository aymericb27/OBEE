<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="container">
            <div class="p-4 border rounded bg-light mt-3">
                <div class="mb-4 d-flex align-items-center">
                    <h3 class="box_code UE pl-2 d-inline-block pr-2 mr-2 mb-0">
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
                <span> </span>
                <p class="mb-4">
                    <quill-editor
                        v-model:content="ue.description"
                        content-type="html"
                        theme="snow"
                    ></quill-editor>
                </p>
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
                        class="btn btn-primary mt-2"
                        @click="openModal('aavprerequis')"
                    >
                        ajouter un prérequis
                    </button>
                </div>
            </div>
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
                aavs: {},
                ecs: {},
            },
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
        async loadUE() {
            try {
                console.log(this.id);
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
