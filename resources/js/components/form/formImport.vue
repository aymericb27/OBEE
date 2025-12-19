<template>
    <div class="container mt-5 p-5 border bg-white">
        <!-- 🌋 Erreurs -->
        <div v-if="errors.length" class="alert alert-danger text-left">
            <ul>
                <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
            </ul>
        </div>

        <!-- SECTION : Choix du type d'import -->
        <div class="mb-4">
            <label><strong>Type d’import :</strong></label>
            <select v-model="config.type" class="form-control w-50">
                <option disabled value="">-- Choisir --</option>
                <option value="UE">Unité d’enseignement</option>
                <option value="AAT">Acquis d’apprentissage terminal</option>
                <option value="AAV">Acquis d’apprentissage visé</option>
                <option value="PROGRAMME">Programme</option>
            </select>
        </div>

        <!-- SECTION : Mode d'import -->
        <div class="mb-4" v-if="config.type">
            <label><strong>Mode d’import :</strong></label>
            <select v-model="config.importMode" class="form-control w-50">
                <option value="list">
                    Liste de données (plusieurs lignes)
                </option>
                <option value="single">Formulaire vertical (une UE)</option>
            </select>
        </div>

        <!-- SECTION : Upload du fichier -->
        <div class="mb-4">
            <label><strong>Fichier Excel :</strong></label>
            <input type="file" @change="handleFile" class="form-control w-50" />
        </div>

        <!-- SECTION : Preview dynamique -->
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

        <!-- SECTION : Configuration des lignes -->
        <div v-if="fullData.length" class="row mt-4 mb-3">
            <div class="col">
                <label><strong>Ligne de début :</strong></label>
                <input
                    type="number"
                    v-model="config.startRow"
                    class="form-control"
                />
            </div>

            <div class="col">
                <label><strong>Ligne de fin :</strong></label>
                <input
                    type="number"
                    v-model="config.endRow"
                    class="form-control"
                />
            </div>
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
            <h5>Mapping des cellules (ex: C6, D8...)</h5>

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
                        placeholder="ex: C6"
                        v-model="config.cells[field.key]"
                        class="form-control"
                    />
                </div>
            </div>

            <!-- 🟦 SECTIONS UE -->
            <div v-if="config.type === 'UE'" class="mt-5">
                <!-- =====================================================
         PRE-REQUIS
    ====================================================== -->
                <h4>Prérequis (UE)</h4>

                <h5 class="mt-3">Extraction du Code</h5>
                <select
                    v-model="config.prerequis.code.mode"
                    class="form-control w-50 mb-2"
                >
                    <option value="single">Une seule cellule</option>
                    <option value="horizontal">Liste horizontale</option>
                </select>

                <!-- MODE SINGLE -->
                <div
                    v-if="config.prerequis.code.mode === 'single'"
                    class="row mb-4"
                >
                    <div class="col-3">
                        <label>Cellule (ex: C18)</label>
                        <input
                            v-model="config.prerequis.code.cell"
                            class="form-control"
                        />
                    </div>
                    <div class="col-3">
                        <label>Séparateur code/libellé</label>
                        <input
                            v-model="config.prerequis.code.separator"
                            class="form-control"
                            placeholder=":"
                        />
                    </div>
                </div>

                <!-- MODE HORIZONTAL -->
                <div v-else class="row mb-4">
                    <div class="col-2">
                        <label>Ligne</label>
                        <input
                            v-model="config.prerequis.code.row"
                            class="form-control"
                        />
                    </div>
                    <div class="col-2">
                        <label>Début col.</label>
                        <input
                            v-model="config.prerequis.code.startCol"
                            class="form-control"
                        />
                    </div>
                    <div class="col-2">
                        <label>Fin col.</label>
                        <input
                            v-model="config.prerequis.code.endCol"
                            class="form-control"
                        />
                    </div>
                </div>

                <h5>Colonnes supplémentaires</h5>
                <button
                    class="btn btn-outline-primary btn-sm mb-3"
                    @click="addExtra('prerequis')"
                >
                    + Ajouter une colonne
                </button>

                <div
                    v-for="(item, index) in config.prerequis.extra"
                    :key="index"
                    class="border p-3 mb-3 rounded"
                >
                    <!-- TYPE -->
                    <label>Type :</label>
                    <select v-model="item.type" class="form-control w-50 mb-2">
                        <option value="libelle">Libellé</option>
                        <option value="contribution">Contribution</option>
                    </select>

                    <!-- MODE -->
                    <label>Méthode d'extraction :</label>
                    <select v-model="item.mode" class="form-control w-50 mb-2">
                        <option value="single">Une seule cellule</option>
                        <option value="horizontal">Liste horizontale</option>
                    </select>

                    <!-- EXTRA MODE SINGLE -->
                    <div v-if="item.mode === 'single'" class="row mb-4">
                        <div class="col-3">
                            <label>Cellule</label>
                            <input
                                v-model="item.cell"
                                class="form-control"
                                placeholder="C18"
                            />
                        </div>
                        <div class="col-3">
                            <label>Séparateur</label>
                            <input
                                v-model="item.separator"
                                class="form-control"
                                placeholder=":"
                            />
                        </div>
                    </div>

                    <!-- EXTRA MODE HORIZONTAL -->
                    <div v-else class="row mb-3">
                        <div class="col-2">
                            <label>Ligne</label>
                            <input
                                v-model="item.row"
                                class="form-control"
                                placeholder="18"
                            />
                        </div>
                        <div class="col-2">
                            <label>Début col.</label>
                            <input
                                v-model="item.startCol"
                                class="form-control"
                                placeholder="C"
                            />
                        </div>
                        <div class="col-2">
                            <label>Fin col.</label>
                            <input
                                v-model="item.endCol"
                                class="form-control"
                                placeholder="G"
                            />
                        </div>
                    </div>

                    <button
                        class="btn btn-danger btn-sm"
                        @click="removeExtra('prerequis', index)"
                    >
                        Supprimer
                    </button>
                </div>

                <!-- =====================================================
         AAV
    ====================================================== -->
                <h4 class="mt-5">Acquis d’apprentissage visé (AAV)</h4>

                <h5 class="mt-3">Extraction du Code</h5>
                <select
                    v-model="config.aav.code.mode"
                    class="form-control w-50 mb-2"
                >
                    <option value="single">Une seule cellule</option>
                    <option value="horizontal">Liste horizontale</option>
                </select>

                <div v-if="config.aav.code.mode === 'single'" class="row mb-4">
                    <div class="col-3">
                        <label>Cellule</label>
                        <input
                            v-model="config.aav.code.cell"
                            class="form-control"
                        />
                    </div>
                    <div class="col-3">
                        <label>Séparateur</label>
                        <input
                            v-model="config.aav.code.separator"
                            class="form-control"
                        />
                    </div>
                </div>

                <div v-else class="row mb-4">
                    <div class="col-2">
                        <label>Ligne</label>
                        <input
                            v-model="config.aav.code.row"
                            class="form-control"
                        />
                    </div>
                    <div class="col-2">
                        <label>Début col.</label>
                        <input
                            v-model="config.aav.code.startCol"
                            class="form-control"
                        />
                    </div>
                    <div class="col-2">
                        <label>Fin col.</label>
                        <input
                            v-model="config.aav.code.endCol"
                            class="form-control"
                        />
                    </div>
                </div>

                <h5>Colonnes supplémentaires</h5>
                <button
                    class="btn btn-outline-primary btn-sm mb-3"
                    @click="addExtra('aav')"
                >
                    + Ajouter une colonne
                </button>

                <div
                    v-for="(item, index) in config.aav.extra"
                    :key="index"
                    class="border p-3 mb-3 rounded"
                >
                    <label>Type :</label>
                    <select v-model="item.type" class="form-control w-50 mb-2">
                        <option value="libelle">Libellé</option>
                        <option value="contribution">Contribution</option>
                    </select>

                    <label>Méthode d'extraction :</label>
                    <select v-model="item.mode" class="form-control w-50 mb-2">
                        <option value="single">Une seule cellule</option>
                        <option value="horizontal">Liste horizontale</option>
                    </select>

                    <div v-if="item.mode === 'single'" class="row mb-4">
                        <div class="col-3">
                            <label>Cellule</label>
                            <input v-model="item.cell" class="form-control" />
                        </div>
                        <div class="col-3">
                            <label>Séparateur</label>
                            <input
                                v-model="item.separator"
                                class="form-control"
                            />
                        </div>
                    </div>

                    <div v-else class="row mb-3">
                        <div class="col-2">
                            <label>Ligne</label>
                            <input v-model="item.row" class="form-control" />
                        </div>
                        <div class="col-2">
                            <label>Début col.</label>
                            <input
                                v-model="item.startCol"
                                class="form-control"
                            />
                        </div>
                        <div class="col-2">
                            <label>Fin col.</label>
                            <input v-model="item.endCol" class="form-control" />
                        </div>
                    </div>

                    <button
                        class="btn btn-danger btn-sm"
                        @click="removeExtra('aav', index)"
                    >
                        Supprimer
                    </button>
                </div>

                <!-- =====================================================
         AAT
    ====================================================== -->
                <h4 class="mt-5">Acquis d’apprentissage terminaux (AAT)</h4>

                <h5 class="mt-3">Extraction du Code</h5>
                <select
                    v-model="config.aat.code.mode"
                    class="form-control w-50 mb-2"
                >
                    <option value="single">Une seule cellule</option>
                    <option value="horizontal">Liste horizontale</option>
                </select>

                <div v-if="config.aat.code.mode === 'single'" class="row mb-4">
                    <div class="col-3">
                        <label>Cellule</label>
                        <input
                            v-model="config.aat.code.cell"
                            class="form-control"
                        />
                    </div>
                    <div class="col-3">
                        <label>Séparateur</label>
                        <input
                            v-model="config.aat.code.separator"
                            class="form-control"
                        />
                    </div>
                </div>

                <div v-else class="row mb-4">
                    <div class="col-2">
                        <label>Ligne</label>
                        <input
                            v-model="config.aat.code.row"
                            class="form-control"
                        />
                    </div>
                    <div class="col-2">
                        <label>Début col.</label>
                        <input
                            v-model="config.aat.code.startCol"
                            class="form-control"
                        />
                    </div>
                    <div class="col-2">
                        <label>Fin col.</label>
                        <input
                            v-model="config.aat.code.endCol"
                            class="form-control"
                        />
                    </div>
                </div>

                <h5>Colonnes supplémentaires</h5>
                <button
                    class="btn btn-outline-primary btn-sm mb-3"
                    @click="addExtra('aat')"
                >
                    + Ajouter une colonne
                </button>

                <div
                    v-for="(item, index) in config.aat.extra"
                    :key="index"
                    class="border p-3 mb-3 rounded"
                >
                    <label>Type :</label>
                    <select v-model="item.type" class="form-control w-50 mb-2">
                        <option value="libelle">Libellé</option>
                        <option value="contribution">Contribution</option>
                    </select>

                    <label>Méthode d'extraction :</label>
                    <select v-model="item.mode" class="form-control w-50 mb-2">
                        <option value="single">Une seule cellule</option>
                        <option value="horizontal">Liste horizontale</option>
                    </select>

                    <div v-if="item.mode === 'single'" class="row mb-4">
                        <div class="col-3">
                            <label>Cellule</label>
                            <input v-model="item.cell" class="form-control" />
                        </div>
                        <div class="col-3">
                            <label>Séparateur</label>
                            <input
                                v-model="item.separator"
                                class="form-control"
                            />
                        </div>
                    </div>

                    <div v-else class="row mb-3">
                        <div class="col-2">
                            <label>Ligne</label>
                            <input v-model="item.row" class="form-control" />
                        </div>
                        <div class="col-2">
                            <label>Début col.</label>
                            <input
                                v-model="item.startCol"
                                class="form-control"
                            />
                        </div>
                        <div class="col-2">
                            <label>Fin col.</label>
                            <input v-model="item.endCol" class="form-control" />
                        </div>
                    </div>

                    <button
                        class="btn btn-danger btn-sm"
                        @click="removeExtra('aat', index)"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- SECTION : Bouton Import -->
        <div v-if="preview.length" class="mt-4 text-center">
            <button class="btn btn-primary" @click="sendImport">
                Importer
            </button>
        </div>
    </div>
</template>

<script>
import * as XLSX from "xlsx";
import axios from "axios";

export default {
    data() {
        return {
            errors: [],
            excelFile: null,

            fullData: [],
            config: {
                type: "",
                importMode: "list",
                startRow: 1,
                endRow: 10,
                columns: {},
                cells: {},
                prerequis: {
                    code: { mode: "single", cell: "", separator: "" },
                    extra: [],
                },
                aav: {
                    code: { mode: "single", cell: "", separator: "" },
                    extra: [],
                },
                aat: {
                    code: { mode: "single", cell: "", separator: "" },
                    extra: [],
                },
            },
        };
    },

    computed: {
        preview() {
            if (!this.fullData.length) return [];
            const start = Math.max(1, this.config.startRow) - 1;
            const end = Math.max(start, this.config.endRow - 1);
            return this.fullData.slice(start, end + 1);
        },

        availableFields() {
            if (this.config.type === "UE") {
                return [
                    { key: "code", label: "Code UE" },
                    { key: "name", label: "Nom UE" },
                    { key: "description", label: "Description" },
                    { key: "ects", label: "ECTS" },
                ];
            }
            if (this.config.type === "AAT") {
                return [
                    { key: "code", label: "Code AAT" },
                    { key: "name", label: "Libellé AAT" },
                ];
            }
            if (this.config.type === "AAV") {
                return [
                    { key: "code", label: "Code AAV" },
                    { key: "name", label: "Libellé AAV" },
                ];
            }
            return [];
        },
    },

    methods: {
        addExtra(section) {
            this.config[section].extra.push({
                type: "libelle",
                mode: "single",
                cell: "",
                separator: "",
                row: "",
                startCol: "",
                endCol: "",
            });
        },

        removeExtra(section, index) {
            this.config[section].extra.splice(index, 1);
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
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                this.fullData = rows;
            };
            reader.readAsArrayBuffer(this.excelFile);
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
            const form = new FormData();
            form.append("file", this.excelFile);
            form.append("config", JSON.stringify(this.config));
            try {
                const res = await axios.post("/import/generic", form);
                console.log("Résultat import :", res.data);
            } catch (err) {
                this.errors.push(
                    err.response?.data?.message || "Erreur inconnue."
                );
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
</style>
