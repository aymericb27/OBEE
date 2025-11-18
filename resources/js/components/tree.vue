<template>
    <div class="row">
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
                </div>
            </div>
        </div>
        <div class="border bg-white rounded p-3 secondary_color col-md-6">
            <span>
                <h2 class="secondary_color mb-1 d-inline-block">
                    {{ prog.name }}
                </h2>
                <button class="btn btn-lg btn-primary float-right ml-auto mb-2">
                    + ajout semestre
                </button>
                <p class="text-muted mb-3">
                    Programme structuré avec les semestres, unité
                    d'enseignements et les éléments constitutifs
                </p>
            </span>
            <span v-for="semestre in prog.listSemestre">
                <SemesterBlock :semester="semestre" :number="semestre.number" />
            </span>

            <SemesterBlock
                v-if="prog.secondSemestre"
                :semester="prog.secondSemestre"
                title="Semester 2"
            />
        </div>
    </div>
</template>
<script>
import axios from "axios";
import list from "./list.vue";
import modalExport from "./modalExport.vue";
import SemesterBlock from "./SemesterBlock.vue";

export default {
    data() {
        return {
            selectedProgramId: null, // 👈 programme sélectionné
            prog: {
                name: "",
                firstSemestre: {
                    ectsCount: 30,
                },
            },
            progs: [],
        };
    },
    components: {
        list,
        modalExport,
        SemesterBlock,
    },

    methods: {
        async loadPrograms() {
            const response = await axios.get("pro/get");
            this.progs = response.data;

            // Sélection du premier programme par défaut
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
            this.prog = response.data;
            console.log(this.prog);
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
</style>
