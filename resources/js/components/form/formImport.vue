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
                <div v-if="canChooseProgrammeSemester" class="mb-3">
                    <button
                        type="button"
                        class="primary_color btn btn-link p-0 text-decoration-underline"
                        @click="openProgramModal"
                    >
                        choisir un semestre parmis les programmes
                    </button>

                    <div v-if="selectedProgrammeLink" class="mt-2">
                        <h5 class="d-inline-block">Programme</h5>
                        : {{ selectedProgrammeLink.code }} -
                        {{ selectedProgrammeLink.name }} | Semestres
                        {{ selectedProgrammeLink.semesters.join(", ") }}
                    </div>
                </div>

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
                        class="col-md-4"
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
                            <div class="col-4">
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
        :singleSelect="true"
        routeGET="/pro/get"
        title="Choisir un programme"
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
                    <h5 class="modal-title">Choisir un semestre</h5>
                </div>

                <div class="modal-body">
                    <div v-if="!availableSemesters.length" class="text-muted">
                        Aucun semestre trouvé pour ce programme.
                    </div>

                    <div
                        v-for="sem in availableSemesters"
                        :key="sem.semester"
                        class="form-check mb-3"
                    >
                        <input
                            :id="`semester-${sem.semester}`"
                            v-model="selectedSemesters"
                            class="form-check-input"
                            type="checkbox"
                            :value="sem.semester"
                        />
                        <label
                            class="form-check-label mt-0"
                            :for="`semester-${sem.semester}`"
                        >
                            Semestre {{ sem.semester }}
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
            selectedProgramDraft: null,
            selectedSemesters: [],
            availableSemesters: [],

            errors: [],
            successMessage: "",
            editTarget: null,
            missingUELinksMessage: "",
            importWarnings: [],
            selectedProgrammeLink: null,
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
            return (
                this.config.importMode === "single" && this.config.type === "UE"
            );
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
        },
        "config.importMode"(newMode) {
            if (newMode !== "single") {
                this.resetProgrammeSemesterSelection();
            }
        },
    },

    mounted() {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;

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
                aav: { ...this.config.aav, ...(saved.aav || {}) },
                aat: { ...this.config.aat, ...(saved.aat || {}) },
                ue: { ...this.config.ue, ...(saved.ue || {}) },
            };
        } catch (e) {
            // Si le JSON est corrompu
            localStorage.removeItem(STORAGE_KEY);
        } finally {
            this.isHydrating = false; // ✅ toujours exécuté
        }
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
            if (!this.canChooseProgrammeSemester) return;
            this.showProgramModal = true;
        },
        async handleProgramSelected(selectedItems) {
            if (!Array.isArray(selectedItems) || selectedItems.length !== 1) {
                this.errors = ["Veuillez sélectionner un seul programme."];
                return;
            }

            const program = selectedItems[0];
            if (!program?.id) return;

            this.showProgramModal = false;
            this.selectedProgramDraft = program;
            this.selectedSemesters = [];
            this.availableSemesters = [];

            try {
                const response = await axios.get("/pro/get/detailed", {
                    params: { id: program.id },
                });
                const semesters = Array.isArray(response.data?.semester)
                    ? response.data.semester
                    : [];

                this.availableSemesters = semesters;
                this.showSemesterModal = true;
            } catch (error) {
                this.errors = [
                    "Impossible de charger les semestres du programme sélectionné.",
                ];
                this.selectedProgramDraft = null;
            }
        },
        confirmSemesterSelection() {
            if (
                !this.selectedProgramDraft ||
                !Array.isArray(this.selectedSemesters) ||
                !this.selectedSemesters.length
            ) {
                return;
            }

            this.selectedProgrammeLink = {
                id: this.selectedProgramDraft.id,
                code: this.selectedProgramDraft.code,
                name: this.selectedProgramDraft.name,
                semesters: this.selectedSemesters
                    .map((s) => Number(s))
                    .filter((s) => Number.isInteger(s) && s > 0)
                    .sort((a, b) => a - b),
            };

            this.showSemesterModal = false;
            this.selectedProgramDraft = null;
            this.selectedSemesters = [];
            this.availableSemesters = [];
        },
        closeSemesterModal() {
            this.showSemesterModal = false;
            this.selectedProgramDraft = null;
            this.selectedSemesters = [];
            this.availableSemesters = [];
        },
        resetProgrammeSemesterSelection() {
            this.showProgramModal = false;
            this.showSemesterModal = false;
            this.selectedProgramDraft = null;
            this.selectedSemesters = [];
            this.availableSemesters = [];
            this.selectedProgrammeLink = null;
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
            if (this.canChooseProgrammeSemester && this.selectedProgrammeLink) {
                payloadConfig.selectedProgrammeLink = {
                    id: this.selectedProgrammeLink.id,
                    code: this.selectedProgrammeLink.code,
                    name: this.selectedProgrammeLink.name,
                    semesters: this.selectedProgrammeLink.semesters,
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
