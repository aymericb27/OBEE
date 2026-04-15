<template>
    <div class="pb-5">
        <div class="m-5 p-5 border bg-white">
            <!-- 🌋 Erreurs -->
            <div v-if="errors.length" class="alert alert-danger text-left">
                <ul>
                    <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
                </ul>
            </div>
            <div v-if="successMessage" class="alert alert-success">
                {{ successMessage }}
                <div v-if="editTarget" class="mt-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success"
                        @click="goToImportedItemEdition"
                    >
                        Éditer l'élément importé
                    </button>
                </div>
            </div>
            <div
                v-if="missingUELinksMessage || importWarnings.length"
                class="alert alert-warning text-left"
            >
                <div v-if="missingUELinksMessage">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    {{ missingUELinksMessage }}
                </div>
                <ul v-if="importWarnings.length" class="mb-0 mt-2">
                    <li v-for="(warn, i) in importWarnings" :key="i">
                        {{ warn }}
                    </li>
                </ul>
            </div>
            <!-- SECTION : Choix du type d'import -->
            <div class="row">
                <div class="mb-4 col-md-6">
                    <label><strong>Type d’import :</strong></label>
                    <select v-model="config.type" class="form-control w-50">
                        <option disabled value="">-- Choisir --</option>
                        <option value="UE">Unité d’enseignement</option>
                        <option value="AAT">
                            Acquis d’apprentissage terminal
                        </option>
                    </select>
                </div>

                <!-- SECTION : Mode d'import -->
                <div class="mb-4 col-md-6">
                    <label class="d-flex align-items-center gap-2">
                        <strong>Mode d’import :</strong>

                        <!-- bouton ? -->
                        <button
                            type="button"
                            class="btn btn-link p-0 text-decoration-none"
                            title="Aide"
                            @click="showImportModeHelp = true"
                            style="line-height: 1"
                        >
                            <i
                                class="fa-solid fa-circle-question primary_color"
                            ></i>
                        </button>
                    </label>

                    <select
                        v-model="config.importMode"
                        class="form-control w-50"
                    >
                        <option value="list">Liste de données</option>
                        <option value="single">Formulaire horizontal</option>
                    </select>
                </div>
            </div>

            <div v-if="canChooseAATProgramme" class="mb-4">
                <div class="mt-2">
                    <button
                        type="button"
                        class="primary_color btn btn-link p-0 text-decoration-underline"
                        @click="openProgramModal"
                    >
                        choisir le programme
                    </button>
                </div>
                <div v-if="selectedAATProgramme" class="mt-2">
                    <h5 class="d-inline-block">Programme sélectionné</h5>
                    : {{ selectedAATProgramme.code || "-" }} -
                    {{ selectedAATProgramme.name || "-" }}
                </div>
            </div>
            <div v-if="canChooseProgrammeSemester" class="listComponent mb-4">
                <div class="mb-2">
                    <h5 class="d-inline-block mb-0 primary_color">
                        Liste des programmes qui comportent cette UE
                        <button
                            type="button"
                            class="btn btn-primary ml-2 mb-2"
                            @click="openProgramModal"
                        >
                            ajouter un programme
                        </button>
                    </h5>
                </div>

                <div class="row border-bottom">
                    <div class="col-md-1 p-2"></div>
                    <div class="col-md-2 p-2">Code</div>
                    <div class="col-md-6 p-2">Libellé</div>
                    <div class="col-md-3 p-2">Semestres</div>
                </div>

                <div
                    v-if="!selectedProgrammeLinks.length"
                    class="p-2 text-center"
                >
                    aucune donnée à afficher
                </div>
                <div
                    v-else
                    v-for="(pro, index) in selectedProgrammeLinks"
                    :key="pro.id"
                    class="row"
                    :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                >
                    <div class="col-md-1 text-right p-2">
                        <i
                            @click="removeSelectedProgramme(pro.id)"
                            class="text-danger fa fa-close pr-0"
                            style="cursor: pointer"
                        ></i>
                    </div>
                    <div class="col-md-2 p-2 PRO">{{ pro.code || "-" }}</div>
                    <div class="col-md-6 p-2">{{ pro.name || "-" }}</div>
                    <div class="col-md-3 p-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary mb-1"
                            @click="openSemesterModalForProgram(pro.id)"
                        >
                            choisir
                        </button>
                        <div v-if="pro.semesters.length" class="small text-muted">
                            S{{ pro.semesters.join(", S") }}
                        </div>
                        <div v-else class="small text-muted">
                            Aucun semestre
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION : Upload du fichier -->
            <!-- SECTION : Upload du fichier (Drag & Drop) -->
            <div class="mb-4 mt-4">
                <div
                    class="dropzone w-100 text-center"
                    :class="{ 'is-dragging': isDragging }"
                    @dragenter.prevent="onDragEnter"
                    @dragover.prevent="onDragOver"
                    @dragleave.prevent="onDragLeave"
                    @drop.prevent="onDrop"
                    @click="openFilePicker"
                >
                    <i
                        class="fa-brands fa-dropbox primary_color"
                        style="font-size: 64px"
                    ></i>
                    <div v-if="!excelFile">
                        <strong>Glissez-déposez</strong> un fichier ici<br />
                        ou
                        <span class="primary_color text-decoration-underline"
                            >cliquez pour choisir</span
                        >
                        <div class="text-muted small mt-1">
                            Formats acceptés : .xlsx, .xls
                        </div>
                    </div>

                    <div v-else>
                        <strong>Fichier sélectionné :</strong>
                        {{ excelFile.name }}
                        <button
                            class="btn btn-sm btn-outline-danger ms-2"
                            type="button"
                            @click.stop="clearFile"
                        >
                            Retirer
                        </button>
                    </div>
                </div>

                <!-- input caché -->
                <input
                    ref="fileInput"
                    type="file"
                    accept=".xlsx,.xls"
                    class="d-none"
                    @change="handleFile"
                />
            </div>

            <!-- SECTION : Preview -->
            <div v-if="preview.length" class="mt-4">
                <h5>Preview ({{ config.startRow }} → {{ config.endRow }})</h5>

                <table class="table table-bordered table-sm bg-white">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th v-for="(col, c) in preview[0]" :key="c">
                                {{ indexToColumnLetter(c) }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, r) in preview" :key="r">
                            <td>
                                <strong>{{ config.startRow + r }}</strong>
                            </td>
                            <td v-for="(cell, c) in row" :key="c">
                                {{ cell }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 🟢 MODE LISTE -->
            <div
                v-if="config.importMode === 'list' && fullData.length"
                class="mt-4"
            >
                <h5>Mapping des colonnes</h5>

                <div
                    class="row mb-2"
                    v-for="field in availableFields"
                    :key="field.key"
                >
                    <div class="col-4">
                        <label>{{ field.label }}</label>
                    </div>
                    <div class="col-4">
                        <input
                            placeholder="ex: A1, C2, D3..."
                            v-model="config.columns[config.type][field.key]"
                            class="form-control w-50"
                        />
                    </div>
                </div>
                <div class="primary_color mt-4">
                    Indiquez la première cellule à laquelle commencent les
                    données.
                </div>
            </div>

            <!-- 🔵 MODE SINGLE -->
            <div
                v-if="config.importMode === 'single' && fullData.length"
                class="mt-4"
            >
                <h5 v-if="config.type == 'UE'">Unité d'enseignement</h5>

                <div
                    class="row mb-2"
                    v-for="field in availableFields"
                    :key="field.key"
                >
                    <div class="col-4">
                        <label
                            >{{ field.label }}
                            <strong v-if="field.isMandatory" class="text-danger"
                                >*</strong
                            ></label
                        >
                    </div>
                    <div class="col-4">
                        <input
                            placeholder="ex: C6"
                            v-model="config.cells[config.type][field.key]"
                            class="form-control w-50"
                        />
                    </div>
                </div>

                <div class="mt-5 row">
                    <div
                        class="col-md-3"
                        v-for="block in relatedBlocks"
                        :key="block.id"
                    >
                        <h5 class="mb-4">{{ block.title }}</h5>

                        <div
                            class="row mb-2"
                            v-for="f in block.fields"
                            :key="f.key"
                        >
                            <div class="col-4">
                                <label>{{ f.label }}</label>
                            </div>
                            <div class="col-6">
                                <input
                                    type="text"
                                    class="form-control"
                                    :placeholder="f.placeholder"
                                    v-model="config[block.modelKey][f.key]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="primary_color mt-4">
                    Pour les listes, indiquez la première cellule à laquelle
                    commencent les données. Si les données sont déjà présentes
                    dans le logiciel, veuillez juste indiquer le sigle.
                </div>
            </div>

            <!-- Bouton -->
            <div v-if="preview.length" class="mt-4 text-center">
                <button
                    class="btn btn-primary"
                    @click="sendImport"
                    :disabled="isLoading"
                >
                    {{ isLoading ? "Import en cours..." : "Importer" }}
                </button>
            </div>
        </div>
    </div>

    <modalList
        type="PRO"
        v-if="showProgramModal"
        :visible="showProgramModal"
        :singleSelect="canChooseAATProgramme"
        :listToExclude="
            canChooseProgrammeSemester ? selectedProgrammeLinks : []
        "
        routeGET="/pro/get"
        :title="
            canChooseAATProgramme
                ? 'Choisir un programme'
                : 'Choisir des programmes'
        "
        @selected="handleProgramSelected"
        @close="showProgramModal = false"
    />

    <div
        v-if="showSemesterModal"
        class="modal fade show"
        tabindex="-1"
        style="display: block"
        role="dialog"
        aria-modal="true"
        @click.self="closeSemesterModal"
    >
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Choisir un semestre
                        <span v-if="activeSemesterProgramName">
                            - {{ activeSemesterProgramName }}
                        </span>
                    </h5>
                </div>

                <div class="modal-body">
                    <div v-if="!availableSemesters.length" class="text-muted">
                        Aucun semestre trouvé pour ce programme.
                    </div>

                    <div
                        v-for="sem in availableSemesters"
                        :key="sem"
                        class="form-check mb-3"
                    >
                        <input
                            :id="`semester-${activeSemesterProgramId}-${sem}`"
                            v-model="selectedSemesters"
                            class="form-check-input"
                            type="checkbox"
                            :value="sem"
                        />
                        <label
                            class="form-check-label mt-0"
                            :for="`semester-${activeSemesterProgramId}-${sem}`"
                        >
                            Semestre {{ sem }}
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-secondary"
                        @click="closeSemesterModal"
                    >
                        Annuler
                    </button>
                    <button
                        class="btn btn-primary"
                        :disabled="!selectedSemesters.length"
                        @click="confirmSemesterSelection"
                    >
                        Valider
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showSemesterModal" class="modal-backdrop fade show"></div>

    <!-- Modal aide importMode -->
    <div
        v-if="showImportModeHelp"
        class="modal fade show"
        tabindex="-1"
        style="display: block"
        role="dialog"
        aria-modal="true"
        @click.self="showImportModeHelp = false"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aide</h5>
                </div>

                <div class="modal-body helper">
                    <ul>
                        <li>
                            <strong style="color: rgb(33, 88, 114)"
                                >Liste de données</strong
                            >
                            : l’Excel ressemble à une table — chaque ligne = 1
                            enregistrement.
                            <br />
                            Les colonnes sont fixes : A="code", B="libellé",
                            C="ects", …
                        </li>

                        <li class="mt-3">
                            <strong style="color: rgb(33, 88, 114)"
                                >Formulaire horizontal</strong
                            >
                            : on a un seul élément décrit verticalement dans
                            l’Excel.
                            <br />
                            Exemple : A1="Sigle", C1="LEPL1102", A2="Intitulé",
                            C2="Analyse I", …
                            <br />
                            Particularité : on peut lire horizontalement des
                            données (ex : A1 → J1 = acquis d’apprentissage visés
                            liés à l’élément).
                        </li>
                    </ul>
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-primary"
                        @click="showImportModeHelp = false"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop -->
    <div v-if="showImportModeHelp" class="modal-backdrop fade show"></div>
</template>

<script>
import * as XLSX from "xlsx";
import axios from "axios";
import modalList from "../modalList.vue";
import { currentProgramState } from "../../stores/currentProgram";
const STORAGE_KEY = "import_generic_config_v1";

export default {
    components: { modalList },
    data() {
        return {
            isDragging: false,
            showImportModeHelp: false,
            isHydrating: true,
            showProgramModal: false,
            showSemesterModal: false,
            activeSemesterProgramId: null,
            activeSemesterProgramName: "",
            selectedSemesters: [],
            availableSemesters: [],

            errors: [],
            successMessage: "",
            editTarget: null,
            missingUELinksMessage: "",
            importWarnings: [],
            selectedProgrammeLinks: [],
            selectedAATProgramme: null,
            isLoading: false,
            excelFile: null,
            fullData: [],
            config: {
                type: "UE",
                importMode: "list",
                columns: {
                    UE: {
                        code: "",
                        name: "",
                        description: "",
                        ects: "",
                    },
                    AAT: {
                        code: "",
                        name: "",
                    },
                },

                cells: {
                    UE: {
                        code: "",
                        name: "",
                        description: "",
                        ects: "",
                    },
                    AAT: {
                        code: "",
                        name: "",
                    },
                },
                prerequis: {
                    code: "",
                    libelle: "",
                },
                prerequis_ue: {
                    code: "",
                    libelle: "",
                },
                aav: {
                    code: "",
                    libelle: "",
                },
                aat: {
                    code: "",
                    libelle: "",
                },
                ue: {
                    code: "",
                    libelle: "",
                },
            },
        };
    },

    computed: {
        currentProgram() {
            return currentProgramState;
        },
        relatedBlocks() {
            return [
                {
                    id: "prerequis",
                    title: "Liste des prérequis (AAV)",
                    hideWhenType: ["PRE", "AAT"], // équivaut à v-if="config.type !== 'PRE'"
                    modelKey: "prerequis", // pointe vers config.prerequis
                    fields: [
                        { key: "code", label: "Sigles", placeholder: "ex: C9" },
                        {
                            key: "libelle",
                            label: "Libellés",
                            placeholder: "ex: C11",
                        },
                    ],
                },
                {
                    id: "prerequis_ue",
                    title: "Liste des prérequis (UE)",
                    hideWhenType: ["AAT"],
                    modelKey: "prerequis_ue",
                    fields: [
                        { key: "code", label: "Sigles", placeholder: "ex: C13" },
                        {
                            key: "libelle",
                            label: "Libellés",
                            placeholder: "ex: C14",
                        },
                    ],
                },
                {
                    id: "aav",
                    title: "Liste des acquis d'apprentissage visées",
                    hideWhenType: ["AAV"],
                    modelKey: "aav",
                    fields: [
                        {
                            key: "code",
                            label: "Sigles",
                            placeholder: "ex: C20",
                        },
                        {
                            key: "libelle",
                            label: "Libellés",
                            placeholder: "ex: C21",
                        },
                    ],
                },
                {
                    id: "aat",
                    title: "Liste des acquis d'apprentissage terminaux",
                    hideWhenType: ["AAT"],
                    modelKey: "aat",
                    fields: [
                        {
                            key: "code",
                            label: "Sigles",
                            placeholder: "ex: C30",
                        },
                        {
                            key: "libelle",
                            label: "Libellés",
                            placeholder: "ex: C31",
                        },
                    ],
                },
                {
                    id: "ue",
                    title: "Liste des unités d'enseignement",
                    hideWhenType: ["UE"],
                    modelKey: "ue",
                    fields: [
                        {
                            key: "code",
                            label: "Sigles",
                            placeholder: "ex: C30",
                        },
                        {
                            key: "libelle",
                            label: "Libellés",
                            placeholder: "ex: C31",
                        },
                    ],
                },
            ].filter((b) => {
                if (!b.hideWhenType) return true; // sécurité
                return !b.hideWhenType.includes(this.config.type);
            });
        },

        preview() {
            if (!this.fullData.length) return [];

            const start = Math.max(1, this.config.startRow) - 1;
            const end = Math.max(start, this.config.endRow - 1);
            const rows = this.fullData.slice(start, end + 1);

            // ✅ calcule la dernière colonne réellement non vide dans la zone preview
            const lastCol = this.getLastNonEmptyCol(rows);
            const colCount = lastCol >= 0 ? lastCol + 1 : 0;

            // ✅ tronque à droite, mais garde les vides au milieu
            return rows.map((r) => {
                const row = r || [];
                return colCount ? row.slice(0, colCount) : [];
            });
        },

        availableFields() {
            if (this.config.type === "UE") {
                return [
                    { key: "code", label: "Sigle", isMandatory: true },
                    { key: "name", label: "Libellé", isMandatory: true },
                    { key: "description", label: "Description" },
                    { key: "ects", label: "ECTS", isMandatory: true },
                ];
            }
            if (this.config.type === "AAT")
                return [
                    { key: "code", label: "Sigle AAT", isMandatory: true },
                    {
                        key: "name",
                        label: "Libellé AAT",
                        isMandatory: true,
                    },
                ];
            if (this.config.type === "AAV")
                return [
                    { key: "code", label: "Sigle AAV", isMandatory: true },
                    { key: "name", label: "Libellé AAV" },
                ];
            return [];
        },
        canChooseProgrammeSemester() {
            return this.config.type === "UE";
        },
        canChooseAATProgramme() {
            return this.config.type === "AAT";
        },
    },
    watch: {
        config: {
            deep: true,
            handler(newVal) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
            },
        },
        "config.type"(newType) {
            if (newType !== "UE") {
                this.resetProgrammeSemesterSelection();
            }
            if (newType === "UE") {
                this.ensureDefaultUEProgrammeFromCurrent();
            }
            if (newType === "AAT") {
                this.ensureDefaultAATProgrammeFromCurrent();
            }
        },
        "config.importMode"(newMode) {
            if (newMode === "single" && this.config.type === "UE") {
                this.ensureDefaultUEProgrammeFromCurrent();
            }
        },
        "currentProgram.id"(newId, oldId) {
            if (newId === oldId) return;
            if (this.canChooseProgrammeSemester) {
                this.ensureDefaultUEProgrammeFromCurrent(true);
            }
            if (this.canChooseAATProgramme) {
                this.ensureDefaultAATProgrammeFromCurrent(true);
            }
        },
    },

    mounted() {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            try {
                const saved = JSON.parse(raw);

                // ⚠️ merge pour garder les nouvelles clés si tu ajoutes des champs plus tard
                this.config = {
                    ...this.config,
                    ...saved,
                    columns: { ...this.config.columns, ...(saved.columns || {}) },
                    cells: { ...this.config.cells, ...(saved.cells || {}) },
                    prerequis: {
                        ...this.config.prerequis,
                        ...(saved.prerequis || {}),
                    },
                    prerequis_ue: {
                        ...this.config.prerequis_ue,
                        ...(saved.prerequis_ue || {}),
                    },
                    aav: { ...this.config.aav, ...(saved.aav || {}) },
                    aat: { ...this.config.aat, ...(saved.aat || {}) },
                    ue: { ...this.config.ue, ...(saved.ue || {}) },
                };
            } catch (e) {
                // Si le JSON est corrompu
                localStorage.removeItem(STORAGE_KEY);
            }
        }

        this.isHydrating = false;
        this.ensureDefaultUEProgrammeFromCurrent();
        this.ensureDefaultAATProgrammeFromCurrent();
    },

    methods: {
        openFilePicker() {
            this.$refs.fileInput?.click();
        },

        clearFile() {
            this.excelFile = null;
            this.fullData = [];
        },

        onDragEnter() {
            this.isDragging = true;
        },

        onDragOver() {
            this.isDragging = true;
        },

        onDragLeave(e) {
            // évite que ça s’éteigne quand on passe sur un enfant
            if (e.currentTarget.contains(e.relatedTarget)) return;
            this.isDragging = false;
        },

        onDrop(e) {
            this.isDragging = false;

            const file = e.dataTransfer?.files?.[0];
            if (!file) return;

            // Optionnel : validation extension
            const ok = /\.(xlsx|xls)$/i.test(file.name);
            if (!ok) {
                this.errors = [
                    "Veuillez déposer un fichier Excel (.xlsx ou .xls).",
                ];
                return;
            }

            this.errors = [];
            this.excelFile = file;
            this.readExcel();
        },

        getLastNonEmptyCol(rows) {
            let last = -1;
            for (const row of rows) {
                if (!row) continue;
                for (let c = row.length - 1; c >= 0; c--) {
                    const v = row[c];
                    if (
                        v !== null &&
                        v !== undefined &&
                        String(v).trim() !== ""
                    ) {
                        last = Math.max(last, c);
                        break;
                    }
                }
            }
            return last; // -1 si tout est vide
        },

        handleFile(e) {
            this.excelFile = e.target.files[0];
            this.readExcel();
        },
        readExcel() {
            if (!this.excelFile) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: "array" });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];

                // 1) Lire brut
                const rows = XLSX.utils.sheet_to_json(sheet, {
                    header: 1,
                    defval: "",
                    blankrows: true,
                });

                // 2) Calculer la vraie zone "non vide" en parcourant les cellules réelles
                const { maxRow, maxCol } = this.getRealUsedRange(sheet);

                // Si feuille vide
                if (maxRow === 0 || maxCol === 0) {
                    this.fullData = [];
                    this.config.startRow = 1;
                    this.config.endRow = 1;
                    return;
                }

                // 3) Tronquer rows à la vraie zone
                const trimmed = rows
                    .slice(0, maxRow) // lignes 1..maxRow
                    .map((r) => (r || []).slice(0, maxCol)); // colonnes 1..maxCol

                this.fullData = trimmed;

                // ✅ auto start/end (sur vrai contenu)
                this.config.startRow = 1;
                this.config.endRow = maxRow;
            };

            reader.readAsArrayBuffer(this.excelFile);
        },

        // ✅ Détecte la vraie dernière cellule non vide (ignore styles / !ref)
        getRealUsedRange(sheet) {
            let maxRow = 0;
            let maxCol = 0;

            for (const addr of Object.keys(sheet)) {
                // ignore meta keys
                if (addr[0] === "!") continue;

                const cell = sheet[addr];
                if (!cell) continue;

                // ignore cellules réellement vides
                const v = cell.v;
                if (v === undefined || v === null || String(v).trim() === "")
                    continue;

                const decoded = XLSX.utils.decode_cell(addr);
                maxRow = Math.max(maxRow, decoded.r + 1); // r est 0-based
                maxCol = Math.max(maxCol, decoded.c + 1); // c est 0-based
            }

            return { maxRow, maxCol };
        },

        indexToColumnLetter(index) {
            let temp,
                letter = "";
            let num = index + 1;
            while (num > 0) {
                temp = (num - 1) % 26;
                letter = String.fromCharCode(temp + 65) + letter;
                num = Math.floor((num - temp - 1) / 26);
            }
            return letter;
        },
        formatImportErrors(payload) {
            const out = [];

            // errors: [ {rowIndex, row, type, errors|message }, ... ]
            const list = payload?.errors;
            if (!Array.isArray(list)) return out;

            for (const e of list) {
                const line =
                    e.excelRow ?? e.row?.__row ?? (e.rowIndex ?? 0) + 1;

                // ValidationException => e.errors = { "main.name": ["..."], ... }
                if (
                    e.type === "validation" &&
                    e.errors &&
                    typeof e.errors === "object"
                ) {
                    const msgs = Object.values(e.errors).flat();
                    for (const msg of msgs) {
                        out.push(`Ligne ${line} : ${msg}`);
                    }
                    continue;
                }

                // DB / unknown
                if (e.message) {
                    out.push(`Ligne ${line} : ${e.message}`);
                    continue;
                }

                out.push(`Ligne ${line} : erreur inconnue`);
            }

            return out;
        },
        formatImportWarnings(payload) {
            const out = [];
            const list = payload?.warnings;
            if (!Array.isArray(list)) return out;

            for (const w of list) {
                if (typeof w === "string") {
                    out.push(w);
                    continue;
                }

                const message = w?.message || "Avertissement";
                const line = w?.excelRow ?? w?.row?.__row ?? null;

                if (line) {
                    out.push(`Ligne ${line} : ${message}`);
                } else {
                    out.push(message);
                }
            }

            return out;
        },
        openProgramModal() {
            if (!this.canChooseProgrammeSemester && !this.canChooseAATProgramme)
                return;
            this.showProgramModal = true;
        },
        normalizeSemesterValues(rawValues) {
            const source = Array.isArray(rawValues) ? rawValues : [];
            const normalized = [];

            for (const value of source) {
                const raw =
                    typeof value === "object" && value !== null
                        ? value.semester
                        : value;
                const semester = Number(raw);
                if (
                    Number.isInteger(semester) &&
                    semester > 0 &&
                    !normalized.includes(semester)
                ) {
                    normalized.push(semester);
                }
            }

            return normalized.sort((a, b) => a - b);
        },
        async getProgrammeSemesterNumbers(programId) {
            try {
                const response = await axios.get("/pro/get/detailed", {
                    params: { id: programId },
                });
                return this.normalizeSemesterValues(response.data?.semester);
            } catch (error) {
                return [];
            }
        },
        async ensureDefaultUEProgrammeFromCurrent(force = false) {
            if (!this.canChooseProgrammeSemester) return;

            const currentId = Number(this.currentProgram?.id);
            if (!Number.isInteger(currentId) || currentId <= 0) return;

            const existingIndex = this.selectedProgrammeLinks.findIndex(
                (item) => Number(item?.id) === currentId,
            );

            if (!force && existingIndex >= 0) return;

            const fallbackProgramme = {
                id: currentId,
                code: String(this.currentProgram?.code || "").trim(),
                name: String(this.currentProgram?.name || "").trim(),
            };

            if (!fallbackProgramme.code && !fallbackProgramme.name) {
                try {
                    const response = await axios.get("/pro/get/detailed", {
                        params: { id: currentId },
                    });
                    fallbackProgramme.code = String(
                        response.data?.code || "",
                    ).trim();
                    fallbackProgramme.name = String(
                        response.data?.name || "",
                    ).trim();
                } catch (error) {}
            }

            const availableSemesters =
                await this.getProgrammeSemesterNumbers(currentId);
            const existingProgramme =
                existingIndex >= 0 ? this.selectedProgrammeLinks[existingIndex] : null;

            const nextProgramme = {
                id: currentId,
                code:
                    fallbackProgramme.code ||
                    String(existingProgramme?.code || "").trim(),
                name:
                    fallbackProgramme.name ||
                    String(existingProgramme?.name || "").trim(),
                semesters: this.normalizeSemesterValues(
                    existingProgramme?.semesters,
                ).filter((semester) => availableSemesters.includes(semester)),
                availableSemesters,
            };

            const others = this.selectedProgrammeLinks.filter(
                (item) => Number(item?.id) !== currentId,
            );
            this.selectedProgrammeLinks = [nextProgramme, ...others];
        },
        async ensureProgrammeSemesterOptions(programId) {
            const targetIndex = this.selectedProgrammeLinks.findIndex(
                (item) => Number(item?.id) === Number(programId),
            );
            if (targetIndex < 0) return [];

            const current = this.selectedProgrammeLinks[targetIndex];
            const semesters =
                current?.availableSemesters?.length > 0
                    ? this.normalizeSemesterValues(current.availableSemesters)
                    : await this.getProgrammeSemesterNumbers(programId);

            this.selectedProgrammeLinks.splice(targetIndex, 1, {
                ...current,
                availableSemesters: semesters,
                semesters: this.normalizeSemesterValues(current?.semesters).filter(
                    (semester) => semesters.includes(semester),
                ),
            });

            return semesters;
        },
        removeSelectedProgramme(programId) {
            const id = Number(programId);
            this.selectedProgrammeLinks = this.selectedProgrammeLinks.filter(
                (item) => Number(item?.id) !== id,
            );
            if (this.activeSemesterProgramId === id) {
                this.closeSemesterModal();
            }
        },
        async openSemesterModalForProgram(programId) {
            const id = Number(programId);
            if (!Number.isInteger(id) || id <= 0) return;

            const semesters = await this.ensureProgrammeSemesterOptions(id);
            const programme = this.selectedProgrammeLinks.find(
                (item) => Number(item?.id) === id,
            );

            if (!programme) return;

            this.activeSemesterProgramId = id;
            this.activeSemesterProgramName = [programme.code, programme.name]
                .filter(Boolean)
                .join(" - ");
            this.availableSemesters = semesters;
            this.selectedSemesters = this.normalizeSemesterValues(
                programme.semesters,
            ).filter((semester) => semesters.includes(semester));
            this.showSemesterModal = true;
        },
        async ensureDefaultAATProgrammeFromCurrent(force = false) {
            if (!this.canChooseAATProgramme) return;

            const currentId = Number(currentProgramState.id);
            if (!Number.isInteger(currentId) || currentId <= 0) return;

            if (!force && this.selectedAATProgramme?.id) return;

            const fallbackProgramme = {
                id: currentId,
                code: String(currentProgramState.code || "").trim(),
                name: String(currentProgramState.name || "").trim(),
            };

            if (fallbackProgramme.code || fallbackProgramme.name) {
                this.selectedAATProgramme = fallbackProgramme;
                return;
            }

            try {
                const response = await axios.get("/pro/get/detailed", {
                    params: { id: currentId },
                });
                this.selectedAATProgramme = {
                    id: currentId,
                    code: String(response.data?.code || "").trim(),
                    name: String(response.data?.name || "").trim(),
                };
            } catch (error) {
                this.selectedAATProgramme = fallbackProgramme;
            }
        },
        async handleProgramSelected(selectedItems) {
            if (this.canChooseAATProgramme) {
                if (
                    !Array.isArray(selectedItems) ||
                    selectedItems.length !== 1
                ) {
                    this.errors = ["Veuillez sélectionner un seul programme."];
                    return;
                }

                const program = selectedItems[0];
                if (!program?.id) return;

                this.selectedAATProgramme = {
                    id: Number(program.id),
                    code: String(program.code || "").trim(),
                    name: String(program.name || "").trim(),
                };
                this.showProgramModal = false;
                return;
            }

            if (!this.canChooseProgrammeSemester) {
                this.showProgramModal = false;
                return;
            }

            this.showProgramModal = false;
            if (!Array.isArray(selectedItems) || !selectedItems.length) {
                return;
            }

            this.errors = [];

            const existingById = new Map(
                this.selectedProgrammeLinks.map((item) => [
                    Number(item?.id),
                    item,
                ]),
            );

            const programs = await Promise.all(
                selectedItems.map(async (program) => {
                    const programId = Number(program?.id);
                    if (!Number.isInteger(programId) || programId <= 0) {
                        return null;
                    }

                    const semesterOptions =
                        await this.getProgrammeSemesterNumbers(programId);
                    const existing = existingById.get(programId);

                    return {
                        id: programId,
                        code: String(
                            program?.code || existing?.code || "",
                        ).trim(),
                        name: String(
                            program?.name || existing?.name || "",
                        ).trim(),
                        semesters: this.normalizeSemesterValues(
                            existing?.semesters,
                        ).filter((semester) =>
                            semesterOptions.includes(semester),
                        ),
                        availableSemesters: semesterOptions,
                    };
                }),
            );

            const selectedPrograms = programs.filter(Boolean);
            if (!selectedPrograms.length) {
                return;
            }

            const existing = [...this.selectedProgrammeLinks];
            for (const selectedProgramme of selectedPrograms) {
                const index = existing.findIndex(
                    (item) => Number(item?.id) === Number(selectedProgramme.id),
                );
                if (index >= 0) {
                    existing.splice(index, 1, {
                        ...existing[index],
                        ...selectedProgramme,
                    });
                } else {
                    existing.push(selectedProgramme);
                }
            }

            this.selectedProgrammeLinks = existing;

            if (selectedPrograms.length === 1) {
                await this.openSemesterModalForProgram(selectedPrograms[0].id);
            }
        },
        confirmSemesterSelection() {
            if (!this.activeSemesterProgramId) {
                return;
            }

            const selected = this.normalizeSemesterValues(
                this.selectedSemesters,
            ).filter((semester) => this.availableSemesters.includes(semester));

            this.selectedProgrammeLinks = this.selectedProgrammeLinks.map(
                (programme) => {
                    if (
                        Number(programme?.id) !==
                        Number(this.activeSemesterProgramId)
                    ) {
                        return programme;
                    }

                    return {
                        ...programme,
                        semesters: selected,
                    };
                },
            );

            this.closeSemesterModal();
        },
        closeSemesterModal() {
            this.showSemesterModal = false;
            this.activeSemesterProgramId = null;
            this.activeSemesterProgramName = "";
            this.selectedSemesters = [];
            this.availableSemesters = [];
        },
        resetProgrammeSemesterSelection() {
            this.showProgramModal = false;
            this.closeSemesterModal();
            this.selectedProgrammeLinks = [];
        },

        async sendImport() {
            this.errors = [];
            this.successMessage = "";
            this.editTarget = null;
            this.missingUELinksMessage = "";
            this.importWarnings = [];
            this.isLoading = true;

            if (!this.validateRequiredImportFields()) {
                this.isLoading = false;
                window.scrollTo({ top: 0, behavior: "smooth" });
                return;
            }

            const form = new FormData();
            form.append("file", this.excelFile);

            const payloadConfig = JSON.parse(JSON.stringify(this.config));
            if (
                this.canChooseProgrammeSemester &&
                this.selectedProgrammeLinks.length
            ) {
                payloadConfig.selectedProgrammeLinks = this.selectedProgrammeLinks
                    .map((programme) => ({
                        id: Number(programme.id),
                        code: String(programme.code || "").trim(),
                        name: String(programme.name || "").trim(),
                        semesters: this.normalizeSemesterValues(
                            programme.semesters,
                        ),
                    }))
                    .filter((programme) => programme.semesters.length > 0);
            }
            if (this.canChooseAATProgramme && this.selectedAATProgramme) {
                payloadConfig.selectedAATProgramme = {
                    id: this.selectedAATProgramme.id,
                    code: this.selectedAATProgramme.code,
                    name: this.selectedAATProgramme.name,
                };
            }

            form.append("config", JSON.stringify(payloadConfig));
            try {
                const res = await axios.post("/import/generic", form);

                // si le backend renvoie errors même en 200/207
                const apiErrors = this.formatImportErrors(res.data);
                const apiWarnings = this.formatImportWarnings(res.data);
                this.importWarnings = apiWarnings;

                if (apiErrors.length) {
                    this.errors = apiErrors;
                    this.successMessage = "Import terminé avec des erreurs.";
                } else {
                    this.errors = [];
                    this.successMessage = "Import réussi !";
                    this.setEditTargetFromImportResponse(res.data);
                    this.setUEWithoutLinksWarning(res.data);
                }

                window.scrollTo({ top: 0, behavior: "smooth" });
                console.log("Résultat import :", res.data);
            } catch (err) {
                window.scrollTo({ top: 0, behavior: "smooth" });

                const data = err.response?.data;
                this.errors = [];

                const apiErrors = this.formatImportErrors(data);
                if (apiErrors.length) {
                    this.errors = apiErrors;
                    this.editTarget = null;
                    this.missingUELinksMessage = "";
                    this.importWarnings = [];
                    return;
                }

                if (data?.errors && typeof data.errors === "object") {
                    this.errors = Object.values(data.errors).flat();
                    this.editTarget = null;
                    this.missingUELinksMessage = "";
                    this.importWarnings = [];
                    return;
                }

                this.editTarget = null;
                this.missingUELinksMessage = "";
                this.importWarnings = [];
                this.errors.push(data?.message || "Erreur inconnue.");
            } finally {
                this.isLoading = false;
            }
        },

        setEditTargetFromImportResponse(payload) {
            const mode = payload?.mode || this.config.importMode;
            const type = payload?.type || this.config.type;
            const id = payload?.stored?.id;

            if (mode !== "single" || !id) {
                this.editTarget = null;
                return;
            }

            const routeByType = {
                UE: "modifyUE",
                AAT: "modifyAAT",
            };

            const routeName = routeByType[type];
            if (!routeName) {
                this.editTarget = null;
                return;
            }

            this.editTarget = { id, routeName };
        },

        goToImportedItemEdition() {
            if (!this.editTarget) return;
            this.$router.push({
                name: this.editTarget.routeName,
                params: { id: this.editTarget.id },
            });
        },

        setUEWithoutLinksWarning(payload) {
            const mode = payload?.mode || this.config.importMode;
            const type = payload?.type || this.config.type;

            if (type !== "UE") {
                this.missingUELinksMessage = "";
                return;
            }

            // En mode liste, seules les données UE principales sont importées.
            if (mode === "list") {
                this.missingUELinksMessage =
                    "Attention : vous avez importé une UE sans acquis d'apprentissage visé et sans acquis d'apprentissage terminal.";
                return;
            }

            const links = payload?.data?.links || {};
            const hasAAT =
                Array.isArray(links.aats) &&
                links.aats.some(
                    (row) =>
                        String(row?.code || "").trim() ||
                        String(row?.libelle || "").trim(),
                );
            const hasAAV =
                Array.isArray(links.aavs) &&
                links.aavs.some(
                    (row) =>
                        String(row?.code || "").trim() ||
                        String(row?.libelle || "").trim(),
                );

            if (!hasAAV && !hasAAT) {
                this.missingUELinksMessage =
                    "Attention : vous avez importé une UE sans acquis d'apprentissage visé et sans acquis d'apprentissage terminal.";
                return;
            }

            if (!hasAAV) {
                this.missingUELinksMessage =
                    "Attention : vous avez importé une UE sans acquis d'apprentissage visé.";
                return;
            }

            if (!hasAAT) {
                this.missingUELinksMessage =
                    "Attention : vous avez importé une UE sans acquis d'apprentissage terminal.";
                return;
            }

            this.missingUELinksMessage = "";
        },

        validateRequiredImportFields() {
            const type = this.config.type;
            const mode = this.config.importMode;
            const selectedAATProgrammeId = Number(this.selectedAATProgramme?.id);
            const selectedProgrammeCount = this.selectedProgrammeLinks.length;
            const selectedSemesterCount = this.selectedProgrammeLinks.reduce(
                (count, programme) =>
                    count +
                    this.normalizeSemesterValues(programme?.semesters).length,
                0,
            );

            if (type === "UE") {
                if (selectedProgrammeCount < 1) {
                    this.errors = [
                        "Au moins un programme doit être sélectionné pour l'import d'une UE.",
                    ];
                    return false;
                }

                if (selectedSemesterCount < 1) {
                    this.errors = [
                        "Au moins un semestre doit être sélectionné pour l'import d'une UE.",
                    ];
                    return false;
                }
            }

            if (
                type === "AAT" &&
                (!Number.isInteger(selectedAATProgrammeId) ||
                    selectedAATProgrammeId <= 0)
            ) {
                this.errors = [
                    "Le programme est obligatoire pour l'import d'un AAT.",
                ];
                return false;
            }

            if (mode === "list") {
                const codeCol = this.config?.columns?.[type]?.code;
                if (!String(codeCol || "").trim()) {
                    this.errors = ["Le champ Sigle est obligatoire."];
                    return false;
                }
            }

            if (mode === "single") {
                const codeCell = this.config?.cells?.[type]?.code;
                if (!String(codeCell || "").trim()) {
                    this.errors = ["Le champ Sigle est obligatoire."];
                    return false;
                }
            }

            return true;
        },
    },
};
</script>

<style scoped>
table td,
table th {
    padding: 4px !important;
    font-size: 13px;
}
.dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 60px;
    background: #fafafa;
    cursor: pointer;
    transition: 0.15s ease-in-out;
}

.dropzone.is-dragging {
    border-color: #3b82f6;
    background: #eff6ff;
}
label {
    display: inline-block;
    /* margin-bottom: .5rem; */
    margin-top: 0.5rem;
}
</style>
