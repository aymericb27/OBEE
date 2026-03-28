<template>
    <BaseLoader v-if="isLoading" text="Chargement..." size="md" />

    <div v-else>
        <div v-if="isResearch" class="d-flex align-items-center my-2 px-2">
            <div class="search-wrapper position-relative flex-grow-1 mr-2">
                <input
                    type="text"
                    class="form-control ps-4"
                    placeholder="Recherche..."
                    v-model="search"
                />
                <i class="fa fa-search position-absolute search-icon"></i>
            </div>
            <router-link v-if="actionButton?.to" :to="actionButton.to">
                <button class="btn btn-primary">
                    {{ actionButton.label }}
                </button>
            </router-link>
        </div>
        <div
            class="d-flex justify-content-end align-items-center gap-2 mb-2 mr-2"
        >
            <label class="mb-0 small mr-2">Nombre d'élément à afficher</label>
            <select
                v-model.number="pageSize"
                class="form-control form-select form-select-sm w-auto"
            >
                <option
                    v-for="size in pageSizeOptions"
                    :key="size"
                    :value="size"
                >
                    {{ size }}
                </option>
            </select>
        </div>
        <div class="rounded" :class="isResearch ? 'border-top' : 'border'">
            <div class="row m-auto bg-light border-bottom">
                <div
                    class="col-md-2 p-2 pl-3"
                    v-if="listColonne.includes('code')"
                >
                    Code
                </div>
                <div
                    :class="nameColumnClass()"
                    class="p-2 pl-3"
                    v-if="listColonne.includes('name')"
                >
                    Nom
                </div>
                <div
                    class="col-md-1 p-2 pl-3"
                    v-if="listColonne.includes('semestre')"
                >
                    Semestre
                </div>
                <div
                    class="col-md-1 p-2 pl-3"
                    v-if="listColonne.includes('ects')"
                >
                    ECTS
                </div>

                <div
                    class="col-md-2 p-2 pl-3"
                    v-if="listColonne.includes('ues')"
                >
                    UE liées
                </div>
                <div
                    class="col-md-2 p-2 pl-3"
                    v-if="listColonne.includes('programme')"
                >
                    Programme
                </div>
                <div
                    class="col-md-1 p-2 pl-3"
                    v-if="listColonne.includes('contribution')"
                >
                    contribution
                </div>
                <div
                    class="col-md-2 p-2 pl-3"
                    v-if="listColonne.includes('element_constitutif_aav')"
                >
                    Element constitutif
                </div>
                <div
                    class="col-md-2 p-2 pl-3"
                    v-if="listColonne.includes('aat_contributions')"
                >
                    AAT
                </div>
                <div
                    class="col-md-2 p-2 pl-3"
                    v-if="listColonne.includes('aat_contributions')"
                >
                    Contribution
                </div>
            </div>

            <div>
                <div v-if="filteredItems.length">
                    <div
                        v-for="(item, index) in paginatedItems"
                        :key="item.row_key ?? `${item.id}-${index}`"
                        :class="[
                            index != paginatedItems.length - 1
                                ? 'border-bottom'
                                : '',
                        ]"
                        class="row m-auto"
                    >
                        <div
                            class="col-md-2 p-3"
                            v-if="listColonne.includes('code')"
                        >
                            <router-link
                                :to="{
                                    name: linkDetailed,
                                    params: { id: item.id },
                                }"
                            >
                                <h6
                                    :class="typeList"
                                    class="m-0"
                                    style="font-size: 1.1em"
                                >
                                    {{ item.code }}
                                </h6>
                            </router-link>
                        </div>

                        <div
                            :class="`${nameColumnClass()} p-3`"
                            v-if="listColonne.includes('name')"
                        >
                            <div class="mb-0 d-flex align-items-center">
                                <h6
                                    class="secondary_color mb-0"
                                    style="font-size: 1.1em"
                                >
                                    {{ item.name }}
                                </h6>
                                <AnomalyBadge
                                    class="ml-2"
                                    :summary="item.anomaly_summary"
                                />
                                <span v-if="item.error" class="h-100 p-2">
                                    <i
                                        class="fa-solid fa-triangle-exclamation"
                                        style="color: #f3aa24"
                                    ></i>
                                </span>
                            </div>
                        </div>

                        <div
                            class="col-md-1 p-2"
                            v-if="listColonne.includes('semestre')"
                        >
                            {{ item.semestre }}
                        </div>

                        <div
                            class="col-md-1 p-3"
                            v-if="listColonne.includes('ects')"
                        >
                            {{ item.ects }}
                        </div>
                        <div
                            class="col-md-2 p-3"
                            v-if="listColonne.includes('ues')"
                        >
                            <span
                                v-if="
                                    Array.isArray(item.ues) && item.ues.length
                                "
                            >
                                <template
                                    v-for="(ue, ueIndex) in item.ues"
                                    :key="ue.id"
                                >
                                    <router-link
                                        :to="{
                                            name: 'ue-detail',
                                            params: { id: ue.id },
                                        }"
                                    >
                                        <h6
                                            class="UE mr-1 d-inline-block"
                                            style="font-size: 1.1em"
                                        >
                                            {{ ue.code }}
                                        </h6>
                                    </router-link>
                                    <AnomalyBadge
                                        class="mr-1"
                                        :summary="ue.anomaly_summary"
                                    />
                                    <span v-if="ueIndex < item.ues.length - 1"
                                        >,
                                    </span>
                                </template>
                            </span>
                            <span v-else>-</span>
                        </div>
                        <div
                            class="col-md-2 p-3"
                            v-if="listColonne.includes('programme')"
                        >
                            <router-link
                                :to="{
                                    name: 'pro-detail',
                                    params: { id: item.fk_programme },
                                }"
                            >
                                <h6 class="PRO m-0" style="font-size: 1.1em">
                                    {{ item.programme_code }}
                                </h6>
                            </router-link>
                        </div>
                        <div
                            class="col-md-1 p-3 text-center"
                            v-if="listColonne.includes('contribution')"
                        >
                            <span
                                :class="
                                    contributionClass(
                                        item.contribution,
                                        item.level_contribution,
                                    )
                                "
                            >
                                {{ item.contribution }}
                            </span>
                        </div>

                        <div
                            class="col-md-2 p-3"
                            v-if="
                                listColonne.includes(
                                    'element_constitutif_aav',
                                ) && paramsRouteGET.id != item.ue_source_id
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

                        <div
                            class="col-md-2 p-3"
                            v-if="listColonne.includes('aat_contributions')"
                        >
                            <template
                                v-if="
                                    Array.isArray(item.aat_contributions) &&
                                    item.aat_contributions.length
                                "
                            >
                                <template
                                    v-for="(aat, aatIndex) in item.aat_contributions"
                                    :key="`${item.id}-aat-${aat.aat_id}-${aatIndex}`"
                                >
                                    <router-link
                                        :to="{
                                            name: 'aat-detail',
                                            params: { id: aat.aat_id },
                                        }"
                                    >
                                        <h6 style="font-size: 1.1em;" class="AAT d-inline-block mr-1">{{
                                            aat.aat_code
                                        }}</h6>
                                    </router-link>
                                    <span
                                        v-if="
                                            aatIndex <
                                            item.aat_contributions.length - 1
                                        "
                                    >
                                        ,
                                    </span>
                                </template>
                            </template>
                            <span v-else>-</span>
                        </div>
                        <div
                            class="col-md-2 p-3"
                            v-if="listColonne.includes('aat_contributions')"
                        >
                            <template
                                v-if="
                                    Array.isArray(item.aat_contributions) &&
                                    item.aat_contributions.length
                                "
                            >
                                <template
                                    v-for="(aat, aatIndex) in item.aat_contributions"
                                    :key="`${item.id}-aat-contrib-${aat.aat_id}-${aatIndex}`"
                                >
                                    <span
                                        :class="
                                            contributionClass(
                                                aat.contribution,
                                                aat.aat_level_contribution,
                                            )
                                        "
                                    >
                                        {{ aat.contribution }}
                                    </span>
                                    <span
                                        v-if="
                                            aatIndex <
                                            item.aat_contributions.length - 1
                                        "
                                    >
                                        ,
                                    </span>
                                </template>
                            </template>
                            <span v-else>-</span>
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
        </div>
    </div>
</template>

<script>
import BaseLoader from "./modal/baseLoader.vue";
import AnomalyBadge from "./common/AnomalyBadge.vue";

import axios from "axios";

export default {
    components: { BaseLoader, AnomalyBadge },

    props: {
        routeGET: String,
        linkDetailed: String,
        typeList: String,
        isBorder: { type: Boolean, default: false },
        isResearch: { type: Boolean, default: false },
        listColonne: Array,
        sortByCode: { type: Boolean, default: false },
        paramsRouteGET: {
            type: Object,
            required: false,
            default: () => ({}),
        },
        actionButton: {
            type: Object,
            required: false,
            default: () => null,
        },
    },

    data() {
        return {
            search: "",
            items: [],
            isLoading: false,
            pageSize: 10,
            pageSizeOptions: [10, 25, 50],
            currentPage: 1,
        };
    },

    computed: {
        paramsSignature() {
            return JSON.stringify(this.paramsRouteGET || {});
        },
        filteredItems() {
            const lower = this.search.toLowerCase();
            const filtered = !this.search
                ? [...this.items]
                : this.items.filter(
                      (item) =>
                          (item.code &&
                              item.code.toLowerCase().includes(lower)) ||
                          (item.name &&
                              item.name.toLowerCase().includes(lower)),
                  );

            if (!this.sortByCode) return filtered;

            return filtered.sort((a, b) => {
                const codeA = a?.code ?? "";
                const codeB = b?.code ?? "";

                if (codeA && codeB) {
                    return codeA.localeCompare(codeB, undefined, {
                        numeric: true,
                        sensitivity: "base",
                    });
                }

                if (codeA) return -1;
                if (codeB) return 1;

                const nameA = a?.name ?? "";
                const nameB = b?.name ?? "";
                return nameA.localeCompare(nameB, undefined, {
                    numeric: true,
                    sensitivity: "base",
                });
            });
        },

        totalPages() {
            return Math.max(
                1,
                Math.ceil(this.filteredItems.length / this.pageSize),
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

        pageSize() {
            this.currentPage = 1;
        },

        // ✅ si la liste change (re-fetch), on revient page 1
        items() {
            this.currentPage = 1;
        },

        // ✅ on reload uniquement si la valeur des params change réellement
        paramsSignature() {
            this.loadItems();
        },
        routeGET() {
            this.loadItems();
        },
    },

    methods: {
        nameColumnClass() {
            const hasUes = this.listColonne.includes("ues");
            const hasProgramme = this.listColonne.includes("programme");
            const hasElementConstitutif = this.listColonne.includes(
                "element_constitutif_aav",
            );
            const hasAATContributions =
                this.listColonne.includes("aat_contributions");
            if (
                hasAATContributions &&
                hasElementConstitutif &&
                !hasUes &&
                !hasProgramme
            )
                return "col-md-4";
            if (hasAATContributions && !hasUes && !hasProgramme)
                return "col-md-6";
            if (hasUes && hasProgramme) return "col-md-4";
            if (hasUes || hasProgramme) return "col-md-6";
            return "col-md-8";
        },
        contributionClass(value, max) {
            const safeMax = Number(max) > 0 ? Number(max) : 10;
            const oneThird = Math.ceil(safeMax / 3);
            const twoThirds = Math.ceil((safeMax * 2) / 3);
            if (value == 10) return "strong_mapping strong_ten_mapping";
            if (value > twoThirds) return "strong_mapping";
            if (value > oneThird) return "medium_mapping";
            return "weak_mapping";
        },
        async loadItems() {
            this.isLoading = true;

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
            } finally {
                this.isLoading = false; // ✅
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
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: darkgray;
    pointer-events: none; /* évite que l’icône bloque les clics */
}
</style>
