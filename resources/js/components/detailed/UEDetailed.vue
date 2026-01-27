<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left primary_color"></i>
                Retour
            </a>
        </div>
        <div class="container pb-4 ">
            <div v-if="$route.query.message" class="alert alert-success mt-3">
                <i
                    class="fa-solid fa-check green mr-2"
                    style="color: darkgreen"
                ></i>
                <span> {{ $route.query.message }} </span>
            </div>
            <div class="p-4 border rounded bg-white mt-3 shadow">
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
                        <i
                            style="font-size: 24px"
                            class="fa-regular fa-trash-can mr-2 deleteBtn"
                            @click="openModalDelete = true"
                        ></i>
                        <router-link
                            :to="{
                                name: 'modifyUE',
                            }"
                        >
                            <i
                                style="font-size: 28px"
                                class="fa-regular fa-pen-to-square primary_color"
                            ></i>
                        </router-link>
                        <i
                            @click="exportUE(ue.id)"
                            style="font-size: 28px"
                            class="fa-solid ml-2 fa-download green_color cursor_pointer"
                        ></i>
                    </span>
                </div>
                <span> </span>
                <div class="pb-4 border-bottom mb-4">
                    <div v-html="ue.description"></div>
                    <div v-if="ue.parent && ue.parent.length">
                        Cette unité est un élément constitutif de
                        <strong>
                            <router-link
                                class="UE"
                                v-if="ue.parent[0].id"
                                :to="{
                                    name: 'ue-detail',
                                    params: { id: ue.parent[0].id },
                                }"
                            >
                                {{ ue.parent[0].code }}
                            </router-link>
                            {{ ue.parent[0].name }}
                        </strong>
                    </div>
                </div>

                <div
                    class="listComponent mb-4"
                    v-if="ue.children && ue.children.length"
                >
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            liste des éléments constitutifs
                        </h5>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        routeGET="/ue/ecs/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="ue-detail"
                        typeList="UE"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-scroll"></i> Faisant partie
                            du/des programme(s)
                        </h5>
                    </div>
                    <list
                        :key="`${ue.id}`"
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
                            <i class="fa-brands fa-google-scholar"></i>
                            Liste des acquis d'apprentissages visé
                        </h5>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        routeGET="/ue/aavvise/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="AAV"
                        :listColonne="[
                            'code',
                            'name',
                            ...(ue.children && ue.children.length
                                ? ['element_constitutif_aav']
                                : []),
                        ]"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-key"></i>
                            Liste des prérequis
                        </h5>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        routeGET="/ue/aavprerequis/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="PRE"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-graduation-cap"></i>
                            Liste des acquis d'apprentissages terminaux
                        </h5>
                    </div>
                    <list
                        :key="`${ue.id}`"
                        v-if="ue.id"
                        routeGET="/ue/aat/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aat-detail"
                        typeList="AAT"
                        :listColonne="['code', 'name']"
                    />
                </div>
            </div>
        </div>
    </div>

    <ConfirmDeleteModal
        :show="openModalDelete"
        :name="ue.name"
        type="UE"
        :idToDelete="ue.id"
        @confirm="deleteItem"
        @cancel="openModalDelete = false"
    />
</template>

<script>
import axios from "axios";
import list from "../list.vue";
import dayjs from "dayjs";
import { onMounted, watch } from "vue";

import errorShedule from "../error/ErrUEShedule.vue";
import ConfirmDeleteModal from "../modal/confirmDeleteModal.vue";

export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: { list, errorShedule, ConfirmDeleteModal },
    data() {
        return {
            openModalDelete: false,
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
        async exportUE(ueId) {
            try {
                const response = await axios.get(`/export/ue/${ueId}`, {
                    responseType: "blob", // 🔥 OBLIGATOIRE pour télécharger un fichier
                });

                // Création d'un lien de téléchargement
                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement("a");
                link.href = url;
                link.download = `UE_${this.ue.code}.xlsx`; // nom du fichier téléchargé
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Erreur de téléchargement :", error);
            }
        },
        async deleteItem() {
            const response = await axios.delete("/ue/delete", {
                params: {
                    id: this.ue.id,
                },
            });
            this.openModalDelete = false;
            this.$router.push({
                name: "tree",
                query: { message: response.data.message },
            });
        },

        async loadUE() {
            try {
                const response = await axios.get("/UEGet/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue = response.data;
                console.log(this.ue);
                /*                 this.ue.semestre = this.ue.semestre === 1 ? "1er" : "2ème";
                const responseError = await axios.get("/Error/UE", {
                    params: {
                        id: this.id,
                    },
                });
                this.errors = responseError.data; */
            } catch (error) {
                console.log(error);
            }
        },
    },

    watch: {
        id: {
            immediate: true, // charge aussi au 1er affichage
            handler() {
                this.loadUE();
            },
        },
    },
};
</script>
