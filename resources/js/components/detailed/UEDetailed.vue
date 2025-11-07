<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="container">
            <div class="mt-3">
                <div
                    v-for="(prog, index) in errors.errorsECTS"
                    :key="index"
                    class="alert alert-danger mb-2"
                >
                    <i
                        class="fa-solid fa-triangle-exclamation"
                        style="color: #f3aa24; font-size: 24px"
                    ></i>
                    <span class="p-2">
                        <strong>Erreur : </strong>L'unité d'enseignement fait
                        partie du programme
                        <router-link
                            :to="{
                                name: 'pro-detail',
                                params: { id: prog.id },
                            }"
                            >{{ prog.code }}</router-link
                        > </span
                    >({{ prog.name }})
                    <span v-if="prog.ects < prog.UEECts"
                        >qui a trop de crédits attribués.</span
                    >
                    <span v-else>qui n'a pas assez de crédits attribués.</span>
                </div>
                <errorShedule :id="this.id" />
            </div>
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
                                params: { isCreate: true },
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
                    <div class="col-md-4">
                        <span class="primary_color">date de fin :</span>
                        {{ ue.date_end }}
                    </div>
                    <div class="col-md-4">
                        <span class="primary_color">semestre :</span>
                        {{ ue.semestre }}
                    </div>
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
													Faisant partie du/des programme(s)
                        </h5>
                    </div>
                    <list
                        v-if="ue.id"
                        routeGET="/ue/pro/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="pro-detail"
                        typeList="PRO"
                        :listColonne="['code', 'name']"
                    />
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
import errorShedule from "../error/ErrUEShedule.vue";

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: { list, errorShedule },
    data() {
        return {
            selectedEC: false,
            ue: {
                name: "",
                description: "",
                code: "",
                semestre: "",
                aavs: {},
                ecs: {},
            },
            errors: {
                errorsECTS: [],
                errorsShedule: [],
                isError: false,
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
                this.ue.semestre = this.ue.semestre === 1 ? "1er" : "2ème";
                const responseError = await axios.get("/Error/UE", {
                    params: {
                        id: this.id,
                    },
                });
                this.errors = responseError.data;
                console.log(responseError);
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
