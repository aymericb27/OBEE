<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="container">
            <div class="p-4 border rounded bg-light mt-3">
                <div class="row mb-2">
                    <h3 class="primary_color ml-2 mb-0">
                        <span class="box_code UE  pl-2 pr-2">{{ ue.code }}</span>
                        {{ ue.name }}
                    </h3>
                </div>
                <p class="mb-4">{{ ue.description }}</p>
                <div class="listComponent mb-4" >
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            Liste des éléments constitutifs
                        </h5>
                    </div>

                    <div>
                        <list
                            v-if="ue.id"
                            routeGET="/ue/ecs/get"
                            :paramsRouteGET="{ id: ue.id }"
                            linkDetailed="ec-detail"
                            typeList="EC"
                            :listColonne="['code', 'name']"
                        />
                    </div>
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            Liste des acquis d'apprentissages
                        </h5>
                    </div>
                    <list
                        v-if="ue.id"
                        routeGET="/ue/aavs/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="AAV"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="d-inline-block mb-4 w-50">
                    <p>
                        progression de l'élève sélectionné
                        <span class="primary-color">(1/3)</span>
                    </p>
                    <div class="progress">
                        <div
                            class="progress-bar bg-success"
                            role="progressbar"
                            style="width: 33%"
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
import list from "./list.vue";
export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: { list },
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
        async loadUE() {
            try {
                const response = await axios.get("/UEGet/detailed", {
                    params: {
                        id: this.id,
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
