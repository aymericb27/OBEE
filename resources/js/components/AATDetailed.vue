<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left"></i> Retour
        </a>
    </div>
    <div class="container">
        <div class="p-4 border rounded bg-light mt-3">
            <div class="row mb-2">
                <h3 class="primary_color ml-2 mb-0">
                    <span class="box_code AAT pl-2 pr-2">{{ aat.code }}</span>

                    {{ aat.name }}
                </h3>
            </div>
            <p class="mb-4">{{ aat.description }}</p>
            <div class="listComponent mb-4">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        acquis d'apprentissage visés liés
                    </h5>
                </div>

                <div>
                    <list
                        v-if="aat.id"
                        routeGET="/aat/aavs/get"
                        :paramsRouteGET="{ id: aat.id }"
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
import list from "./list.vue";
import axios from "axios";
export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: { list },

    emits: ["close"],
    data() {
        return {
            aat: {
                AATName: "",
                AATDescription: "",
                AATCode: "",
            },
        };
    },
    methods: {
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
    },

    mounted() {
        this.loadAAT();
    },
};
</script>
