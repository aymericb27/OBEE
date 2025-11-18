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
            </div>
        </div>
        <div class="border bg-white rounded p-3 secondary_color col-md-9">
            <h2 class="secondary_color mb-1">{{ prog.name }}</h2>
            <p class="text-muted mb-3">
                Program structure with semesters, teaching units, and
                constituent elements
            </p>

            <SemesterBlock
                v-if="prog.firstSemestre"
                :semester="prog.firstSemestre"
                title="Semester 1"
            />

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
            const response = await axios.get("/pro/get/tree", {
                params: { id },
            });
            this.prog = response.data;

            // Facultatif : garder toutes les UE ouvertes
            if (this.prog.firstSemestre?.UES) {
                this.prog.firstSemestre.UES.forEach((ue) => (ue.show = true));
            }
            if (this.prog.secondSemestre?.UES) {
                this.prog.secondSemestre.UES.forEach((ue) => (ue.show = true));
            }
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
    background: #0d6efd;
    color: white;
}
</style>
