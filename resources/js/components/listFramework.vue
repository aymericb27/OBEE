<template>
    <div class="container" v-if="!selectedUE && !selectedEC && !selectedAAT">
        <div class="p-3 border m-3 rounded bg-light">
            <div id="filter">
                <form @submit.prevent="submitFormFilter">
                    <i class="fa-solid fa-filter mr-2"></i>
                    <select
                        v-model="formFilter.displayElelement"
                        class="mr-2 w-30 form-control d-inline-block"
                    >
                        <option disabled value="" selected>
                            -- Affichage par --
                        </option>
                        <option value="UE" selected>
                            Unité d'enseignement
                        </option>
                        <option value="EC">éléments constitutifs</option>
                        <option value="AAT">
                            acquis d'apprentissages terminaux
                        </option>
                    </select>
                    <select class="mr-2 w-25 form-control d-inline-block">
                        <option disabled value="" selected>
                            -- Choisir le semestre --
                        </option>
                        <option value="UE" selected>1er semestre</option>
                        <option value="EC">2ème semestre</option>
                    </select>
                    <button type="submit" class="align-bottom btn btn-primary">
                        rechercher
                    </button>
                </form>
            </div>
        </div>
        <div
            id="listUniteEnseignement"
            class="mt-3 container border"
            v-if="listToDisplay === 'UE'"
        >
            <div class="row border-bottom">
                <div class="col-md-1 p-2">Code</div>
                <div class="col-md-9 p-2">Unité d'enseignement</div>
                <div class="col-md-1 p-2">Semestre</div>
                <div class="col-md-1 p-2">ECTS</div>
            </div>
            <div
                v-for="(ue, index) in ues"
                :key="ue.id"
                :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                class="row"
            >
                <div class="col-md-1 p-2 ue" @click="openDetailed(ue.UEid)">
                    {{ ue.UEcode }}
                </div>
                <div class="col-md-10 p-2">
                    <p class="primary_color mb-0">
                        <a href="#" @click="openDetailed(ue.UEid)">{{
                            ue.UEname
                        }}</a>
                    </p>
                </div>
                <div class="col-md-1 p-2">
                    {{ ue.ects }}
                </div>
            </div>
        </div>
        <div class="mt-3 container border" v-if="listToDisplay === 'EC'">
            <div class="row border-bottom">
                <div class="col-md-1 p-2">Code</div>
                <div class="col-md-9 p-2">élement constitutif</div>
            </div>
            <div
                v-for="(ec, index) in ecs"
                :key="ec.id"
                :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                class="row"
            >
                <div class="col-md-1 p-2" @click="openDetailedEC(ec.id)">
                    {{ ec.code }}
                </div>
                <div class="col-md-11 p-2">
                    <p class="primary_color mb-0">
                        <a href="#" @click="openDetailedEC(ec.id)">{{
                            ec.name
                        }}</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-3 container border" v-if="listToDisplay === 'AAT'">
            <div class="row border-bottom">
                <div class="col-md-1 p-2">Code</div>
                <div class="col-md-9 p-2">
                    Acquis d'apprentissages terminaux
                </div>
            </div>
            <div
                v-for="(aat, index) in aats"
                :key="aat.id"
                :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                class="row"
            >
                <div class="col-md-1 p-2" @click="openDetailedAAT(aat.AATId)">
                    {{ aat.AATCode }}
                </div>
                <div class="col-md-11 p-2">
                    <p class="primary_color mb-0">
                        <a href="#" @click="openDetailedAAT(aat.AATId)">{{
                            aat.AATName
                        }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <UEDetail v-if="selectedUE" :ueid="selectedUE" @close="selectedUE = null" />
    <ECDetail v-if="selectedEC" :ecid="selectedEC" @close="selectedEC = null" />
    <AATDetailed
        v-if="selectedAAT"
        :aatid="selectedAAT"
        @close="selectedAAT = null"
    />
</template>

<script>
import axios from "axios";
import UEDetail from "./UEDetailed.vue";
import ECDetail from "./ECDetailed.vue";
import AATDetailed from "./AATDetailed.vue";

export default {
    components: { UEDetail, ECDetail, AATDetailed },

    data() {
        return {
            ues: [], // liste des UE
            ecs: [],
            aats: [],
            detailed: false,
            selectedEC: null,
            selectedAAT: null,
            selectedUE: null, // l’UE sélectionnée
            listToDisplay: "UE",
            formFilter: {
                displayElelement: "UE",
            },
        };
    },
    methods: {
        openDetailed(ueid) {
            this.selectedUE = ueid;
        },
        openDetailedEC(ecid) {
            this.selectedEC = ecid;
        },
        openDetailedAAT(aatid) {
            this.selectedAAT = aatid;
        },
        async submitFormFilter() {
            let displayElelement = this.formFilter.displayElelement;
            this.listToDisplay = displayElelement;
            if (displayElelement === "EC") {
                this.loadECs();
            }
            if (displayElelement === "UE") {
                this.loadUEs();
            }
            if (displayElelement === "AAT") {
                this.loadAATs();
            }
        },
        async loadECs() {
            try {
                const response = await axios.get("/ECGet");
                this.ecs = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async loadUEs() {
            try {
                const response = await axios.get("/UEGet", {
                    params: {
                        withUE: true,
                    },
                });
                this.ues = response.data;
            } catch (error) {
                console.log(error);
            }
        },
        async loadAATs() {
            try {
                const response = await axios.get("/AATGet");
                this.aats = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
    },
    mounted() {
        this.loadUEs();
    },
};
</script>
