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
        <div class="p-4 border rounded bg-white mt-3">
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
                        @click="exportUE(ue.id)"
                        style="font-size: 28px"
                        class="fa-solid ml-2 fa-download green_color cursor_pointer"
                    ></i>
                </span>
            </div>

            <div class="mb-4" v-html="aav.description"></div>
            <div class="listComponent mb-4">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
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
            <div class="listComponent mb-4">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
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
            } catch (error) {
                console.log(error);
            }
        },
    },

    mounted() {
        this.loadAAV();
    },
};
</script>
