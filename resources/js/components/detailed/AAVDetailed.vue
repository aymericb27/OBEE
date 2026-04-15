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
                        title="supprimer"
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
                            title="éditer"
                        ></i>
                    </router-link>
                    <i
                        @click="exportAAV(aav.id)"
                        style="font-size: 28px"
                        class="fa-solid ml-2 fa-download green_color cursor_pointer"
                        title="télécharger"
                    ></i>
                </span>
            </div>

            <div class="mb-4" v-html="aav.description"></div>
            <div class="listComponent mb-4">
                <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('aats')">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-solid fa-graduation-cap"></i>

                        acquis d'apprentissage terminaux lié(s)
                    </h5>
                    <i class="fa-solid primary_color" :class="isExpanded('aats') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                </div>

                <div v-if="isExpanded('aats')">
                    <div class="border rounded" v-if="aav.id">
                        <div class="row m-auto bg-light border-bottom">
                            <div class="col-md-2 p-2 pl-3">Code</div>
                            <div class="col-md-5 p-2 pl-3">Nom</div>
                            <div class="col-md-3 p-2 pl-3">Programme</div>
                            <div class="col-md-2 p-2 pl-3">Contribution</div>
                        </div>

                        <div v-if="aats.length">
                            <div
                                v-for="(item, index) in aats"
                                :key="item.row_key ?? `${item.id}-${index}`"
                                class="row m-auto"
                                :class="[
                                    index != aats.length - 1
                                        ? 'border-bottom'
                                        : '',
                                ]"
                            >
                                <div class="col-md-2 p-3">
                                    <router-link
                                        :to="{
                                            name: 'aat-detail',
                                            params: { id: item.id },
                                        }"
                                    >
                                        <h6
                                            class="AAT m-0"
                                            style="font-size: 1.1em"
                                        >
                                            {{ item.code }}
                                        </h6>
                                    </router-link>
                                </div>
                                <div class="col-md-5 p-3">
                                    <h6
                                        class="secondary_color mb-0"
                                        style="font-size: 1.1em"
                                    >
                                        {{ item.name }}
                                    </h6>
                                </div>
                                <div class="col-md-3 p-3">
                                    <router-link
                                        :to="{
                                            name: 'pro-detail',
                                            params: { id: item.fk_programme },
                                        }"
                                    >
                                        <h6
                                            class="PRO m-0"
                                            style="font-size: 1.1em"
                                        >
                                            {{ item.programme_code }}
                                        </h6>
                                    </router-link>
                                    {{ item.programme_name }}
                                </div>
                                <div class="col-md-2 p-3">
                                    <span
                                        v-if="
                                            item.contribution !== null &&
                                            item.contribution !== undefined
                                        "
                                        :class="
                                            contributionClass(
                                                item.contribution,
                                                item.level_contribution,
                                            )
                                        "
                                    >
                                        {{ item.contribution }}
                                    </span>
                                    <span v-else>-</span>
                                </div>
                            </div>
                        </div>

                        <div v-else>
                            <p class="p-2 text-center mb-0">
                                Aucune donnée à afficher
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="listComponent mb-4" v-if="isPrerequisPro">
                <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('prerequisPro')">
                    <h5 class="d-inline-block primary_color">
                        <i class="fa-solid fa-key"></i>

                        prérequis pour les programmes
                    </h5>
                    <i class="fa-solid primary_color" :class="isExpanded('prerequisPro') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                </div>

                <div>
                    <list
                        :isBorder="true"
                        v-if="aav.id"
                        v-show="isExpanded('prerequisPro')"
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
                    <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('prerequisUE')">
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-solid fa-key"></i>

                            prérequis pour les unités d'enseignement
                        </h5>
                        <i class="fa-solid primary_color" :class="isExpanded('prerequisUE') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                    </div>

                    <div>
                        <list
                            :isBorder="true"
                            v-if="aav.id"
                        v-show="isExpanded('prerequisUE')"
                            routeGET="/aav/UEPrerequis/get"
                            :paramsRouteGET="{ id: aav.id }"
                            linkDetailed="ue-detail"
                            typeList="UE"
                            :listColonne="['code', 'name']"
                        />
                    </div>
                </div>
                <div class="listComponent mb-4">
                    <div class="mb-2 d-flex justify-content-between align-items-center cursor_pointer" @click="toggleSection('viseUE')">
                        <h5 class="d-inline-block primary_color">
                            <i class="fa-brands fa-google-scholar"></i>

                            acquis d'apprentissage visé pour les unités
                            d'enseignement
                        </h5>
                        <i class="fa-solid primary_color" :class="isExpanded('viseUE') ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
                    </div>

                    <div>
                        <list
                            :isBorder="true"
                            v-if="aav.id"
                        v-show="isExpanded('viseUE')"
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
            expandedSections: {
                aats: true,
                prerequisPro: true,
                prerequisUE: true,
                viseUE: true,
            },
            isPrerequisPro: false,
            aats: [],
            aav: {
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
        contributionClass(value, max) {
            const oneThird = Math.ceil(max / 3);
            const twoThirds = Math.ceil((max * 2) / 3);
            if (value == 10) return "strong_mapping strong_ten_mapping";
            if (value > twoThirds) return "strong_mapping";
            if (value > oneThird) return "medium_mapping";
            return "weak_mapping";
        },
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
                const responseAATs = await axios.get("/aav/aats/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.aats = Array.isArray(responseAATs.data)
                    ? responseAATs.data
                    : [];
                const proPrerequis = await axios.get("/aav/PROPrerequis/get", {
                    params: {
                        id: this.id,
                    },
                });
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




