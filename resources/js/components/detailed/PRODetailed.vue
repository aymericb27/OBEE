<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container">
        <div class="p-4 border rounded bg-white mt-3">
            <div class="row mb-4">
                <h3 class="primary_color col-md-10 mb-0">
                    <span class="box_code PRO pl-2 pr-2">{{ pro.code }}</span>

                    {{ pro.name }}
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
            <div class="listComponent mb-4">
                <div class="mb-2">
                    <h4 class="d-inline-block primary_color">
                        Liste des unités d'enseignements
                    </h4>
                </div>
                <list
                    :isBorder="true"
                    v-if="pro.id"
                    routeGET="/pro/ue/get"
                    :paramsRouteGET="{ id: pro.id }"
                    linkDetailed="ue-detail"
                    typeList="UE"
                    :listColonne="['code', 'name']"
                />
            </div>
        </div>
    </div>
</template>
<script>
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
    },

    data() {
        return {
            pro: {
                name: "",
                code: "",
            },
        };
    },
    methods: {
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
    },

    mounted() {
        this.loadPRO();
    },
};
</script>
