<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container">
        <div class="p-4 border rounded bg-light mt-3">
            <div class="row mb-2">
                <h3 class="primary_color ml-2 mb-0">
                    <span class="box_code AAV pl-2 pr-2">{{ aav.code }}</span>

                    {{ aav.name }}
                </h3>
            </div>
            <p class="mb-4">{{ aav.description }}</p>
            <div class="listComponent mb-4">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        acquis d'apprentissage terminaux lié(s)
                    </h5>
                </div>

                <div>
                    <list
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
                        acquis d'apprentissage visé pour les unités d'enseignements
                    </h5>
                </div>

                <div>
                    <list
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
            aav: {
                name: "",
                description: "",
                code: "",
            },
        };
    },
    methods: {
        async loadAAT() {
            try {
                const response = await axios.get("/aav/get/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.aav = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
    },

    mounted() {
        this.loadAAT();
    },
};
</script>
