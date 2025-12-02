<template>
    <div v-if="isResearch" class="col-md-6 position-relative my-2">
        <input
            type="text"
            class="form-control ps-4"
            placeholder="Recherche..."
            v-model="search"
        />

        <i class="fa fa-search position-absolute search-icon"></i>
    </div>
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
            v-if="filteredItems.length"
            v-for="(item, index) in filteredItems"
            :key="item.id"
            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
            class="row m-auto"
        >
            <div class="col-md-1 p-3" v-if="listColonne.includes('code')">
                <router-link
                    :to="{
                        name: linkDetailed,
                        params: { id: item.id },
                    }"
                >
                    <h5 :class="typeList">
                        {{ item.code }}
                    </h5>
                </router-link>
            </div>
            <div class="col-md-9 p-3" v-if="listColonne.includes('name')">
                <h5 class="mb-0">
                    <span class="secondary_color">
                        {{ item.name }}
                    </span>
                    <span v-if="item.error" class="h-100 p-2">
                        <i
                            class="fa-solid fa-triangle-exclamation"
                            style="color: #f3aa24"
                        ></i>
                    </span>
                </h5>
            </div>
            <div class="col-md-1 p-2" v-if="listColonne.includes('semestre')">
                {{ item.semestre }}
            </div>
            <div class="col-md-1 p-3" v-if="listColonne.includes('ects')">
                {{ item.ects }}
            </div>
        </div>
        <div v-else><p class="p-2 text-center mb-0">Aucune donnée à afficher</p></div>
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
        isResearch: {
            type: Boolean,
            default: false,
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
            search: "", // <-- valeur de la recherche

            items: {},
        };
    },
    computed: {
        filteredItems() {
            if (!this.search) return this.items;

            const lower = this.search.toLowerCase();

            return this.items.filter(
                (item) =>
                    (item.code && item.code.toLowerCase().includes(lower)) ||
                    (item.name && item.name.toLowerCase().includes(lower))
            );
        },
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
<style>
.search-icon {
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: darkgray;
    pointer-events: none; /* évite que l’icône bloque les clics */
}
</style>
