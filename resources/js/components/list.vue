<template>
    <div class="row m-auto border-bottom">
        <div class="col-md-1 p-2" v-if="listColonne.includes('code')">Code</div>
        <div class="col-md-9 p-2" v-if="listColonne.includes('name')">Nom</div>
        <div class="col-md-1 p-2" v-if="listColonne.includes('semestre')">
            Semestre
        </div>
        <div class="col-md-1 p-2" v-if="listColonne.includes('ects')">ECTS</div>
    </div>
    <div
        :class="{ border: isBorder }"
        :style="isBorder ? { borderTop: '0px !important' } : {}"
    >
        <div
            v-for="(item, index) in items"
            :key="item.id"
            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
            class="row m-auto"
        >
            <div
                class="col-md-1 p-2"
                :class="typeList"
                v-if="listColonne.includes('code')"
            >
                {{ item.code }}
            </div>
            <div class="col-md-9 p-2" v-if="listColonne.includes('name')">
                <p class="primary_color mb-0">
                    <router-link
                        :to="{
                            name: linkDetailed,
                            params: { id: item.id },
                        }"
                    >
                        {{ item.name }}
                    </router-link>
                    <span v-if="item.error" class="h-100 p-2">
                        <i
                            class="fa-solid fa-triangle-exclamation"
                            style="color: #f3aa24"
                        ></i>
                    </span>
                </p>
            </div>
            <div class="col-md-1" v-if="listColonne.includes('semestre')"></div>
            <div class="col-md-1 p-2" v-if="listColonne.includes('ects')">
                {{ item.ects }}
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";
export default {
    props: {
        routeGET: String,
        linkDetailed: String,
        typeList: String,
        isBorder: {
            type: Boolean,
            default: false, // 🔹 false par défaut
        },
        listColonne: Array,
        paramsRouteGET: {
            type: Object, // car tu passes { id: ue.id }
            required: false,
            default: () => ({}), // au cas où rien n'est passé
        },
    },

    data() {
        return {
            items: {},
        };
    },
    methods: {
        async loadItems() {
            try {
                let response;
                if (
                    this.paramsRouteGET &&
                    Object.keys(this.paramsRouteGET).length > 0
                ) {
                    response = await axios.get(this.routeGET, {
                        params: this.paramsRouteGET,
                    });
                } else {
                    response = await axios.get(this.routeGET);
                }
                this.items = response.data;
            } catch (error) {
                console.error("Erreur loadItems :", error);
            }
        },
    },

    mounted() {
        this.loadItems();
    },
};
</script>
