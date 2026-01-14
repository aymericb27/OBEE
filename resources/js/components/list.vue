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
        <div
            class="col-md-2 p-2"
            v-if="listColonne.includes('element_constitutif_aav')"
        >
            Element constitutif
        </div>
    </div>

    <div
        :class="{ border: isBorder }"
        :style="isBorder ? { borderTop: '0px !important' } : {}"
    >
        <div v-if="filteredItems.length">
            <div
                v-for="(item, index) in paginatedItems"
                :key="item.id"
                :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                class="row m-auto"
            >
                <div class="col-md-1 p-3" v-if="listColonne.includes('code')">
                    <router-link
                        :to="{ name: linkDetailed, params: { id: item.id } }"
                    >
                        <h5 :class="typeList">{{ item.code }}</h5>
                    </router-link>
                </div>

                <div class="col-md-9 p-3" v-if="listColonne.includes('name')">
                    <h5 class="mb-0">
                        <span class="secondary_color">{{ item.name }}</span>
                        <span v-if="item.error" class="h-100 p-2">
                            <i
                                class="fa-solid fa-triangle-exclamation"
                                style="color: #f3aa24"
                            ></i>
                        </span>
                    </h5>
                </div>

                <div
                    class="col-md-1 p-2"
                    v-if="listColonne.includes('semestre')"
                >
                    {{ item.semestre }}
                </div>

                <div class="col-md-1 p-3" v-if="listColonne.includes('ects')">
                    {{ item.ects }}
                </div>

                <div
                    class="col-md-1 p-3"
                    v-if="
                        listColonne.includes('element_constitutif_aav') &&
                        paramsRouteGET.id != item.ue_source_id
                    "
                >
                    <router-link
                        :to="{
                            name: 'ue-detail',
                            params: { id: item.ue_source_id },
                        }"
                    >
                        <h5 class="UE">{{ item.ue_source_code }}</h5>
                    </router-link>
                </div>
            </div>

            <!-- ✅ Pagination uniquement si > 10 -->
            <div
                v-if="showPagination"
                class="d-flex justify-content-between align-items-center p-2"
            >
                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="currentPage === 1"
                    @click="prevPage"
                >
                    <i class="fa fa-chevron-left"></i>
                </button>

                <span class="small">
                    Page {{ currentPage }} / {{ totalPages }} —
                    {{ filteredItems.length }} résultats
                </span>

                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="currentPage === totalPages"
                    @click="nextPage"
                >
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <div v-else>
            <p class="p-2 text-center mb-0">Aucune donnée à afficher</p>
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
        isBorder: { type: Boolean, default: false },
        isResearch: { type: Boolean, default: false },
        listColonne: Array,
        paramsRouteGET: {
            type: Object,
            required: false,
            default: () => ({}),
        },
    },

    data() {
        return {
            search: "",
            items: [],

            // ✅ pagination
            pageSize: 10,
            currentPage: 1,
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

        totalPages() {
            return Math.max(
                1,
                Math.ceil(this.filteredItems.length / this.pageSize)
            );
        },

        showPagination() {
            return this.filteredItems.length > this.pageSize;
        },

        paginatedItems() {
            const start = (this.currentPage - 1) * this.pageSize;
            return this.filteredItems.slice(start, start + this.pageSize);
        },
    },

    watch: {
        // ✅ quand la recherche change, on revient page 1
        search() {
            this.currentPage = 1;
        },

        // ✅ si la liste change (re-fetch), on revient page 1
        items() {
            this.currentPage = 1;
        },

        // ✅ si les params changent, on reload
        paramsRouteGET: {
            deep: true,
            handler() {
                this.loadItems();
            },
        },
        routeGET() {
            this.loadItems();
        },
    },

    methods: {
        async loadItems() {
            try {
                const hasParams =
                    this.paramsRouteGET &&
                    Object.keys(this.paramsRouteGET).length > 0;

                const response = hasParams
                    ? await axios.get(this.routeGET, {
                          params: this.paramsRouteGET,
                      })
                    : await axios.get(this.routeGET);

                this.items = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                console.error("Erreur loadItems :", error);
                this.items = [];
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) this.currentPage++;
        },
        prevPage() {
            if (this.currentPage > 1) this.currentPage--;
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
