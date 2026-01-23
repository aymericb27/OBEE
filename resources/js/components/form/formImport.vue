<template>
    <div class="pb-5">
        <div class="m-5 p-5 border bg-white">
            <div>
                <pre>{{ config }}</pre>
            </div>
            <!-- 🌋 Erreurs -->
            <div v-if="errors.length" class="alert alert-danger text-left">
                <ul>
                    <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
                </ul>
            </div>
            <div v-if="successMessage" class="alert alert-success">
                {{ successMessage }}
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
                            placeholder="ex: A, C, D..."
                            v-model="config.columns[field.key]"
                            class="form-control"
                        />
                    </div>
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
                            v-model="config.cells[field.key]"
                            class="form-control"
                        />
                    </div>
                </div>

                <!-- 🟦 UE SECTIONS -->
                <div class="mt-5 row">
                    <div class="col-md-4" v-if="config.type !== 'PRE'">
                        <h5 class="mb-4">Liste des prérequis (AAV)</h5>
                        <div class="row mt-2 mb-2">
                            <div class="col-4"><label>Sigles </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.prerequis.code"
                                    class="form-control"
                                    placeholder="ex: C9"
                                />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><label>Libellés </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.prerequis.libelle"
                                    class="form-control"
                                    placeholder="ex: C11"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" v-if="config.type !== 'AAV'">
                        <h5 class="mb-4">
                            Liste des acquis d'apprentissage visées
                        </h5>
                        <div class="row mb-2">
                            <div class="col-4"><label>Sigles </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.aav.code"
                                    class="form-control"
                                    placeholder="ex: C20"
                                />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><label>Libellés </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.aav.libelle"
                                    class="form-control"
                                    placeholder="ex: C21"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" v-if="config.type !== 'AAT'">
                        <h5 class="mb-4">
                            Liste des acquis d'apprentissages terminaux
                        </h5>
                        <div class="row mb-2">
                            <div class="col-4"><label>Sigles </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.aat.code"
                                    class="form-control"
                                    placeholder="ex: C30"
                                />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><label>Libellés </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.aat.libelle"
                                    class="form-control"
                                    placeholder="ex: C31"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" v-if="config.type !== 'UE'">
                        <h5 class="mb-4">Liste des unités d'enseignements</h5>
                        <div class="row mb-2">
                            <div class="col-4"><label>Sigles </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.ue.code"
                                    class="form-control"
                                    placeholder="ex: C30"
                                />
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><label>Libellés </label></div>
                            <div class="col-4">
                                <input
                                    type="text"
                                    v-model="config.ue.libelle"
                                    class="form-control"
                                    placeholder="ex: C31"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-danger mt-4">
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
const STORAGE_KEY = "import_generic_config_v1";

export default {
    data() {
        return {
            isDragging: false,
            showImportModeHelp: false,

            errors: [],
            successMessage: "",
            isLoading: false,
            excelFile: null,
            fullData: [],
            config: {
                type: "UE",
                importMode: "list",
                columns: {},
                cells: {},

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
                    { key: "code", label: "Sigle" },
                    { key: "name", label: "Libellé", isMandatory: true },
                    { key: "description", label: "Description" },
                    { key: "ects", label: "ECTS" },
                ];
            }
            if (this.config.type === "AAT")
                return [
                    { key: "code", label: "Sigle AAT" },
                    { key: "name", label: "Libellé AAT" },
                ];
            if (this.config.type === "AAV")
                return [
                    { key: "code", label: "Sigle AAV" },
                    { key: "name", label: "Libellé AAV" },
                ];
            return [];
        },
    },
    watch: {
        config: {
            deep: true,
            handler(newVal) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
            },
        },

        "config.type": {
            handler(newType) {
                const defaults = {
                    prerequis: { code: "", libelle: "" },
                    aav: { code: "", libelle: "" },
                    aat: { code: "", libelle: "" },
                    ue: { code: "", libelle: "" },
                };

                const map = {
                    UE: "ue",
                    AAT: "aat",
                    AAV: "aav",
                    PRE: "prerequis",
                };

                const keep = map[newType];

                // reset tous les blocs sauf celui du type
                for (const key of Object.keys(defaults)) {
                    this.config[key] = { ...defaults[key] };
                }

                // reset des mappings qui dépendent du type
                this.config.columns = {};
                this.config.cells = {};
            },
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
            };
        } catch (e) {
            // Si le JSON est corrompu
            localStorage.removeItem(STORAGE_KEY);
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

        async sendImport() {
            this.errors = [];
            this.successMessage = "";
            this.isLoading = true;

            const form = new FormData();
            form.append("file", this.excelFile);
            form.append("config", JSON.stringify(this.config));

            try {
                const res = await axios.post("/import/generic", form);

                // Désactive le bouton
                this.isLoading = false;

                // Message succès
                this.successMessage = "Import réussi !";

                // Scroll tout en haut
                window.scrollTo({ top: 0, behavior: "smooth" });

                console.log("Résultat import :", res.data);
            } catch (err) {
                window.scrollTo({ top: 0, behavior: "smooth" });

                this.isLoading = false;

                const data = err.response?.data;

                // Reset
                this.errors = [];

                // ✅ Cas Laravel ValidationException
                if (data?.errors && typeof data.errors === "object") {
                    // data.errors = { field: [msg1, msg2], other: [msg] }
                    this.errors = Object.values(data.errors).flat();
                    return;
                }

                // ✅ Fallback : message simple
                this.errors.push(data?.message || "Erreur inconnue.");
            }
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
