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
            <div class="row mb-4">
                <h3 class="primary_color col-md-9 mb-0">
                    <span class="box_code PRO pl-2 pr-2">{{ pro.code }}</span>

                    {{ pro.name }}
                </h3>
                <span class="col-md-3 text-right">
                    <i
                        style="font-size: 24px"
                        class="fa-regular fa-trash-can mr-2 deleteBtn"
                        title="supprimer"
                        @click="openModalDelete = true"
                    ></i>
                    <router-link
                        :to="{
                            name: 'modifyPRO',
                        }"
                    >
                        <i
                            style="font-size: 28px"
                            class="fa-regular fa-pen-to-square primary_color"
                            title="éditer"
                        ></i>
                    </router-link>
                    <i
                        @click="exportPRO(pro.id)"
                        style="font-size: 28px"
                        class="fa-solid ml-2 fa-download green_color cursor_pointer"
                        title="télécharger"
                    ></i>
                    <router-link
                        :to="{
                            name: 'programAnalysis',
                            params: { id: pro.id },
                        }"
                    >
                        <i
                            style="font-size: 28px"
                            class="fa-solid ml-2 fa-chart-area cadetblue "
                            title="analyser le processus"
                        ></i>
                    </router-link>
                </span>
            </div>
            <div class="listComponent mb-4">
                <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('ues')">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-solid fa-book-open"></i>
                        Liste des unités d'enseignement
                    </h5>
                    <i class="fa-solid primary_color" :class="isExpanded('ues') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                </div>
                <list
                    :isBorder="true"
                    v-if="pro.id"
                        v-show="isExpanded('ues')"
                    routeGET="/pro/ue/get"
                    :paramsRouteGET="{ id: pro.id }"
                    linkDetailed="ue-detail"
                    typeList="UE"
                    :listColonne="['code', 'name']"
                />
            </div>
            <div class="listComponent mb-4">
                <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('aats')">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-solid fa-graduation-cap"></i>
                        Liste des acquis d'apprentissage terminaux
                    </h5>
                    <i class="fa-solid primary_color" :class="isExpanded('aats') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                </div>
                <list
                    :isBorder="true"
                    v-if="pro.id"
                    v-show="isExpanded('aats')"
                    routeGET="/aat/get"
                    :paramsRouteGET="{ program_id: pro.id }"
                    linkDetailed="aat-detail"
                    typeList="AAT"
                    :listColonne="['code', 'name']"
                />
            </div>
            <div class="listComponent mb-4">
                <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('prerequis')">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-solid fa-key"></i>
                        Liste des prérequis
                    </h5>
                    <i class="fa-solid primary_color" :class="isExpanded('prerequis') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                </div>
                <list
                    :isBorder="true"
                    v-if="pro.id"
                        v-show="isExpanded('prerequis')"
                    routeGET="/pro/pre/get"
                    :paramsRouteGET="{ id: pro.id }"
                    linkDetailed="aav-detail"
                    typeList="AAV"
                    :listColonne="['code', 'name']"
                />
            </div>
        </div>
    </div>
    <ConfirmDeleteModal
        :show="openModalDelete"
        :name="pro.name"
        type="PRO"
        :idToDelete="pro.id"
        @confirm="deleteItem"
        @cancel="openModalDelete = false"
    />
</template>
<script>
import ConfirmDeleteModal from "../modal/confirmDeleteModal.vue";
import axios from "axios";
import list from "../list.vue";
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
            expandedSections: {
                ues: true,
                aats: true,
                prerequis: true,
            },
            pro: {
                name: "",
                code: "",
            },
        };
    },
    methods: {
        toggleSection(section) {
            this.expandedSections[section] = !this.expandedSections[section];
        },
        isExpanded(section) {
            return this.expandedSections[section] !== false;
        },
        async deleteItem() {
            const response = await axios.delete("/pro/delete", {
                params: {
                    id: this.pro.id,
                },
            });
            this.openModalDelete = false;
            this.$router.push({
                name: "tree",
                query: { message: response.data.message },
            });
        },
        async loadPRO() {
            try {
                const response = await axios.get("/pro/get/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.pro = response.data;
            } catch (error) {
                console.log(error);
            }
        },
        async exportPRO(ueId) {
            try {
                const response = await axios.get(`/export/pro/${ueId}`, {
                    responseType: "blob", // 🔥 OBLIGATOIRE pour télécharger un fichier
                });

                // Création d'un lien de téléchargement
                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement("a");
                link.href = url;
                link.download = `PRO_${this.pro.code}.xlsx`; // nom du fichier téléchargé
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
        this.loadPRO();
    },
};
</script>




