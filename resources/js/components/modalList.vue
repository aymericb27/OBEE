<template>
    <div
        v-if="visible"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
    >
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ title }}</h5>
                    <button type="button" class="close btn" @click="close">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="loading" class="text-center py-3 text-muted">
                        Chargement...
                    </div>

                    <div v-else>
                        <!-- 🔍 Barre de recherche -->
                        <div class="mb-3">
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Rechercher par code ou libellé..."
                                v-model.trim="searchQuery"
                            />
                        </div>

                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-10 p-2">Libellé</div>
                        </div>
                        <div
                            v-if="!paginatedList.length"
                            class="text-center p-2"
                        >
                            <h6>aucune donnée à afficher</h6>
                        </div>
                        <div
                            v-for="(item, index) in paginatedList"
                            :key="item.id"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                            class="row"
                        >
                            <div class="col-md-1 text-right p-2">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    v-model="selectedIds"
                                    :value="item.id"
                                />
                            </div>
                            <div class="col-md-1 p-2" :class="type">
                                {{ item.code }}
                            </div>
                            <p class="col-md-10 mb-0 p-2">
                                {{ item.name }}
                            </p>
                        </div>

                        <!-- Pagination -->
                        <nav
                            v-if="totalPages > 1"
                            class="mt-3 d-flex justify-content-center align-items-center"
                        >
                            <ul class="pagination mb-0">
                                <li
                                    class="page-item"
                                    :class="{ disabled: currentPage === 1 }"
                                >
                                    <button
                                        class="page-link"
                                        @click="changePage(currentPage - 1)"
                                        :disabled="currentPage === 1"
                                    >
                                        «
                                    </button>
                                </li>

                                <li
                                    v-for="page in totalPages"
                                    :key="page"
                                    class="page-item"
                                    :class="{ active: currentPage === page }"
                                >
                                    <button
                                        class="page-link"
                                        @click="changePage(page)"
                                    >
                                        {{ page }}
                                    </button>
                                </li>

                                <li
                                    class="page-item"
                                    :class="{
                                        disabled: currentPage === totalPages,
                                    }"
                                >
                                    <button
                                        class="page-link"
                                        @click="changePage(currentPage + 1)"
                                        :disabled="currentPage === totalPages"
                                    >
                                        »
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <div class="modal-footer">
                    <router-link
                        v-if="btnAddElement"
                        :to="{
                            name: 'createUE',
                            query: btnAddElementParam,
                        }"
                    >
                        <button class="btn btn-primary mr-auto">
                            {{ btnAddElementMessage }}
                        </button>
                    </router-link>

                    <button
                        v-if="btnAddModal"
                        class="btn btn-primary mr-auto"
                        @click="emitBtnModal()"
                    >
                        {{ btnAddElementMessage }}
                    </button>
                    <button class="btn btn-secondary" @click="close">
                        Annuler
                    </button>
                    <button class="btn btn-primary" @click="confirmSelection">
                        Ajouter
                    </button>
                </div>
            </div>
        </div>

        <!-- Overlay sombre -->
        <div class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ModalList",
    props: {
        btnAddModal: { type: Boolean, default: false },
        btnAddElement: { type: Boolean, default: false },
        btnAddElementRoute: { type: String, default: "" },
        btnAddElementMessage: { type: String, default: "" },
        btnAddElementParam: { type: Object, default: {} },
        routeGET: { type: String, required: true },
        title: { type: String, default: "Sélectionner des éléments" },
        visible: { type: Boolean, default: false },
        type: { type: String, default: "" },
        listToExclude: { type: Array, default: () => [] },
    },
    data() {
        return {
            list: [],
            selectedIds: [],
            loading: false,
            currentPage: 1,
            itemsPerPage: 10,
            searchQuery: "", // 🔍 nouveau champ
        };
    },
    computed: {
        // ✅ Filtrage local
        filteredList() {
            if (!this.searchQuery) return this.list;

            const query = this.searchQuery.toLowerCase();
            return this.list.filter(
                (item) =>
                    item.name.toLowerCase().includes(query) ||
                    item.code.toLowerCase().includes(query)
            );
        },

        totalPages() {
            return Math.ceil(this.filteredList.length / this.itemsPerPage);
        },

        paginatedList() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredList.slice(start, start + this.itemsPerPage);
        },
    },
    methods: {
        async loadList() {
            this.loading = true;
            try {
                const response = await axios.get(this.routeGET);

                const excludeIds = this.listToExclude.map((item) => item.id);
                this.list = response.data.filter(
                    (item) => !excludeIds.includes(item.id)
                );

                this.currentPage = 1;
            } catch (err) {
                console.error("Erreur de chargement :", err);
            } finally {
                this.loading = false;
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        close() {
            this.$emit("close");
            this.selectedIds = [];
            this.currentPage = 1;
            this.searchQuery = "";
        },

        confirmSelection() {
            const selectedItems = this.list.filter((item) =>
                this.selectedIds.includes(item.id)
            );
            this.$emit("selected", selectedItems);
            this.close();
        },
        emitBtnModal() {
            this.$emit("close");

            this.$emit("btnAddElementModal");
        },
    },
    mounted() {
        this.loadList();
    },
};
</script>

<style scoped>
.modal-backdrop {
    z-index: 1040;
}
.modal-dialog {
    z-index: 1050;
}
.page-item.disabled .page-link {
    pointer-events: none;
    opacity: 0.6;
}

.page-item.active .page-link {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}
</style>
