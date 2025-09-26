<template>
    <div v-if="!selectedEC">
        <div class="back_btn">
            <a href="#" @click.prevent="$emit('close')">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="container">
            <div class="p-4 border rounded bg-light mt-3">
                <div class="row mb-2">
                    <h3 class="primary_color ml-2 mb-0">
                        <span class="box_code ue">{{ ue.UECode }}</span>
                        {{ ue.UEname }}
                    </h3>
                </div>
                <p class="mb-4">{{ ue.UEdescription }}</p>
                <div class="listComponent">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            Liste des éléments constitutifs
                        </h5>
                    </div>

                    <div class="mb-4">
                        <div v-for="ec in ue.ecs">
                            <div class="row mb-2">
                                <h6 class="ml-2 p-2 mb-0">
                                    <span
                                        class="box_code ec"
                                        @click="openDetailed(ec.ECId)"
                                    >
                                        {{ ec.ECCode }}
                                    </span>
                                    {{ ec.ECName }}
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="listComponent">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            Liste des acquis d'apprentissages
                        </h5>
                    </div>

                    <div class="mb-4">
                        <div v-for="aav in ue.aavs">
                            <div class="row mb-2">
                                <h6 class="ml-2 p-2 mb-0">
                                    <span class="primary_color box_code aav">
                                        {{ aav.AAVCode }}
                                    </span>
                                    {{ aav.AAVDescription }}
                                </h6>
                            </div>
                        </div>
                    </div>
                    <div class="d-inline-block ml-2 w-50">
                        <p>
                            progression de l'élève sélectionné
                            <span class="primary-color">(1/5)</span>
                        </p>
                        <div class="progress">
                            <div
                                class="progress-bar bg-success"
                                role="progressbar"
                                style="width: 30%"
                                aria-valuenow="25"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        Liste des prérequis
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <ECDetail v-if="selectedEC" :ecid="selectedEC" @close="selectedEC = null" />
</template>

<script>
import axios from "axios";
import ECDetail from "./ECDetailed.vue";

export default {
    props: {
        ueid: {
            type: [String, Number],
            required: true,
        },
        csrfform: String,
        ueroutestore: String,
        ecroutestore: String,
    },
    components: {
        ECDetail,
    },
    emits: ["close"],
    data() {
        return {
            selectedEC: false,
            ue: {
                UEname: "",
                UEDescription: "",
                UECode: "",
                aavs: {},
                ecs: {},
            },
        };
    },
    methods: {
        openDetailed(ueid) {
            console.log(ueid);
            this.selectedEC = ueid;
        },
        goback() {},
        async loadUE() {
            try {
                const response = await axios.get("/UEGet/detailed", {
                    params: {
                        ueid: this.ueid,
                    },
                });
                this.ue = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
    },

    mounted() {
        this.loadUE();
    },
};
</script>
