<template>
    <div v-if="$route.query.message" class="alert alert-success mt-3 mr-5 ml-5">
        <i class="fa-solid fa-check green mr-2" style="color: darkgreen"></i>
        <span> {{ $route.query.message }} </span>
    </div>
    <div class="row mt-3">
        <div class="col-md-3">
            <div class="border p-3 bg-white rounded">
                <h2 class="secondary_color">
                    <i class="fa-solid fa-graduation-cap primary_color"></i>
                    Programme
                </h2>
                <ul class="secondary_color">
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
                    <button
                        class="btn btn-lg btn-outline-secondary ml-auto mb-2"
                    >
                        + importation de donnée
                    </button>
                </div>
            </div>
        </div>
        <div class="border bg-white rounded p-3 secondary_color col-md-6">
            <span>
                <h2 class="secondary_color mb-1 d-inline-block">
                    {{ prog.name }}
                </h2>
                <button
                    class="btn btn-lg btn-primary float-right ml-auto mb-2"
                    @click="openSemesterModal"
                >
                    + ajout semestre
                </button>
                <p class="text-muted mb-3">
                    Programme structuré avec les semestres, unité
                    d'enseignements et les éléments constitutifs
                </p>
            </span>
            <span v-for="semestre in prog.listSemestre">
                <SemesterBlock
                    :semester="semestre"
                    :number="semestre.number"
                    @open-ue-modal="openModalUE"
                />
            </span>
        </div>
    </div>
    <!-- Modal ajout semestre -->
    <div v-if="showSemesterModal" class="modal-backdrop-custom">
        <div class="modal-custom">
            <h4 class="mb-3">Confirmation</h4>

            <p>Voulez-vous vraiment rajouter un semestre ?</p>

            <div class="text-right mt-4">
                <button
                    class="btn btn-secondary mr-2"
                    @click="showSemesterModal = false"
                >
                    Non
                </button>

                <button class="btn btn-primary" @click="confirmAddSemester">
                    Oui, ajouter
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

export default {
    data() {
        return {
            UEsToExclude: [],
            showModalUE: false,
            UECreateType: null,
            paramUEForm: {},
            showSemesterModal: false,
            semesterSelected: "",
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
    },

    methods: {
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
            console.log(this.paramUEForm);
            this.modalRoute = "/ues/get";
            this.modalTitle = "Ajouter des unités d'enseignements";
            this.showModalUE = true;
        },
        async handleSelectedUE(UES) {
            try {
                const response = await axios.post("programme/ues/add", {
                    list: UES,
                    id: this.selectedProgramId,
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
        },
        async loadPrograms() {
            const response = await axios.get("pro/get");
            this.progs = response.data;
            this.selectProgram(this.progs[0].id);
        },

        async selectProgram(id) {
            this.selectedProgramId = id;
            this.loadProgramDetailed(id);
        },

        async loadProgramDetailed(id) {
            const response = await axios.get("/programme/get/tree", {
                params: { id },
            });
            console.log(this.prog);
            this.prog = response.data;
        },
        openSemesterModal() {
            this.showSemesterModal = true;
        },
		
        async confirmAddSemester() {
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
        },
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
