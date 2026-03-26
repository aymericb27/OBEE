<template>
    <div v-if="$route.query.message" class="alert alert-success mt-3 mr-5 ml-5">
        <i class="fa-solid fa-check green mr-2" style="color: darkgreen"></i>
        <span> {{ $route.query.message }} </span>
    </div>
    <div class="row mt-3 mr-0 p-3">
        <div class="col-md-3">
            <div class="border p-3 bg-white rounded">
                <h2 class="secondary_color">
                    <i class="fa-solid fa-graduation-cap primary_color"></i>
                    Programme
                </h2>
                <BaseLoader
                    v-if="isLoadingPRO"
                    text="Chargement..."
                    size="md"
                />
                <ul v-else class="secondary_color">
                    <li
                        class="p-2 program-item"
                        :class="{ active: selectedProgramId === prog.id }"
                        style="list-style-type: none; cursor: pointer"
                        v-for="prog in progs"
                        :key="prog.id"
                        @click="selectProgram(prog.id)"
                    >
                        <h5 class="m-0">{{ prog.name }}</h5>
                    </li>
                </ul>
                <div class="text-right">
                    <router-link
                        :to="{
                            name: 'createProgram',
                            params: { id: null },
                        }"
                    >
                        <button class="btn btn-lg btn-primary ml-auto mb-2">
                            + ajout programme
                        </button>
                    </router-link>
                    <router-link
                        :to="{
                            name: 'formImport',
                        }"
                    >
                        <button
                            class="btn btn-lg btn-outline-secondary ml-auto mb-2"
                        >
                            + importation de donnée
                        </button>
                    </router-link>
                </div>
            </div>
        </div>
        <div class="border bg-white rounded p-3 mb-3 secondary_color col-md-6">
            <BaseLoader v-if="isLoadingSEM" text="Chargement..." size="md" />
            <div v-else>
                <div class="p-3">
                    <strong v-if="!progs.length" class="p-3 mt-3"
                        >Aucun programme actuellement dans le logiciel.</strong
                    >
                </div>

                <span v-if="progs.length">
                    <h2 class="secondary_color mb-1 d-inline-block">
                        {{ prog.name }}
                    </h2>

                    <div class="float-right d-flex flex-column align-items-end">
                        <router-link
                            :to="{
                                name: 'modifyPRO',
                                params: { id: prog.id },
                            }"
                        >
                            <button class="btn btn-lg btn-primary ml-auto mb-2">
                                modifier le programme
                            </button>
                        </router-link>
                        <button
                            class="btn btn-lg btn-success ml-auto mb-2"
                            @click="openCopyModal(prog.id)"
                        >
                            copier le programme
                        </button>
                    </div>
                    <p class="text-muted mb-3">
                        Programme structuré avec les semestres, unité
                        d'enseignement et les éléments constitutifs
                    </p>
                    <div class="mb-3">
                        <span class="badge badge-success text-white ml-2 p-2">
                            Total des crédits des UE pour le semestre
                        </span>
                        <span class="badge bg-light text-dark ml-2 border p-2">
                            Nombre imposé par le programme
                        </span>
                    </div>
                </span>
                <span v-for="semestre in prog.listSemestre">
                    <SemesterBlock
                        :semester="semestre"
                        :program-id="prog.id"
                        :number="semestre.number"
                        @open-ue-modal="openModalUE"
                        @deleteRefresh="loadProgramDetailed(prog.id)"
                    />
                </span>
            </div>
        </div>
    </div>

    <div v-if="showCopyModal" class="modal-backdrop-custom">
        <div class="modal-custom">
            <h4 class="mb-3">Confirmation</h4>
            <p>Voulez vous copier le programme et tout ce qu'il contient ?</p>
            <div class="text-right mt-4">
                <button class="btn btn-danger mr-2" @click="closeCopyModal">
                    Non
                </button>
                <button class="btn btn-success" @click="confirmCopyProgram">
                    Oui
                </button>
            </div>
        </div>
    </div>
    <div v-if="showCopyDetailsModal" class="modal-backdrop-custom">
        <div class="modal-custom">
            <h4 class="mb-3">Copier le programme</h4>
            <p v-if="copyFormError" class="text-danger mb-2">
                {{ copyFormError }}
            </p>
            <div class="form-group mb-3">
                <label class="mb-1">Sigle du nouveau programme</label>
                <input
                    v-model="copyForm.code"
                    type="text"
                    class="form-control"
                />
            </div>
            <div class="form-group mb-3">
                <label class="mb-1">Libellé du nouveau programme</label>
                <input
                    v-model="copyForm.name"
                    type="text"
                    class="form-control"
                />
            </div>

            <div class="text-right mt-4">
                <button
                    class="btn btn-danger mr-2"
                    @click="closeCopyDetailsModal"
                >
                    Annuler
                </button>
                <button class="btn btn-success" @click="confirmCopyDetails">
                    Confirmer
                </button>
            </div>
        </div>
    </div>
    <modalList
        v-if="showModalUE"
        :visible="showModalUE"
        :routeGET="modalRoute"
        :title="modalTitle"
        :btnAddElement="true"
        btnAddElementRoute="/ue/create"
        :btnAddElementParam="paramUEForm"
        btnAddElementMessage="Créer une unité d'enseignement"
        type="UE"
        :listToExclude="UEsToExclude"
        @close="showModalUE = false"
        @selected="handleSelectedUE"
    />
</template>
<script>
import axios from "axios";
import list from "./list.vue";
import SemesterBlock from "./SemesterBlock.vue";
import modalList from "./modalList.vue";
import BaseLoader from "./modal/baseLoader.vue";
import {
    clearCurrentProgram,
    currentProgramState,
    setCurrentProgram,
} from "../stores/currentProgram";

export default {
    data() {
        return {
            isLoadingPRO: true,
            isLoadingSEM: true,
            UEsToExclude: [],
            showModalUE: false,
            showCopyModal: false,
            showCopyDetailsModal: false,
            programToCopyId: null,
            copyForm: {
                name: "",
                code: "",
            },
            copyFormError: null,
            UECreateType: null,
            paramUEForm: {},
            /*             showSemesterModal: false,
             */ semesterSelected: "",
            selectedProgramId: null, // 👈 programme sélectionné
            prog: {
                name: "",
            },
            progs: [],
        };
    },
    components: {
        list,
        modalList,
        SemesterBlock,
        BaseLoader,
    },

    methods: {
        getStoredProgramId() {
            return currentProgramState.id;
        },
        clearStoredProgramId() {
            clearCurrentProgram();
        },
        openModalUE(param) {
            this.UEsToExclude = param.semester.UES;
            this.modalTarget = "ue";
            this.paramUEForm = {
                semesterNumber: param.semester.number,
                programID: this.selectedProgramId,
            };
            if (param.type === "EC") {
                this.paramUEForm.UEParentId = param.UE.id;
            }
            this.modalRoute = "/ues/get";
            this.modalTitle = "Ajouter des unités d'enseignement";
            this.showModalUE = true;
            this.semesterSelected = param.semester.number;
        },
        async handleSelectedUE(UES) {
            if (this.paramUEForm.UEParentId) {
                try {
                    const response = await axios.post("ues/add/EC", {
                        idParent: this.paramUEForm.UEParentId,
                        listChild: UES,
                    });
                    this.$router.replace({
                        query: {
                            message: response.data.message,
                        },
                    });
                    this.loadProgramDetailed(this.selectedProgramId);
                } catch (error) {
                    console.log(error);
                }
            } else {
                try {
                    const response = await axios.post("programme/ues/add", {
                        list: UES,
                        programme_id: this.selectedProgramId,
                        semester: this.semesterSelected,
                    });
                    this.$router.replace({
                        query: {
                            message: response.data.message,
                        },
                    });
                    this.loadProgramDetailed(this.selectedProgramId);
                } catch (error) {
                    console.log(error);
                }
            }
        },
        async loadPrograms() {
            this.isLoadingPRO = true;
            const response = await axios.get("pro/get");
            this.progs = response.data;
            if (this.progs.length) {
                const storedId = this.getStoredProgramId();
                const hasStoredProgram = storedId
                    ? this.progs.some((p) => p.id === storedId)
                    : false;
                const programIdToSelect = hasStoredProgram
                    ? storedId
                    : this.progs[0].id;
                this.selectProgram(programIdToSelect);
            } else {
                this.clearStoredProgramId();
                this.isLoadingSEM = false;
            }
            this.isLoadingPRO = false;
        },

        async selectProgram(id) {
            this.selectedProgramId = id;
            const selectedProgram = this.progs.find((program) => program.id === id);
            if (selectedProgram) {
                setCurrentProgram(selectedProgram);
            } else {
                setCurrentProgram({ id });
            }
            this.loadProgramDetailed(id);
        },

        async loadProgramDetailed(id) {
            this.isLoadingSEM = true;
            const response = await axios.get("/programme/get/tree", {
                params: { id },
            });
            console.log(this.prog);
            this.prog = response.data;
            setCurrentProgram(this.prog);
            this.isLoadingSEM = false;
        },
        openCopyModal(programId) {
            this.programToCopyId = programId;
            this.showCopyModal = true;
        },
        closeCopyModal() {
            this.showCopyModal = false;
            this.programToCopyId = null;
        },
        confirmCopyProgram() {
            if (!this.programToCopyId) return;
            this.copyForm = {
                name: this.prog?.name || "",
                code: this.prog?.code || "",
            };
            this.showCopyModal = false;
            this.showCopyDetailsModal = true;
        },
        closeCopyDetailsModal() {
            this.showCopyDetailsModal = false;
            this.programToCopyId = null;
            this.copyFormError = null;
            this.copyForm = {
                name: "",
                code: "",
            };
        },
        async confirmCopyDetails() {
            if (!this.programToCopyId) return;
            this.copyFormError = null;
            const name = (this.copyForm.name || "").trim();
            const code = (this.copyForm.code || "").trim();

            if (!name || !code) {
                this.copyFormError =
                    "Veuillez remplir le sigle et le libellé du programme.";
                return;
            }

            try {
                const response = await axios.post("/programme/copy", {
                    source_id: this.programToCopyId,
                    name,
                    code,
                });

                this.closeCopyDetailsModal();
                this.$router.push({
                    name: "pro-detail",
                    params: { id: response.data.id },
                    query: { message: response.data.message },
                });
            } catch (error) {
                this.copyFormError =
                    error?.response?.data?.message ||
                    "Erreur lors de la copie du programme.";
            }
        },
        /*         openSemesterModal() {
            this.showSemesterModal = true;
        }, */

        /*         async confirmAddSemester() {
            this.showSemesterModal = false;

            const response = await axios.post("/programme/add-semester", {
                id: this.selectedProgramId,
            });

            this.loadProgramDetailed(this.selectedProgramId);
            this.$router.replace({
                query: {
                    message: "Semestre rajouté avec succès",
                },
            });
        }, */
    },
    mounted() {
        this.loadPrograms();
    },
};
</script>
<style>
.program-item {
    border-radius: 5px;
    transition: 0.2s;
}

.program-item:hover {
    background: #f3f3f3;
}

.program-item.active {
    background: rgba(42, 113, 205, 0.89);
    color: white;
}
.modal-backdrop-custom {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-custom {
    background: white;
    padding: 25px;
    border-radius: 8px;
    width: 350px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.25);
}
</style>
