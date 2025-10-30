<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="container">
            <div v-if="$route.query.message" class="alert alert-success mt-3">
                <i
                    class="fa-solid fa-check green mr-2"
                    style="color: darkgreen"
                ></i>
                <span> {{ $route.query.message }} </span>
            </div>
            <div class="p-4 border rounded bg-light mt-3">
                <div class="row mb-2">
                    <h3 class="primary_color col-md-10 mb-0">
                        <span class="box_code UE pl-2 pr-2 mr-2">{{
                            ue.code
                        }}</span>
                        <span>
                            {{ ue.name }}
                        </span>
                    </h3>
                    <span class="col-md-2 text-right">
                        <router-link
                            :to="{
                                name: 'modifyUE',
                                params: { id: ue.id },
                            }"
                        >
                            <button class="btn btn-primary">modifier</button>
                        </router-link>
                    </span>
                </div>
                <span> </span>
                <div class="mb-4" v-html="ue.description"></div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <span class="primary_color">date de début :</span>
                        {{ ue.date_begin }}
                    </div>
                    <div class="col-md-6">
                        <span class="primary_color">date de fin :</span>
                        {{ ue.date_end }}
                    </div>
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            Liste des acquis d'apprentissages visé
                        </h5>
                    </div>
                    <list
                        v-if="ue.id"
                        routeGET="/ue/aavvise/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="AAV"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            Liste des prérequis
                        </h5>
                    </div>
                    <list
                        v-if="ue.id"
                        routeGET="/ue/aavprerequis/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="AAV"
                        :listColonne="['code', 'name']"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import list from "../list.vue";
import dayjs from "dayjs";

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
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
                name: "",
                description: "",
                code: "",
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
                this.ue.date_begin = formatDate(this.ue.date_begin);
                this.ue.date_end = formatDate(this.ue.date_end);
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
