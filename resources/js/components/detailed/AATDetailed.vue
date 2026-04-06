<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
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
        <div class="p-4 border rounded shadow bg-white mt-3">
            <div class="row mb-2">
                <h3 class="primary_color col-md-10 mb-0">
                    <span class="box_code AAT pl-2 pr-2">{{ aat.code }}</span>

                    {{ aat.name }}
                </h3>
                <span class="col-md-2 text-right">
                    <i
                        style="font-size: 24px"
                        class="fa-regular fa-trash-can mr-2 deleteBtn"
                        @click="openModalDelete = true"
                    ></i>
                    <router-link
                        :to="{
                            name: 'modifyAAT',
                            params: { id: aat.id },
                        }"
                    >
                        <i
                            style="font-size: 28px"
                            class="fa-regular fa-pen-to-square primary_color"
                        ></i>
                    </router-link>
                    <i
                        @click="exportAAT(aat.id)"
                        style="font-size: 28px"
                        class="fa-solid ml-2 fa-download green_color cursor_pointer"
                    ></i>
                </span>
            </div>
            <div class="mb-4" v-html="aat.description"></div>
            <div class="mb-2" v-if="aat.fk_programme">
                <h5 class="d-inline-block primary_color">Programme :</h5>
                <router-link
                    :to="{
                        name: 'pro-detail',
                        params: { id: aat.fk_programme },
                    }"
                >
                    <span class="PRO">
                        {{ aat.programme_code }}
                    </span>
                </router-link>
                <span v-if="aat.programme_name">
                    - {{ aat.programme_name }}
                </span>
            </div>
			<div class="mb-4"><h5 class="d-inline-block primary_color">Contribution Maximum :</h5> <span class="strong_mapping">{{ aat.level_contribution }}</span></div>
            <div class="listComponent mb-4">
                <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('aavLinked')">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-brands fa-google-scholar"></i>

                        acquis d'apprentissage visés liés
                    </h5>
                    <i class="fa-solid primary_color" :class="isExpanded('aavLinked') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                </div>

                <div>
                    <list
                        :isBorder="true"
                        v-if="aat.id"
                        v-show="isExpanded('aavLinked')"
                        routeGET="/aat/aavs/get"
                        :paramsRouteGET="{ id: aat.id }"
                        linkDetailed="aav-detail"
                        typeList="AAV"
                        :listColonne="['code', 'name', 'ues', 'programme', 'contribution']"
                    />
                </div>
            </div>
        </div>
    </div>
    <ConfirmDeleteModal
        :show="openModalDelete"
        :name="aat.name"
        type="AAT"
        :idToDelete="aat.id"
        @confirm="deleteItem"
        @cancel="openModalDelete = false"
    />
</template>
<script>
import list from "../list.vue";
import axios from "axios";
import ConfirmDeleteModal from "../modal/confirmDeleteModal.vue";

export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: { list, ConfirmDeleteModal },

    emits: ["close"],
    data() {
        return {
            openModalDelete: false,
            expandedSections: {
                aavLinked: true,
            },
            aat: {
                name: "",
                description: "",
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
            const response = await axios.delete("/aat/delete", {
                params: {
                    id: this.aat.id,
                },
            });
            this.openModalDelete = false;
            this.$router.push({
                name: "tree",
                query: { message: response.data.message },
            });
        },
        async loadAAT() {
            try {
                const response = await axios.get("/aat/get/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.aat = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async exportAAT(ueId) {
            try {
                const response = await axios.get(`/export/aat/${ueId}`, {
                    responseType: "blob", // 🔥 OBLIGATOIRE pour télécharger un fichier
                });

                // Création d'un lien de téléchargement
                const blob = new Blob([response.data], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement("a");
                link.href = url;
                link.download = `AAT_${this.aat.code}.xlsx`; // nom du fichier téléchargé
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
        this.loadAAT();
    },
};
</script>




