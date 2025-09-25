<template>
    <div class="back_btn">
        <a href="#" @click.prevent="$emit('close')">
            <i class="fa-solid fa-circle-arrow-left"></i> Retour
        </a>
    </div>
    <div class="container">
        <div class="p-4 border rounded bg-light mt-3">
            <div class="row mb-2">
                <div class="box_code pl-3 pr-3">{{ ue.UECode }}</div>
                <h3 class="primary_color ml-2 mb-0">
                    {{ ue.UEname }}
                </h3>
            </div>
            <p class="mb-4">{{ ue.UEdescription }}</p>
            <div class="listComponent">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        Liste des acquis d'apprentissages
                    </h5>
                </div>

                <div class="mb-4">
                    <div v-for="aav in ue.aavs">
                        <div class="row mb-2">
                            
                            <div class="box_code aat mr-2">{{ aav.AATCode }}</div>
                            <div class="box_code aav">{{ aav.AAVCode }}</div>
                            <p class="ml-2 p-2 mb-0">
                                {{ aav.AAVDescription }}
                            </p>
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
        </div>
    </div>
</template>
<script>
import axios from "axios";

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
    emits: ["close"],
    data() {
        return {
            ue: {
                UEname: "",
                UEDescription: "",
                UECode: "",
                aavs: {},
            },
        };
    },
    methods: {
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
