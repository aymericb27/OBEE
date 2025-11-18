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
                    <h5 class="modal-title">Exportation</h5>
                    <button
                        type="button"
                        class="close btn"
                        @click="$emit('close')"
                    >
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="loading" class="text-center py-3 text-muted">
                        Chargement...
                    </div>

                    <div v-else>
                        <div v-if="filter.displayElement === 'UE'">
                            <div class="primary_color">
                                Exportation des unités d'enseignements :
                            </div>
                            <ul>
                                <li class="py-1" v-if="filter.onlyErrors">
                                    Avec des erreurs
                                </li>
                                <li class="py-1" v-if="filter.program === ''">
                                    Depuis tout les programmes
                                </li>
                                <li class="py-1" v-else="filter.program">
                                    depuis le programme : {{ program.name }}
                                </li>
                                <li class="py-1" v-if="filter.semester === ''">
                                    Des deux semestres
                                </li>
                                <li class="py-1" v-if="filter.semester === '1'">
                                    du 1er semestre
                                </li>
                                <li class="py-1" v-if="filter.semester === '2'">
                                    du 2ème semestre
                                </li>
                            </ul>
                        </div>
                        <div class="bg-light border p-2">
                            <p class="mb-2 primary_color">
                                Voulez vous extraire :
                            </p>
                            <ul>
                                <li class="mb-2">
                                    <div>
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            v-model="select.prog.all"
                                            :style="{
                                                backgroundColor: '#28a745',
                                            }"
                                        />
                                        Les programmes
                                        <ul>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="select.prog.code"
                                                />
                                                Code
                                            </li>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="select.prog.name"
                                                />
                                                Nom
                                            </li>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="select.prog.ects"
                                                />
                                                ECTS
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <div>
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            v-model="select.ue.all"
                                        />
                                        Les unités d'enseignements
                                    </div>
                                    <ul>
                                        <li>
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="select.ue.code"
                                            />
                                            Code
                                        </li>
                                        <li>
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="select.ue.name"
                                            />
                                            Nom
                                        </li>
                                        <li>
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="select.ue.ects"
                                            />
                                            ECTS
                                        </li>
                                        <li>
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="select.ue.description"
                                            />
                                            Description
                                        </li>
                                        <li>
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="select.ue.date_begin"
                                            />
                                            date de début
                                        </li>
                                        <li>
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="select.ue.date_end"
                                            />
                                            date de fin
                                        </li>
                                    </ul>
                                </li>
                                <li class="mb-2">
                                    <div>
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            v-model="select.aavvise.all"
                                        />
                                        Les acquis d'apprentissages visés
                                        <ul>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="
                                                        select.aavvise.code
                                                    "
                                                />
                                                Code
                                            </li>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="
                                                        select.aavvise.name
                                                    "
                                                />
                                                Nom
                                            </li>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="
                                                        select.aavvise
                                                            .description
                                                    "
                                                />
                                                Description
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                <li>
                                    <div>
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            v-model="select.aavprerequis.all"
                                        />
                                        Les prérequis
                                        <ul>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="
                                                        select.aavprerequis.code
                                                    "
                                                />
                                                Code
                                            </li>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="
                                                        select.aavprerequis.name
                                                    "
                                                />
                                                Nom
                                            </li>
                                            <li>
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    v-model="
                                                        select.aavprerequis
                                                            .description
                                                    "
                                                />
                                                Description
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="close">
                        Annuler
                    </button>
                    <button class="btn btn-primary" @click="confirmSelection">
                        télécharger
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ModalExport",
    props: {
        visible: {
            type: Boolean,
            required: true,
        },
        filter: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            searchQuery: "",
            loading: false,
            program: {},
			filename : '',
            select: {
                prog: {
                    all: true,
                    name: true,
                    code: true,
                    ects: true,
                },
                ue: {
                    all: true,
                    name: true,
                    code: true,
                    ects: true,
                    description: true,
                    date_begin: true,
                    date_end: true,
                },
                aavprerequis: {
                    all: true,
                    code: true,
                    name: true,
                    description: true,
                },
                aavvise: {
                    all: true,
                    code: true,
                    name: true,
                    description: true,
                },
            },
        };
    },
    watch: {
        // 🧠 Chaque fois que la modale devient visible
        visible(newVal) {
            if (newVal) {
                this.load(); // ✅ Appelle ta méthode de chargement
            }
        },
    },
    methods: {
        close() {
            this.$emit("close");
        },
        async load() {
            try {
                this.loading = true;
                if (this.filter.program) {
                    const response = await axios.get("/pro/get/detailed", {
                        params: {
                            id: this.filter.program,
                        },
                    });
                    this.program = response.data;
                }
            } catch (error) {
                console.error("Erreur de chargement :", error);
            } finally {
                this.loading = false;
            }
        },
        async confirmSelection() {
            const response = await axios
                .get(`/export/get/${this.filter.displayElement}`, {
                    responseType: "blob",
                    params: { select: this.select, filter: this.filter },
                })
                .then((response) => {
                    console.log(response);

                    const url = window.URL.createObjectURL(
                        new Blob([response.data])
                    );
                    const link = document.createElement("a");
                    link.href = url;
					if(this.filter.displayElement ==="UE"){
						this.filename = "unité d'enseignements"
					}
                    link.setAttribute(
                        "download",
                        `${this.filename}.xlsx`
                    );
                    document.body.appendChild(link);
                    link.click();
                });
        },
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
</style>
