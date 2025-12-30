<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()">
                <i class="fa-solid fa-circle-arrow-left primary_color"></i>
                Retour
            </a>
        </div>
        <div class="container">
            <!--             <div class="mt-3">
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
            </div> -->
            <div v-if="$route.query.message" class="alert alert-success mt-3">
                <i
                    class="fa-solid fa-check green mr-2"
                    style="color: darkgreen"
                ></i>
                <span> {{ $route.query.message }} </span>
            </div>
            <div class="p-4 border rounded bg-white mt-3">
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
                <div class="mb-4" v-html="ue.description"></div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h4 class="d-inline-block primary_color">
                            Faisant partie du/des programme(s)
                        </h4>
                    </div>
                    <list
                        :isBorder="true"
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
                        <h4 class="d-inline-block primary_color">
                            Liste des acquis d'apprentissages visé
                        </h4>
                    </div>
                    <list
                        :isBorder="true"
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
                        <h4 class="d-inline-block primary_color">
                            Liste des prérequis
                        </h4>
                    </div>
                    <list
                        :isBorder="true"
                        v-if="ue.id"
                        routeGET="/ue/aavprerequis/get"
                        :paramsRouteGET="{ id: ue.id }"
                        linkDetailed="aav-detail"
                        typeList="AAV"
                        :listColonne="['code', 'name']"
                    />
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h4 class="d-inline-block primary_color">
                            Liste des acquis d'apprentissages terminaux
                        </h4>
                    </div>
                    <list
                        :isBorder="true"
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

import errorShedule from "../error/ErrUEShedule.vue";
import ConfirmDeleteModal from "../modal/confirmDeleteModal.vue";

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
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
