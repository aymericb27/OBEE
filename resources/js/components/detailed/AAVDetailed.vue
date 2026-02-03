<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container pb-3">
        <div v-if="$route.query.message" class="alert alert-success mt-3">
            <i
                class="fa-solid fa-check green mr-2"
                style="color: darkgreen"
            ></i>
            <span> {{ $route.query.message }} </span>
        </div>
        <div class="p-4 border rounded shadow bg-white mt-3">
            <div class="row mb-2">
                <h3 class="primary_color col-md-10 mb-0">
                    <span class="box_code AAV pl-2 pr-2">{{ aav.code }}</span>

                    {{ aav.name }}
                </h3>
                <span class="col-md-2 text-right">
                    <i
                        style="font-size: 24px"
                        class="fa-regular fa-trash-can mr-2 deleteBtn"
                        @click="openModalDelete = true"
                    ></i>
                    <router-link
                        :to="{
                            name: 'modifyAAV',
                        }"
                    >
                        <i
                            style="font-size: 28px"
                            class="fa-regular fa-pen-to-square primary_color"
                        ></i>
                    </router-link>
                    <i
                        @click="exportAAV(aav.id)"
                        style="font-size: 28px"
                        class="fa-solid ml-2 fa-download green_color cursor_pointer"
                    ></i>
                </span>
            </div>

            <div class="mb-4" v-html="aav.description"></div>
            <div class="listComponent mb-4">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-solid fa-graduation-cap"></i>

                        acquis d'apprentissage terminaux lié(s)
                    </h5>
                </div>

                <div>
                    <list
                        :isBorder="true"
                        v-if="aav.id"
                        routeGET="/aav/aats/get"
                        :paramsRouteGET="{ id: aav.id }"
                        linkDetailed="aat-detail"
                        typeList="AAT"
                        :listColonne="['code', 'name']"
                    />
                </div>
            </div>
            <div class="listComponent mb-4" v-if="isPrerequisPro">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-solid fa-key"></i>

                        prérequis pour les programmes
                    </h5>
                </div>

                <div>
                    <list
                        :isBorder="true"
                        v-if="aav.id"
                        routeGET="/aav/PROPrerequis/get"
                        :paramsRouteGET="{ id: aav.id }"
                        linkDetailed="pro-detail"
                        typeList="PRO"
                        :listColonne="['code', 'name']"
                    />
                </div>
            </div>
            <div v-else>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-key"></i>

                            prérequis pour les unités d'enseignement
                        </h5>
                    </div>

                    <div>
                        <list
                            :isBorder="true"
                            v-if="aav.id"
                            routeGET="/aav/UEPrerequis/get"
                            :paramsRouteGET="{ id: aav.id }"
                            linkDetailed="ue-detail"
                            typeList="UE"
                            :listColonne="['code', 'name']"
                        />
                    </div>
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2">
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-brands fa-google-scholar"></i>

                            acquis d'apprentissage visé pour les unités
                            d'enseignements
                        </h5>
                    </div>

                    <div>
                        <list
                            :isBorder="true"
                            v-if="aav.id"
                            routeGET="/aav/UEvise/get"
                            :paramsRouteGET="{ id: aav.id }"
                            linkDetailed="ue-detail"
                            typeList="UE"
                            :listColonne="['code', 'name']"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <ConfirmDeleteModal
        :show="openModalDelete"
        :name="aav.name"
        type="AAV"
        :idToDelete="aav.id"
        @confirm="deleteItem"
        @cancel="openModalDelete = false"
    />
</template>
<script>
import axios from "axios";
import list from "../list.vue";
import ConfirmDeleteModal from "../modal/confirmDeleteModal.vue";

export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: {
        list,
        ConfirmDeleteModal,
    },

    data() {
        return {
            openModalDelete: false,
            isPrerequisPro: false,
            aav: {
                name: "",
                description: "",
                code: "",
            },
        };
    },
    methods: {
        async deleteItem() {
            const response = await axios.delete("/aav/delete", {
                params: {
                    id: this.aav.id,
                },
            });
            this.openModalDelete = false;
            this.$router.push({
                name: "tree",
                query: { message: response.data.message },
            });
        },
        async loadAAV() {
            try {
                const response = await axios.get("/aav/get/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.aav = response.data;
                const proPrerequis = await axios.get("/aav/PROPrerequis/get", {
                    params: {
                        id: this.id,
                    },
                });
				console.log(proPrerequis);
                this.isPrerequisPro = proPrerequis.data.length !== 0;
            } catch (error) {
                console.log(error);
            }
        },
        async exportAAV(aavId) {
            try {
                const response = await axios.get(`/export/aav/${aavId}`, {
                    responseType: "blob", // 🔥 OBLIGATOIRE pour télécharger un fichier
                });

                // Création d'un lien de téléchargement
                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement("a");
                link.href = url;
                link.download = `AAV_${this.aav.code}.xlsx`; // nom du fichier téléchargé
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Erreur de téléchargement :", error);
            }
        },
    },

    mounted() {
        this.loadAAV();
    },
};
</script>
