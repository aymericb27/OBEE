<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left"></i> Retour
        </a>
    </div>
    <div class="container pb-3">
        <div v-if="$route.query.message" class="alert alert-success mt-3">
            <i
                class="fa-solid fa-check green mr-2"
                style="color: darkgreen"
            ></i>
            <span> {{ $route.query.message }} </span>
        </div>
        <div v-if="formErrors" class="alert alert-danger mt-3">
            <i
                class="fa-solid fa-triangle-exclamation mr-2"
                style="color: crimson"
            ></i>
            <span>{{ formErrors }}</span>
        </div>
        <form @submit.prevent="saveProgram" class="border p-4 rounded bg-white">
            <h3 class="primary_color mb-4 text-center">
                {{ form.id ? "Modification" : "Creation" }} d'un programme
            </h3>
            <div class="row mb-4">
                <div class="col-md-3">
                    <h5 class="primary_color">
                        Sigle du programme
                        <strong class="text-danger">*</strong>
                    </h5>

                    <input
                        type="text"
                        v-model="form.code"
                        class="form-control m-auto"
                        required
                    />
                </div>

                <div class="col-md-9">
                    <h5 class="primary_color">
                        Intitulé du programme
                        <strong class="text-danger">*</strong>
                    </h5>

                    <input
                        type="text"
                        v-model="form.name"
                        class="form-control m-auto"
                        required
                    />
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3">
                    <h5 class="primary_color">Nombre de semestres</h5>
                    <select
                        v-model="form.semestre"
                        class="form-control m-auto"
                        required
                    >
                        <option :value="2">2 semestres</option>
                        <option :value="4">4 semestres</option>
                        <option :value="6">6 semestres</option>
                        <option :value="8">8 semestres</option>
                        <option :value="10">10 semestres</option>
                    </select>
                </div>
            </div>
            <div class="mb-4" v-if="form.semestre">
                <h5 class="primary_color mb-3">
                    Répartition des credits par semestre
                </h5>

                <div
                    v-for="n in Number(form.semestre)"
                    :key="n"
                    class="row align-items-center mb-2"
                >
                    <div class="col-md-3 text-end">
                        <h6 class="secondary_color">
                            Semestre {{ n }}
                            <strong class="text-danger">*</strong>
                        </h6>
                    </div>
                    <div class="col-md-3 pr-0">
                        <input
                            required
                            type="number"
                            min="0"
                            class="form-control"
                            v-model.number="form.semestresCredits[n]"
                            placeholder="Credits"
                        />
                    </div>
                </div>
            </div>
            <div class="mt-3 mb-5 w-50">
                <div class="d-flex justify-content-between border-top pt-2">
                    <span
                        ><h6 class="primary_color">
                            Total crédits semestres
                        </h6></span
                    >
                    <span
                        ><h6>{{ semestreCreditsTotal }}</h6></span
                    >
                </div>
            </div>
            <div class="listComponent mb-5">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        Liste des prérequis
                    </h5>
                    <button
                        type="button"
                        class="btn btn-primary ml-2 mb-2"
                        @click="openModalPrerequis()"
                    >
                        ajouter un prérequis
                    </button>
                </div>
                <div class="border rounded">
                    <div class="row border-bottom m-0">
                        <div class="col-md-1"></div>
                        <div class="col-md-1 p-2">Code</div>
                        <div class="col-md-10 p-2">Nom</div>
                    </div>
                    <div
                        v-if="!form.aavprerequis.length"
                        class="p-2 text-center"
                    >
                        aucune donnée a afficher
                    </div>
                    <div
                        v-else
                        v-for="(aav, index) in form.aavprerequis"
                        class="row m-0"
                        :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                    >
                        <div class="col-md-1 text-right p-2">
                            <i
                                @click="removeElement('aavprerequis', aav.id)"
                                class="text-danger fa fa-close pr-0"
                                style="cursor: pointer"
                            ></i>
                        </div>
                        <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                        <div class="col-md-10 p-2">{{ aav.name }}</div>
                    </div>
                </div>
            </div>
            <div class="listComponent mb-5">
                <div class="mb-2">
                    <h5 class="d-inline-block primary_color">
                        Liste des AAT du programme
                    </h5>
                    <button
                        type="button"
                        class="btn btn-primary ml-2 mb-2"
                        @click="openCreateProgramAATModal"
                    >
                        créer un AAT
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-primary ml-2 mb-2"
                        @click="openSourceProgramModal"
                    >
                        copier depuis un autre programme
                    </button>
                </div>
                <div class="border rounded">
                    <div class="row border-bottom m-0">
                        <div class="col-md-1"></div>
                        <div class="col-md-2 p-2">Code</div>
                        <div class="col-md-5 p-2">Nom</div>
                        <div class="col-md-2 p-2">Niveaux</div>
                        <div class="col-md-2 p-2">Source</div>
                    </div>
                    <div
                        v-if="!displayedProgramAats.length"
                        class="p-2 text-center"
                    >
                        aucune donnée a afficher
                    </div>
                    <div
                        v-else
                        v-for="(aat, index) in displayedProgramAats"
                        :key="aat.row_key"
                        class="row m-0"
                        :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                    >
                        <div class="col-md-1 text-right p-2">
                            <i
                                v-if="aat.is_pending"
                                @click="removePendingProgramAAT(aat.temp_id)"
                                class="text-danger fa fa-close pr-0"
                                style="cursor: pointer"
                            ></i>
                        </div>
                        <div class="col-md-2 p-2 AAT">
                            {{ aat.code || "(auto)" }}
                        </div>
                        <div class="col-md-5 p-2">{{ aat.name }}</div>
                        <div class="col-md-2 p-2">
                            {{ aat.level_contribution }}
                        </div>
                        <div class="col-md-2 p-2 small">
                            {{ aat.origin_label }}
                        </div>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary mt-3">
                {{ form.id ? "Modifier le" : "Créer le" }} Programme
            </button>
        </form>
    </div>
    <div
        v-if="showModalCreateAAV"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
    >
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title primary_color">
                        Introduire un prerequis
                    </h5>
                    <button
                        type="button"
                        class="close btn"
                        @click="closeModalAAV"
                    >
                        <span>&times;</span>
                    </button>
                </div>

                <!-- BODY -->
                <div class="modal-body p-4">
                    <div v-if="formAAvErrors" class="alert alert-danger mt-3">
                        <i
                            class="fa-solid fa-triangle-exclamation mr-2"
                            style="color: crimson; font-size: 24px"
                        ></i>
                        <span> {{ formAAvErrors }} </span>
                    </div>
                    <div class="form-group mb-3">
                        <h6 class="primary_color">
                            Libelle
                            <strong class="text-danger">*</strong>
                        </h6>
                        <input
                            type="text"
                            class="form-control"
                            required
                            v-model="aavForm.name"
                        />
                    </div>
                    <div class="form-group mb-3">
                        <h6 class="primary_color">Description</h6>
                        <textarea
                            class="form-control"
                            rows="3"
                            v-model="aavForm.description"
                        ></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <h6 class="primary_color">
                            Acquis d'apprentissage Terminal
                        </h6>
                        <select
                            class="form-control"
                            v-model="aavForm.selectedAATId"
                            @change="addAAT"
                        >
                            <option value="" disabled>
                                — Selectionner un AAT —
                            </option>
                            <option
                                v-for="aat in listAAT"
                                :key="aat.id"
                                :value="aat.id"
                                :disabled="isAATAlreadySelected(aat.id)"
                            >
                                {{ aat.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="aavForm.aatSelected.length" class="mt-4">
                        <h6 class="mb-3 primary_color">AAT selectionnes</h6>

                        <div
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                            v-for="(aat, index) in aavForm.aatSelected"
                            :key="aat.id"
                            class="d-flex align-items-center rounded p-2"
                        >
                            <!-- SUPPRIMER -->
                            <button
                                class="btn btn-sm me-3"
                                @click="removeAAT(aat.id)"
                            >
                                <i
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </button>

                            <!-- NOM -->
                            <div class="flex-grow-1">
                                {{ aat.name }}
                            </div>
                            <!-- CONTRIBUTION -->
                            <select
                                class="form-control w-25 ms-3"
                                v-model="aat.contribution"
                            >
                                <option
                                    v-for="level in aat.level_contribution"
                                    :key="level"
                                    :value="level"
                                >
                                    {{ level }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button
                        class="btn btn-secondary"
                        type="button"
                        @click="closeModalAAV"
                    >
                        Annuler
                    </button>
                    <button
                        class="btn btn-primary"
                        type="submit"
                        @click="submitAAV"
                    >
                        Créer
                    </button>
                </div>
            </div>
        </div>

        <!-- BACKDROP -->
        <div class="modal-backdrop fade show"></div>
    </div>
    <modalList
        v-if="showModalPrerequis"
        :visible="showModalPrerequis"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aavPrerequisToExclude"
        :btnAddModal="true"
        @btnAddElementModal="handleNewAAV"
        btnAddElementMessage="Créer un prerequis"
        type="AAV"
        @close="showModalPrerequis = false"
        @selected="handleSelected"
    />

    <div
        v-if="showModalCreateProgramAAT"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
    >
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title primary_color">
                        Créer un AAT pour ce programme
                    </h5>
                    <button
                        type="button"
                        class="close btn"
                        @click="closeProgramAATModal"
                    >
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <div v-if="formAATErrors" class="alert alert-danger mt-3">
                        <i
                            class="fa-solid fa-triangle-exclamation mr-2"
                            style="color: crimson"
                        ></i>
                        <span>{{ formAATErrors }}</span>
                    </div>
                    <div class="row">
                        <div class="form-group mb-3 col-md-3">
                            <h6 class="primary_color">Sigle (optionnel)</h6>
                            <input
                                type="text"
                                class="form-control"
                                v-model.trim="aatDraft.code"
                            />
                        </div>
                        <div class="form-group mb-3 col-md-9">
                            <h6 class="primary_color">
                                Libelle
                                <strong class="text-danger">*</strong>
                            </h6>
                            <input
                                type="text"
                                class="form-control"
                                v-model.trim="aatDraft.name"
                            />
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <h6 class="primary_color">
                            Nombre de niveaux de contribution
                            <strong class="text-danger">*</strong>
                        </h6>
                        <select
                            class="form-control"
                            v-model.number="aatDraft.level_contribution"
                        >
                            <option
                                v-for="level in levelOptions"
                                :key="level"
                                :value="level"
                            >
                                {{ level }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <h6 class="primary_color">Description</h6>
                        <textarea
                            class="form-control"
                            rows="4"
                            v-model.trim="aatDraft.description"
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-secondary"
                        type="button"
                        @click="closeProgramAATModal"
                    >
                        Annuler
                    </button>
                    <button
                        class="btn btn-primary"
                        type="button"
                        @click="addProgramAATDraft"
                    >
                        Ajouter
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-backdrop fade show"></div>
    </div>

    <modalList
        v-if="showModalSourceProgram"
        :visible="showModalSourceProgram"
        routeGET="/pro/get"
        title="Choisir le programme source"
        type="PRO"
        :singleSelect="true"
        @close="showModalSourceProgram = false"
        @selected="handleSelectedSourceProgram"
    />

    <modalList
        v-if="showModalCopySourceAAT && copyAatRoute"
        :visible="showModalCopySourceAAT"
        :routeGET="copyAatRoute"
        title="Choisir les AAT a copier"
        type="AAT"
        :listToExclude="copySourceAatToExclude"
        @close="showModalCopySourceAAT = false"
        @selected="handleSelectedSourceAAT"
    />
</template>
<script>
import modalList from "../modalList.vue";

import axios from "axios";

export default {
    components: { modalList },

    props: {
        id: {
            type: [String, Number],
        },
    },
    data() {
        return {
            formAAvErrors: null,
            formAATErrors: null,
            formErrors: null,
            showModalPrerequis: false,
            showModalCreateAAV: false,
            showModalCreateProgramAAT: false,
            showModalSourceProgram: false,
            showModalCopySourceAAT: false,
            selectedSourceProgram: null,
            persistedProgramAATs: [],
            pendingProgramAATs: [],
            modalTarget: "",
            modalRoute: "",
            modalTitle: "",
            aavPrerequisToExclude: [],
            listAAT: [],
            form: {
                aavprerequis: [],
                id: null,
                code: "",
                name: "",
                ects: "",
                semestre: 6, // valeur par defaut
                semestresCredits: {}, // { 1: 30, 2: 30, ... }
            },
            aavForm: {
                selectedAATId: "",
                name: "",
                description: "",
                aatSelected: [], // [{ id, name, contribution }]
                contribution: "",
            },
            aatDraft: {
                code: "",
                name: "",
                description: "",
                level_contribution: 3,
            },
        };
    },
    watch: {
        "form.semestre"(newVal) {
            const count = Number(newVal);
            const credits = { ...this.form.semestresCredits };

            // créer les semestres manquants
            for (let i = 1; i <= count; i++) {
                if (credits[i] === undefined) credits[i] = 0;
            }

            // supprimer les semestres en trop
            Object.keys(credits).forEach((key) => {
                if (key > count) delete credits[key];
            });

            this.form.semestresCredits = credits;
        },
        semestreCreditsTotal(val) {
            this.form.ects = val;
        },
    },

    mounted() {
        // mode edition
        if (this.id) {
            this.form.id = Number(this.id);
            this.loadPRO();
            this.loadProgramAATs(this.form.id);
        }
    },
    computed: {
        semestreCreditsTotal() {
            const credits = this.form.semestresCredits || {};
            return Object.values(credits).reduce(
                (sum, v) => sum + (Number(v) || 0),
                0,
            );
        },
        levelOptions() {
            return [3, 4, 5, 6, 7, 8, 9, 10];
        },
        displayedProgramAats() {
            const persisted = (this.persistedProgramAATs || []).map((row) => ({
                ...row,
                row_key: `persisted-${row.id}`,
                is_pending: false,
                origin_label: "deja dans le programme",
            }));

            const pending = (this.pendingProgramAATs || []).map((row) => ({
                ...row,
                row_key: `pending-${row.temp_id}`,
                is_pending: true,
                origin_label:
                    row.origin === "copy"
                        ? `copie de ${row.source_program_code || "programme source"}`
                        : "cree dans ce formulaire",
            }));

            return [...persisted, ...pending];
        },
        copyAatRoute() {
            const sourceProgramId = Number(this.selectedSourceProgram?.id);
            if (!Number.isInteger(sourceProgramId) || sourceProgramId <= 0) {
                return "";
            }
            return `/aat/get?program_id=${sourceProgramId}`;
        },
        copySourceAatToExclude() {
            const currentProgramId = Number(this.form.id);
            const sourceProgramId = Number(this.selectedSourceProgram?.id);
            const sourceEqualsCurrent =
                Number.isInteger(currentProgramId) &&
                currentProgramId > 0 &&
                currentProgramId === sourceProgramId;

            const ids = [];

            if (sourceEqualsCurrent) {
                for (const aat of this.persistedProgramAATs) {
                    ids.push(Number(aat.id));
                }
            }

            for (const pending of this.pendingProgramAATs) {
                if (
                    Number(pending.source_program_id) === sourceProgramId &&
                    Number.isInteger(Number(pending.source_aat_id))
                ) {
                    ids.push(Number(pending.source_aat_id));
                }
            }

            return [...new Set(ids)]
                .filter((id) => Number.isInteger(id) && id > 0)
                .map((id) => ({ id }));
        },
    },

    methods: {
        addAAT() {
            if (!this.aavForm.selectedAATId) return;

            const selectedId = Number(this.aavForm.selectedAATId);
            const aat = this.listAAT.find(
                (a) => Number(a.id) === selectedId,
            );

            if (!aat) return;

            this.aavForm.aatSelected.push({
                id: aat.id,
                name: aat.name,
                contribution: 1, // valeur par defaut
                level_contribution: aat.level_contribution,
            });

            this.aavForm.selectedAATId = "";
        },
        async submitAAV() {
            this.formAAvErrors = null;
            if (!this.aavForm.name) {
                this.formAAvErrors = "Le champs libelle doit etre present";
                return;
            }
            const payload = {
                name: this.aavForm.name,
                description: this.aavForm.description,
                aat: this.aavForm.aatSelected.map((a) => ({
                    id: a.id,
                    contribution: a.contribution,
                })),
            };
            try {
                const response = await axios.post("/aav/store", payload);
                const createdAAV = response.data.aav;

                this.form.aavprerequis.push(createdAAV);

                this.closeModalAAV();
            } catch (error) {
                this.formAAvErrors = this.extractRequestError(error);
            }
        },
        removeAAT(id) {
            this.aavForm.aatSelected = this.aavForm.aatSelected.filter(
                (a) => Number(a.id) !== Number(id),
            );
        },
        isAATAlreadySelected(id) {
            return this.aavForm.aatSelected.some(
                (a) => Number(a.id) === Number(id),
            );
        },
        handleSelected(selectedItems) {
            if (this.modalTarget === "aavprerequis") {
                const existing = new Set(
                    this.form.aavprerequis.map((item) => Number(item.id)),
                );
                for (const item of selectedItems || []) {
                    const itemId = Number(item.id);
                    if (!existing.has(itemId)) {
                        this.form.aavprerequis.push(item);
                        existing.add(itemId);
                    }
                }
            }
        },
        removeElement(type, id) {
            if (
                !this.form ||
                !this.form[type] ||
                !Array.isArray(this.form[type])
            )
                return;

            const index = this.form[type].findIndex(
                (item) => Number(item.id) === Number(id),
            );

            if (index !== -1) {
                this.form[type].splice(index, 1);
            }
        },
        handleNewAAV() {
            this.loadAAT();
            this.showModalCreateAAV = true;
        },
        async loadAAT() {
            const response = await axios.get("/aat/get");
            this.listAAT = response.data;
        },
        openModalPrerequis() {
            this.modalTarget = "aavprerequis";
            this.modalRoute = "/aav/pro/prerequis/get";
            this.modalTitle = "Ajouter des prerequis";
            this.aavPrerequisToExclude = [...this.form.aavprerequis];
            this.showModalPrerequis = true;
        },
        closeModalAAV() {
            this.showModalCreateAAV = false;
            this.resetAAVForm();
        },

        resetAAVForm() {
            this.aavForm = {
                selectedAATId: "",
                name: "",
                description: "",
                aatSelected: [],
                contribution: "",
            };
        },
        openCreateProgramAATModal() {
            this.formAATErrors = null;
            this.resetProgramAATDraft();
            this.showModalCreateProgramAAT = true;
        },
        closeProgramAATModal() {
            this.showModalCreateProgramAAT = false;
            this.resetProgramAATDraft();
        },
        resetProgramAATDraft() {
            this.aatDraft = {
                code: "",
                name: "",
                description: "",
                level_contribution: 3,
            };
        },
        addProgramAATDraft() {
            this.formAATErrors = null;

            const name = (this.aatDraft.name || "").trim();
            const level = Number(this.aatDraft.level_contribution);

            if (!name) {
                this.formAATErrors = "Le champ libelle est obligatoire.";
                return;
            }

            if (!Number.isInteger(level) || level < 3 || level > 10) {
                this.formAATErrors =
                    "Le niveau de contribution doit etre entre 3 et 10.";
                return;
            }

            this.pendingProgramAATs.push({
                temp_id: `new-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                code: (this.aatDraft.code || "").trim(),
                name,
                description: (this.aatDraft.description || "").trim(),
                level_contribution: level,
                origin: "create",
                source_program_id: null,
                source_program_code: null,
                source_aat_id: null,
            });

            this.closeProgramAATModal();
        },
        removePendingProgramAAT(tempId) {
            this.pendingProgramAATs = this.pendingProgramAATs.filter(
                (aat) => aat.temp_id !== tempId,
            );
        },
        normalizeAATLevel(value) {
            const level = Number(value);
            if (!Number.isFinite(level)) {
                return 3;
            }
            return Math.min(10, Math.max(3, Math.trunc(level)));
        },
        openSourceProgramModal() {
            this.formErrors = null;
            this.showModalSourceProgram = true;
        },
        handleSelectedSourceProgram(selectedItems) {
            const selected = Array.isArray(selectedItems)
                ? selectedItems[0]
                : null;
            if (!selected) return;

            const selectedId = Number(selected.id);
            const currentProgramId = Number(this.form.id);

            if (
                Number.isInteger(currentProgramId) &&
                currentProgramId > 0 &&
                selectedId === currentProgramId
            ) {
                this.formErrors =
                    "Le programme source doit etre different du programme en cours.";
                return;
            }

            this.selectedSourceProgram = selected;
            this.formErrors = null;
            this.showModalCopySourceAAT = true;
        },
        handleSelectedSourceAAT(selectedItems) {
            const sourceProgramId = Number(this.selectedSourceProgram?.id);
            const sourceProgramCode = this.selectedSourceProgram?.code || null;

            const existingSourceAatIds = new Set(
                this.pendingProgramAATs
                    .filter((row) => Number(row.source_program_id) === sourceProgramId)
                    .map((row) => Number(row.source_aat_id)),
            );

            for (const item of selectedItems || []) {
                const sourceAatId = Number(item.id);
                if (existingSourceAatIds.has(sourceAatId)) {
                    continue;
                }

                this.pendingProgramAATs.push({
                    temp_id: `copy-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    code: "",
                    name: (item.name || "").trim(),
                    description: (item.description || "").trim(),
                    level_contribution: this.normalizeAATLevel(
                        item.level_contribution,
                    ),
                    origin: "copy",
                    source_program_id: sourceProgramId,
                    source_program_code: sourceProgramCode,
                    source_aat_id: sourceAatId,
                });

                existingSourceAatIds.add(sourceAatId);
            }
        },
        async loadProgramAATs(programId) {
            const id = Number(programId);
            if (!Number.isInteger(id) || id <= 0) {
                this.persistedProgramAATs = [];
                return;
            }

            try {
                const response = await axios.get("/aat/get", {
                    params: { program_id: id },
                });
                this.persistedProgramAATs = Array.isArray(response.data)
                    ? response.data
                    : [];
            } catch (error) {
                this.formErrors = this.extractRequestError(error);
                this.persistedProgramAATs = [];
            }
        },
        async createPendingProgramAATs(programId) {
            const id = Number(programId);
            if (!Number.isInteger(id) || id <= 0 || !this.pendingProgramAATs.length) {
                return { created: 0, failed: [] };
            }

            const failed = [];
            const succeededTempIds = [];

            for (const draft of this.pendingProgramAATs) {
                const payload = {
                    code: (draft.code || "").trim() || null,
                    name: (draft.name || "").trim(),
                    description: (draft.description || "").trim() || null,
                    level_contribution: this.normalizeAATLevel(
                        draft.level_contribution,
                    ),
                    fk_programme: id,
                };

                try {
                    await axios.post("/aat/store", payload);
                    succeededTempIds.push(draft.temp_id);
                } catch (error) {
                    failed.push({
                        label: draft.name || "(sans nom)",
                        message: this.extractRequestError(error),
                    });
                }
            }

            if (succeededTempIds.length) {
                this.pendingProgramAATs = this.pendingProgramAATs.filter(
                    (draft) => !succeededTempIds.includes(draft.temp_id),
                );
            }

            await this.loadProgramAATs(id);

            return { created: succeededTempIds.length, failed };
        },
        extractRequestError(error) {
            const message = error?.response?.data?.message;
            const errors = error?.response?.data?.errors;

            if (errors && typeof errors === "object") {
                const firstField = Object.keys(errors)[0];
                const firstError = Array.isArray(errors[firstField])
                    ? errors[firstField][0]
                    : null;
                if (firstError) {
                    return firstError;
                }
            }

            if (typeof message === "string" && message.trim() !== "") {
                return message;
            }

            return "Une erreur est survenue.";
        },
        async loadPRO() {
            try {
                const response = await axios.get("/pro/get/detailed", {
                    params: { id: this.id },
                });

                const data = response.data;

                // transformer la liste semester[] en objet { 1: ects, 2: ects }
                const semestresCredits = {};
                data.semester.forEach((s) => {
                    semestresCredits[s.semester] = s.ects;
                });

                const prerequis = await axios.get("/pro/pre/get", {
                    params: { id: this.id },
                });

                this.form = {
                    id: data.id,
                    code: data.code,
                    name: data.name,
                    ects: data.ects,
                    semestre: data.semester.length, // nombre de semestres
                    aavprerequis: prerequis.data,
                    semestresCredits,
                };
            } catch (error) {
                this.formErrors = this.extractRequestError(error);
            }
        },
        async saveProgram() {
            this.formErrors = null;
            const url = this.form.id
                ? "/programme/update"
                : "/programme/create";

            try {
                const response = await axios.post(url, this.form);
                const programId = this.form.id
                    ? Number(this.form.id)
                    : Number(response.data?.id);

                if (!this.form.id && Number.isInteger(programId) && programId > 0) {
                    this.form.id = programId;
                }

                const aatSyncResult = await this.createPendingProgramAATs(programId);

                if (aatSyncResult.failed.length) {
                    const failedLabels = aatSyncResult.failed
                        .slice(0, 3)
                        .map((row) => row.label)
                        .join(", ");
                    const failedReasons = aatSyncResult.failed
                        .slice(0, 2)
                        .map((row) => row.message)
                        .filter((msg) => typeof msg === "string" && msg.trim() !== "")
                        .join(" | ");

                    this.formErrors =
                        `Le programme est enregistre mais ${aatSyncResult.failed.length} AAT n'ont pas pu etre crees.` +
                        (failedLabels ? ` Exemples: ${failedLabels}.` : "") +
                        (failedReasons ? ` Détails: ${failedReasons}.` : "");
                    return;
                }

                const createdSuffix =
                    aatSyncResult.created > 0
                        ? ` (${aatSyncResult.created} AAT ajoute(s))`
                        : "";

                this.$router.push({
                    name: "pro-detail",
                    params: { id: programId },
                    query: {
                        message: `${response.data.message}${createdSuffix}.`,
                    },
                });
            } catch (error) {
                this.formErrors = this.extractRequestError(error);
            }
        },
    },
};
</script>



