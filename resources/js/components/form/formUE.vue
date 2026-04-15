<template>
    <div>
        <div class="back_btn">
            <a href="#" @click="$router.back()" class="primary_color">
                <i class="fa-solid fa-circle-arrow-left"></i> Retour
            </a>
        </div>
        <div class="w-75 m-auto pb-3">
            <div v-if="formErrors" class="alert alert-danger mt-3">
                <i
                    class="fa-solid fa-triangle-exclamation mr-2"
                    style="color: crimson; font-size: 24px"
                ></i>
                <span> {{ formErrors }} </span>
            </div>
            <form @submit.prevent="submitFormUE">
                <div class="p-4 border rounded bg-white mt-3">
                    <h3 v-if="!ueParent.name" class="mb-4 primary_color">
                        Introduire une unité d'enseignement
                    </h3>
                    <h3 v-if="ueParent.name" class="mb-4 primary_color">
                        Introduire un élément constitutif
                    </h3>

                    <div class="mb-3" v-if="$route.query.UEParentId">
                        Cette élément sera l'élément constitutif de
                        <span class="UE">{{ ueParent.code }}</span>
                        <span class="ml-1 font-weight-bold secondary_color">{{
                            ueParent.name
                        }}</span>
                        <div class="mt-2 mb-3">
                            <span>Contribution : </span>
                            <select
                                class="form form-control w-25 d-inline-block ml-2"
                                v-model="ueParent.contribution"
                            >
                                <option value="1" selected>faible</option>
                                <option value="2">modéré</option>
                                <option value="3">forte</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center">
                        <span>
                            <input
                                type="text"
                                class="form form-control"
                                v-model="ue.code"
                                placeholder="Sigle"
                            />
                        </span>
                        <span class="pr-2 ml-2 mb-0 w-75 flex-grow-1">
                            <input
                                type="text"
                                class="form form-control"
                                placeholder="intitulé (obligatoire)"
                                v-model="ue.name"
                                required
                            />
                        </span>
                        <span class="ml-2">
                            <input
                                v-if="!ueParent.name"
                                type="number"
                                class="form form-control"
                                v-model="ue.ects"
                                placeholder="Nombre d'ects"
                            />
                        </span>
                    </div>
                    <p class="mb-4">
                        <quill-editor
                            v-model:content="ue.description"
                            placeholder="description"
                            content-type="html"
                            theme="snow"
                            style="height: 175px"
                            required
                        ></quill-editor>
                    </p>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h5 class="d-inline-block primary_color">
                                Liste des acquis d'apprentissage terminaux
                                auxquels cette UE contribue
                                <button
                                    type="button"
                                    class="btn btn-primary ml-2 mb-2"
                                    @click="openModalTerminal()"
                                >
                                    ajouter un acquis d'apprentissage terminal
                                </button>
                            </h5>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-8 p-2">Nom</div>
                            <div class="col-md-2 p-2">Contribution</div>
                        </div>
                        <div
                            v-if="isLoadingAATList"
                            class="p-3 text-center text-muted"
                        >
                            <div
                                class="spinner-border spinner-border-sm mr-2"
                                role="status"
                                aria-hidden="true"
                            ></div>
                            chargement des AAT...
                        </div>
                        <div
                            v-else-if="!ue.aat || !ue.aat.length"
                            class="p-2 text-center"
                        >
                            aucune donnée à afficher
                        </div>

                        <div
                            v-else
                            v-for="(aat, index) in ue.aat"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeElement('aat', aat.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAT">{{ aat.code }}</div>
                            <div class="col-md-8 p-2">{{ aat.name }}</div>
                            <div class="col-md-2 p-2">
                                <select
                                    class="form form-control"
                                    v-model="aat.contribution"
                                >
                                    <option value="1" selected>faible</option>
                                    <option value="2">modéré</option>
                                    <option value="3">forte</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h5 class="d-inline-block mb-0 primary_color">
                                Liste des programmes qui comportent cette UE
                                <button
                                    type="button"
                                    class="btn btn-primary ml-2 mb-2"
                                    @click="openModalPro()"
                                >
                                    ajouter un programme
                                </button>
                            </h5>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1 p-2"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-8 p-2">Libellé</div>
                            <div class="col-md-2 p-2">Semestre</div>
                        </div>
                        <div
                            v-if="isLoadingProList"
                            class="p-3 text-center text-muted"
                        >
                            <div
                                class="spinner-border spinner-border-sm mr-2"
                                role="status"
                                aria-hidden="true"
                            ></div>
                            chargement des programmes...
                        </div>
                        <div v-else-if="!ue.pro.length" class="p-2 text-center">
                            aucune donnée à afficher
                        </div>

                        <div
                            v-else
                            v-for="(pro, index) in ue.pro"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeElement('pro', pro.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 PRO">{{ pro.code }}</div>
                            <div class="col-md-8 p-2">{{ pro.name }}</div>
                            <div class="col-md-2 p-1">
                                <input
                                    type="number"
                                    min="1"
                                    v-model.number="pro.semester"
                                    :max="pro.nbrSemester"
                                    placeholder="vide"
                                    class="form form-control"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h5 class="d-inline-block primary_color">
                                Liste des acquis d'apprentissage visé
                                <button
                                    type="button"
                                    class="btn btn-primary ml-2 mb-2"
                                    @click="openModalVise()"
                                >
                                    ajouter un acquis d'apprentissage visé
                                </button>
                            </h5>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-9 p-2">Nom</div>
                        </div>
                        <div
                            v-if="isLoadingAAVViseList"
                            class="p-3 text-center text-muted"
                        >
                            <div
                                class="spinner-border spinner-border-sm mr-2"
                                role="status"
                                aria-hidden="true"
                            ></div>
                            chargement des AAV visés...
                        </div>
                        <div v-else-if="!ue.aavvise.length" class="p-2 text-center">
                            aucune donnée à afficher
                        </div>

                        <div
                            v-else
                            v-for="(aav, index) in ue.aavvise"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="removeElement('aavvise', aav.id)"
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                            <div class="col-md-10 p-2">{{ aav.name }}</div>
                        </div>
                    </div>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h5 class="d-inline-block primary_color">
                                Liste des prérequis en termes d'UE
                            </h5>
                            <button
                                type="button"
                                class="btn btn-primary ml-2 mb-2"
                                @click="openModalUEPrerequis()"
                            >
                                ajouter un prérequis UE
                            </button>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-9 p-2">Nom</div>
                        </div>
                        <div
                            v-if="isLoadingUEPrerequis"
                            class="p-3 text-center text-muted"
                        >
                            <div
                                class="spinner-border spinner-border-sm mr-2"
                                role="status"
                                aria-hidden="true"
                            ></div>
                            chargement des prérequis UE...
                        </div>
                        <div
                            v-else-if="!ue.ueprerequis.length"
                            class="p-2 text-center"
                        >
                            aucune donnée à afficher
                        </div>
                        <div
                            v-else
                            v-for="(item, index) in ue.ueprerequis"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="
                                        removeElement('ueprerequis', item.id)
                                    "
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 UE">{{ item.code }}</div>
                            <div class="col-md-10 p-2">{{ item.name }}</div>
                        </div>
                    </div>
                    <div class="listComponent mb-5">
                        <div class="mb-2">
                            <h5 class="d-inline-block primary_color">
                                Liste des prérequis en termes d'AAV
                            </h5>
                            <button
                                type="button"
                                class="btn btn-primary ml-2 mb-2"
                                @click="openModalPrerequis()"
                            >
                                ajouter un prérequis
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-primary ml-2 mb-2"
                                :disabled="
                                    !Array.isArray(ue.aavprerequis) ||
                                    !ue.aavprerequis.length ||
                                    isLoadingUEPrerequis
                                "
                                @click="autoAddUEPrerequisFromAAV()"
                            >
                                ajouter les prérequis en terme d'UE automatiquement
                            </button>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-9 p-2">Nom</div>
                        </div>
                        <div
                            v-if="isLoadingAAVPrerequisList"
                            class="p-3 text-center text-muted"
                        >
                            <div
                                class="spinner-border spinner-border-sm mr-2"
                                role="status"
                                aria-hidden="true"
                            ></div>
                            chargement des AAV prérequis...
                        </div>
                        <div
                            v-else-if="!ue.aavprerequis.length"
                            class="p-2 text-center"
                        >
                            aucune donnée à afficher
                        </div>
                        <div
                            v-else
                            v-for="(aav, index) in ue.aavprerequis"
                            class="row"
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                        >
                            <div class="col-md-1 text-right p-2">
                                <i
                                    @click="
                                        removeElement('aavprerequis', aav.id)
                                    "
                                    class="text-danger fa fa-close pr-0"
                                    style="cursor: pointer"
                                ></i>
                            </div>
                            <div class="col-md-1 p-2 AAV">{{ aav.code }}</div>
                            <div class="col-md-10 p-2">{{ aav.name }}</div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$router.back()"
                        class="mr-2 btn btn-lg btn-secondary"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="btn btn-lg btn-primary"
                        v-if="this.id"
                    >
                        Modifier l'unité d'enseignement
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary" v-else>
                        Créer l'unité d'enseignement
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- MODAL CRÉATION AAV -->
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
                        Introduire un acquis d'apprentissage visé
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
                        <h6 class="primary_color"
                            >Libellé <strong class="text-danger">*</strong>
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
                        <h6 class="primary_color">Acquis d'apprentissage Terminal</h6>
                        <select
                            class="form-control"
                            v-model="aavForm.selectedAATId"
                            @change="addAAT"
                        >
                            <option value="" disabled>
                                — Sélectionner un AAT —
                            </option>
                            <option
                                v-for="aat in listAAT"
                                :key="aat.id"
                                :value="aat.id"
                            >
                                {{ aat.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="aavForm.aatSelected.length" class="mt-4">
                        <h6 class="mb-3 primary_color">AAT sélectionnés</h6>
                        <div class="d-flex align-items-center rounded p-2 bg-light border mb-2">
                            <div style="width: 44px"></div>
                            <div class="flex-grow-1 font-weight-bold">AAT</div>
                            <div class="font-weight-bold mr-2" style="width: 38%">Programme</div>
                            <div class="font-weight-bold" style="width: 26%">Contribution</div>
                        </div>

                        <div
                            :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                            v-for="(aat, index) in aavForm.aatSelected"
                            :key="aat.rowKey"
                            class="d-flex align-items-center rounded p-2"
                        >
                            <!-- SUPPRIMER -->
                            <button
                                class="btn btn-sm me-3"
                                @click="removeAAT(aat.rowKey)"
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
                            <!-- PROGRAMME -->
                            <div class="ms-3 mr-2" style="width: 38%">
                                {{ formatProgrammeLabel(aat) }}
                            </div>
                            <!-- CONTRIBUTION -->
                            <select
                                class="form-control ms-3"
                                style="width: 26%"
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
        v-if="showModalVise"
        :visible="showModalVise"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aavViseToExclude"
        type="AAV"
        :btnAddModal="true"
        @btnAddElementModal="handleNewAAV"
        btnAddElementMessage="Créer un acquis d'aprentissage"
        @close="showModalVise = false"
        @selected="handleSelected"
    />
    <modalList
        v-if="showModalPrerequis"
        :visible="showModalPrerequis"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aavPrerequisToExclude"
        type="AAV"
        @close="showModalPrerequis = false"
        @selected="handleSelected"
    />
    <modalList
        v-if="showModalUEPrerequis"
        :visible="showModalUEPrerequis"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="uePrerequisToExclude"
        type="UE"
        @close="showModalUEPrerequis = false"
        @selected="handleSelected"
    />
    <modalList
        v-if="showModalAAT"
        :visible="showModalAAT"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aatPrerequisToExclude"
        type="AAT"
        @close="showModalAAT = false"
        @selected="handleSelected"
    />
    <modalList
        v-if="showModalPro"
        :visible="showModalPro"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="proToExclude"
        type="PRO"
        @close="showModalPro = false"
        @selected="handleSelected"
    />
</template>

<script>
import modalList from "../modalList.vue";
import axios from "axios";
import { QuillEditor } from "@vueup/vue-quill";

export default {
    props: {
        csrfform: String,
        id: {
            type: [String, Number],
        },
        isCreate: {
            type: Boolean,
            default: false,
        },
        SemesterID: { type: Number },
        ProgramID: { type: Number },
        UEParentId: { type: Number, default: null },
    },
    components: { modalList },

    data() {
        return {
            activeForm: null,
            showModalVise: false,
            showModalPrerequis: false,
            showModalUEPrerequis: false,
            showModalCreateAAV: false,
            showModalAAT: false,
            showModalPro: false,
            modalRoute: "",
            modalTitle: "",
            aatPrerequisToExclude: [],
            proToExclude: [],
            aavViseToExclude: [],
            aavPrerequisToExclude: [],
            uePrerequisToExclude: [],
            isLoadingAATList: false,
            isLoadingProList: false,
            isLoadingAAVViseList: false,
            isLoadingAAVPrerequisList: false,
            isLoadingUEPrerequis: false,
            modalTarget: "", // 'aavvise' ou 'aavprerequis' ou 'ueprerequis'
            listAAT: [],
            aavForm: {
                selectedAATId: "",
                name: "",
                description: "",
                aatSelected: [], // [{ id, name, contribution }]
                contribution: "",
            },
            ueParent: {
                contribution: "",
            },
            ue: {
                aavvise: [],
                aavprerequis: [],
                ueprerequis: [],
                pro: [],
                aat: [],
                name: "",
                description: "",
                code: "",
                ects: "",
                aavs: {},
                ecs: {},
            },
            formAAvErrors: null,
            formErrors: null,
        };
    },
    methods: {
        addAAT() {
            if (!this.aavForm.selectedAATId) return;

            const aat = this.listAAT.find(
                (a) => a.id === Number(this.aavForm.selectedAATId),
            );

            if (!aat) return;

            this.aavForm.aatSelected.push({
                id: aat.id,
                name: aat.name,
                contribution: 1, // valeur par défaut
                level_contribution: aat.level_contribution,
                programme_code: aat.programme_code ?? null,
                programme_name: aat.programme_name ?? null,
                rowKey: `${aat.id}-${Date.now()}-${Math.random()}`,
            });

            this.aavForm.selectedAATId = "";
        },

        removeAAT(rowKey) {
            this.aavForm.aatSelected = this.aavForm.aatSelected.filter(
                (a) => a.rowKey !== rowKey,
            );
        },

        isAATAlreadySelected(id) {
            return this.aavForm.aatSelected.some((a) => a.id === id);
        },
        formatProgrammeLabel(aat) {
            const code = (aat?.programme_code || "").trim();
            const name = (aat?.programme_name || "").trim();
            if (!code && !name) return "-";
            if (code && name) return `${code} - ${name}`;
            return code || name;
        },
        handleNewAAV() {
            this.loadAAT();
            this.showModalCreateAAV = true;
        },

        closeModalAAV() {
            this.showModalCreateAAV = false;
            this.resetForm();
        },

        resetForm() {
            this.aavForm = {
                selectedAATId: "",
                name: "",
                description: "",
                aatSelected: [],
                contribution: "",
            };
        },

        openModalTerminal() {
            this.modalTarget = "aat";
            this.modalRoute = "/aat/get";
            this.modalTitle = "Ajouter des acquis d'apprentissage terminaux";
            this.aatPrerequisToExclude = this.ue.aat;
            this.showModalAAT = true;
        },
        openModalVise() {
            this.modalTarget = "aavvise";
            this.modalRoute = "/aav/get";
            this.modalTitle = "Ajouter des acquis d'apprentissage visés";
            this.aavViseToExclude = [
                ...this.ue.aavvise,
                ...this.ue.aavprerequis,
            ];
            this.showModalVise = true;
        },
        openModalPrerequis() {
            this.modalTarget = "aavprerequis";
            this.modalRoute = "/aav/get";
            this.modalTitle = "Ajouter des prérequis";
            this.aavPrerequisToExclude = [
                ...this.ue.aavvise,
                ...this.ue.aavprerequis,
            ];
            this.showModalPrerequis = true;
        },
        openModalUEPrerequis() {
            this.modalTarget = "ueprerequis";
            this.modalRoute = "/ues/get";
            this.modalTitle = "Ajouter des prerequis UE";

            const excluded = [...this.ue.ueprerequis];
            const currentUEId = Number(this.id || this.ue.id);
            if (Number.isInteger(currentUEId) && currentUEId > 0) {
                excluded.push({ id: currentUEId });
            }
            this.uePrerequisToExclude = excluded;
            this.showModalUEPrerequis = true;
        },
        async autoAddUEPrerequisFromAAV() {
            this.formErrors = null;

            const aavIds = (this.ue.aavprerequis || [])
                .map((item) => Number(item?.id))
                .filter((id) => Number.isInteger(id) && id > 0);

            if (!aavIds.length) {
                this.formErrors =
                    "Ajoutez d'abord des prérequis AAV pour générer la liste des prérequis UE.";
                return;
            }

            this.isLoadingUEPrerequis = true;
            try {
                const responses = await Promise.all(
                    aavIds.map((aavId) =>
                        axios.get("/aav/UEvise/get", {
                            params: { id: aavId },
                        }),
                    ),
                );

                const currentUEId = Number(this.id || this.ue.id);
                const ueById = new Map(
                    (this.ue.ueprerequis || []).map((ue) => [
                        Number(ue.id),
                        ue,
                    ]),
                );

                responses.forEach((response) => {
                    const ues = Array.isArray(response.data)
                        ? response.data
                        : [];

                    ues.forEach((ue) => {
                        const ueId = Number(ue?.id);
                        if (!Number.isInteger(ueId) || ueId <= 0) return;
                        if (
                            Number.isInteger(currentUEId) &&
                            currentUEId > 0 &&
                            ueId === currentUEId
                        ) {
                            return;
                        }
                        if (!ueById.has(ueId)) {
                            ueById.set(ueId, {
                                id: ue.id,
                                code: ue.code,
                                name: ue.name,
                            });
                        }
                    });
                });

                this.ue.ueprerequis = Array.from(ueById.values()).sort(
                    (a, b) =>
                        String(a?.code || "").localeCompare(
                            String(b?.code || ""),
                            undefined,
                            {
                                numeric: true,
                                sensitivity: "base",
                            },
                        ),
                );
            } catch (error) {
                console.error(error);
                this.formErrors =
                    "Impossible de générer automatiquement les prérequis UE.";
            } finally {
                this.isLoadingUEPrerequis = false;
            }
        },
        openModalPro() {
            this.modalTarget = "pro";
            this.modalRoute = "/pro/get";
            this.modalTitle = "Ajouter des programmes";
            this.proToExclude = this.ue.pro;
            this.showModalPro = true;
        },
        handleSelected(selectedItems) {
            if (this.modalTarget === "aavvise") {
                this.ue.aavvise.push(...selectedItems);
            } else if (this.modalTarget === "aavprerequis") {
                this.ue.aavprerequis.push(...selectedItems);
            } else if (this.modalTarget === "ueprerequis") {
                this.ue.ueprerequis.push(...selectedItems);
            } else if (this.modalTarget === "pro") {
                this.ue.pro.push(...selectedItems);
            } else if (this.modalTarget === "aat") {
                const itemsWithContribution = selectedItems.map((item) => ({
                    ...item,
                    contribution: 1,
                }));
                this.ue.aat.push(...itemsWithContribution);
            }
        },
        async submitFormUE() {
            try {
                this.formErrors = null;
                const normalizedPro = (this.ue.pro || []).map((pro) => {
                    const semesterRaw = pro?.semester;
                    const semesterNumber =
                        semesterRaw === "" ||
                        semesterRaw === null ||
                        typeof semesterRaw === "undefined" ||
                        Number.isNaN(Number(semesterRaw))
                            ? null
                            : Number(semesterRaw);

                    return {
                        ...pro,
                        semester:
                            semesterNumber !== null && semesterNumber >= 1
                                ? semesterNumber
                                : null,
                    };
                });

                const hasAtLeastOneProgramme = normalizedPro.some((pro) => {
                    const programId = Number(pro?.id);
                    return Number.isInteger(programId) && programId > 0;
                });

                if (!hasAtLeastOneProgramme) {
                    this.formErrors =
                        "Au moins un programme est obligatoire pour enregistrer une UE.";
                    window.scrollTo({ top: 0, behavior: "smooth" });
                    return;
                }

                if (this.id) {
                    const response = await axios.put("/ue/update", {
                        id: this.ue.id,
                        name: this.ue.name,
                        ects: this.ue.ects,
                        code: this.ue.code,
                        description: this.ue.description,
                        aavprerequis: this.ue.aavprerequis,
                        ueprerequis: this.ue.ueprerequis,
                        aavvise: this.ue.aavvise,
                        pro: normalizedPro,
                        aat: this.ue.aat,
                        ueParentContribution: this.ueParent.contribution,
                        ueParent: this.ueParent,
                    });

                    //  Si tout s'est bien passé
                    if (response.data.success) {
                        //  Redirection avec message (query param)
                        this.$router.push({
                            name: "ue-detail",
                            params: { id: this.ue.id },
                            query: { message: "Élément bien modifié" },
                        });
                    }
                } else {
                    const response = await axios.post("/ue/store", {
                        name: this.ue.name,
                        ects: this.ue.ects,
                        code: this.ue.code,
                        description: this.ue.description,
                        aavprerequis: this.ue.aavprerequis,
                        ueprerequis: this.ue.ueprerequis,
                        aavvise: this.ue.aavvise,
                        pro: normalizedPro,
                        aat: this.ue.aat,
                        ueParentID: this.ueParent.id,
                        ueParentContribution: this.ueParent.contribution,
                    });
                    //  Si tout s'est bien passé
                    if (response.data.success) {
                        //  Redirection avec message (query param)
                        this.$router.push({
                            name: "ue-detail",
                            params: { id: response.data.id },
                            query: { message: response.data.message },
                        });
                    }
                }
            } catch (error) {
                // Gestion des erreurs
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.errors
                ) {
                    // Laravel renvoie souvent { errors: { name: ['Le champ name est requis.'], ... } }
                    this.formErrors = Object.values(error.response.data.errors)
                        .flat()
                        .join(" ");
                } else if (error.response && error.response.data.message) {
                    this.formErrors = error.response.data.message;
                } else {
                    this.formErrors = "Une erreur inconnue est survenue.";
                }
                console.error(error);
            }
        },
        async loadAAT() {
            const response = await axios.get("/aat/get");
            this.listAAT = response.data;
        },
        async submitAAV() {
            this.formAAvErrors = null;
            if (!this.aavForm.name) {
                this.formAAvErrors = "Le champs libellé doit être présent";
                return;
            }

            const seenPairs = new Set();
            for (const row of this.aavForm.aatSelected) {
                const key = `${row.id}`;
                if (seenPairs.has(key)) {
                    this.formAAvErrors =
                        "Un même AAT ne peut être ajouté qu'une seule fois.";
                    return;
                }
                seenPairs.add(key);
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

                // Ajout dans la bonne liste
                if (this.modalTarget === "aavvise") {
                    this.ue.aavvise.push(createdAAV);
                } else if (this.modalTarget === "aavprerequis") {
                    this.ue.aavprerequis.push(createdAAV);
                }

                this.closeModalAAV();
            } catch (error) {
                console.error(error);
                alert("Erreur lors de la création.");
            }
        },
        async loadProgram() {
            this.isLoadingProList = true;
            try {
                const programId = this.$route.query.programID;
                const semesterNumber = this.$route.query.semesterNumber;

                if (!programId) {
                    return;
                }

                const response = await axios.get("/pro/get/detailed", {
                    params: { id: programId },
                });

                if (response.data) {
                    const alreadyLinked = this.ue.pro.some(
                        (pro) => pro.id === response.data.id,
                    );
                    const responseProgram = response.data;
                    responseProgram.semester = parseInt(semesterNumber, 10);
                    if (!alreadyLinked) {
                        this.ue.pro.push(responseProgram);
                    }
                }
            } catch (error) {
                console.log(error);
                this.formErrors =
                    "Impossible de charger le programme sélectionné.";
            } finally {
                this.isLoadingProList = false;
            }
        },

        async loadUEParent(idParent) {
            try {
                const responseUE = await axios.get("/UEGet/detailed", {
                    params: {
                        id: idParent,
                    },
                });
                this.ueParent = responseUE.data;
                this.ueParent.contribution = 1;
                console.log(responseUE.data);
            } catch (error) {
                console.log(error);
            }
        },
        async loadUE() {
            this.isLoadingAATList = true;
            this.isLoadingProList = true;
            this.isLoadingAAVViseList = true;
            this.isLoadingAAVPrerequisList = true;
            this.isLoadingUEPrerequis = true;
            try {
                const responseUE = await axios.get("/UEGet/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                // fusionne les données existantes et les données API
                this.ue = {
                    ...this.ue,
                    ...responseUE.data,
                };
                const responseAAT = await axios.get("/ue/aat/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue.aat = responseAAT.data;
                this.isLoadingAATList = false;
                const responsePro = await axios.get("/ue/pro/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue.pro = responsePro.data;
                this.isLoadingProList = false;
                const responseAAVvise = await axios.get("/ue/aavvise/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.ue.aavvise = responseAAVvise.data;
                this.isLoadingAAVViseList = false;
                const responseAAVprerequis = await axios.get(
                    "/ue/aavprerequis/get",
                    {
                        params: {
                            id: this.id,
                        },
                    },
                );
                this.ue.aavprerequis = responseAAVprerequis.data;
                this.isLoadingAAVPrerequisList = false;
                const responseUEprerequis = await axios.get(
                    "/ue/ueprerequis/get",
                    {
                        params: {
                            id: this.id,
                        },
                    },
                );
                this.ue.ueprerequis = responseUEprerequis.data;
            } catch (error) {
                console.log(error);
            } finally {
                this.isLoadingAATList = false;
                this.isLoadingProList = false;
                this.isLoadingAAVViseList = false;
                this.isLoadingAAVPrerequisList = false;
                this.isLoadingUEPrerequis = false;
            }
        },
        removeElement(type, id) {
            console.log(id);
            if (!this.ue || !this.ue[type] || !Array.isArray(this.ue[type]))
                return;

            const index = this.ue[type].findIndex((item) => item.id === id);

            if (index !== -1) {
                this.ue[type].splice(index, 1);
            }
        },
    },

    mounted() {
        if (this.$route.query.programID && this.$route.query.semesterNumber) {
            this.loadProgram();
        }
        if (this.$route.query.UEParentId) {
            this.loadUEParent(this.$route.query.UEParentId);
        }
        if (this.id) {
            this.loadUE();
        }
    },
};
</script>
<style>
.modal-backdrop {
    z-index: 1040;
}

.modal-dialog {
    z-index: 1050;
}

.modal.fade.show.d-block {
    background: rgba(0, 0, 0, 0.45);
}
</style>

