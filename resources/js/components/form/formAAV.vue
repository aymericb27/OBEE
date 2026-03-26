<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container">
        <form @submit.prevent="saveProgram" class="border p-4 rounded bg-white">
            <h3 class="primary_color mb-4">
                Modification d'un acquis d'apprentissage visé
            </h3>
            <div class="mb-3">
                <input
                    placeholder="Libellé de l'acquis d'apprentissage visé"
                    type="text"
                    v-model="form.name"
                    class="form-control"
                    required
                />
            </div>
            <div class="form-group mb-5">
                <quill-editor
                    v-model:content="form.description"
                    placeholder="description"
                    content-type="html"
                    theme="snow"
                    style="height: 175px"
                    required
                ></quill-editor>
            </div>
            <div class="listComponent mb-5">
                <div class="mb-2">
                    <h4 class="d-inline-block primary_color">
                        Liste des acquis d'apprentissage terminaux
                        <button
                            type="button"
                            class="btn btn-primary ml-2 mb-2"
                            @click="openModalTerminal()"
                        >
                            ajouter un acquis d'apprentissage terminal
                        </button>
                    </h4>
                </div>
                <div class="row border-bottom">
                    <div class="col-md-1"></div>
                    <div class="col-md-1 p-2">Code</div>
                    <div class="col-md-5 p-2">Nom</div>
                    <div class="col-md-3 p-2">Programme</div>
                    <div class="col-md-2 p-2">Contribution</div>
                </div>
                <div
                    v-if="!form.aats || !form.aats.length"
                    class="p-2 text-center"
                >
                    aucune donnée à afficher
                </div>

                <div
                    v-for="(aat, index) in form.aats"
                    :key="aat.rowKey"
                    class="row"
                    :class="[index % 2 === 0 ? 'bg-light' : 'bg-white']"
                >
                    <div class="col-md-1 text-right p-2">
                        <i
                            @click="removeAAT(aat.rowKey)"
                            class="text-danger fa fa-close pr-0"
                            style="cursor: pointer"
                        ></i>
                    </div>
                    <div class="col-md-1 p-2 AAT">{{ aat.code }}</div>
                    <div class="col-md-5 p-2">{{ aat.name }}</div>
                    <div class="col-md-3 p-2">
                        <select class="form form-control" v-model="aat.fk_programme">
                            <option :value="null">- aucun programme -</option>
                            <option
                                v-for="pro in listProgramme"
                                :key="pro.id"
                                :value="pro.id"
                            >
                                {{ pro.code }} - {{ pro.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 p-2">
                        <select class="form form-control" v-model="aat.contribution">
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
            <button class="btn btn-primary" type="submit">
                Modifier l'acquis d'apprentissage visé
            </button>
        </form>
    </div>
    <modalList
        v-if="showModalAAT"
        :visible="showModalAAT"
        :routeGET="modalRoute"
        :title="modalTitle"
        :listToExclude="aatToExclude"
        type="AAT"
        @close="showModalAAT = false"
        @selected="handleSelectedAAT"
    />
</template>
<script>
import axios from "axios";
import { QuillEditor } from "@vueup/vue-quill";
import modalList from "../modalList.vue";

export default {
    props: {
        id: {
            type: [String, Number],
        },
    },
    components: { modalList },

    data() {
        return {
            showModalAAT: false,
            modalTarget: "",
            modalRoute: "",
            modalTitle: "",
            aatToExclude: [],
            listProgramme: [],
            form: {
                id: null,
                name: "",
                description: "",
                aats: [],
            },
        };
    },

    mounted() {
        this.loadProgrammes();
        this.loadAAV();
    },

    methods: {
        buildRowKey(id) {
            return `${id}-${Date.now()}-${Math.random()}`;
        },
        handleSelectedAAT(selectedItems) {
            const itemsWithContribution = selectedItems.map((item) => ({
                ...item,
                contribution: 1,
                fk_programme: this.getDefaultProgramIdForAAV(),
                rowKey: this.buildRowKey(item.id),
            }));
            this.form.aats.push(...itemsWithContribution);
        },
        removeAAT(rowKey) {
            this.form.aats = this.form.aats.filter((a) => a.rowKey !== rowKey);
        },
        getDefaultProgramIdForAAV() {
            const routeProgramId = Number(this.$route.query.programID);
            if (Number.isInteger(routeProgramId) && routeProgramId > 0) {
                return routeProgramId;
            }

            if (this.listProgramme.length === 1) {
                return this.listProgramme[0].id;
            }

            return null;
        },
        openModalTerminal() {
            this.modalTarget = "aat";
            this.modalRoute = "/aat/get";
            const aavName = (this.form.name || "").trim();
            this.modalTitle = aavName
                ? `Ajouter des acquis d'apprentissage terminaux pour : ${aavName}`
                : "Ajouter des acquis d'apprentissage terminaux";
            this.aatToExclude = [];
            this.showModalAAT = true;
        },
        async loadProgrammes() {
            try {
                const response = await axios.get("/pro/get");
                this.listProgramme = response.data || [];
            } catch (error) {
                console.log(error);
                this.listProgramme = [];
            }
        },
        async loadAAV() {
            try {
                const response = await axios.get("/aav/get/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.form = response.data;
                const responseAATS = await axios.get("/aav/aats/get", {
                    params: {
                        id: this.id,
                    },
                });
                this.form.aats = (responseAATS.data || []).map((row) => ({
                    ...row,
                    rowKey: this.buildRowKey(row.id),
                }));
                this.form.id = this.id;
            } catch (error) {
                console.log(error);
            }
        },
        async saveProgram() {
            const seenPairs = new Set();
            for (const row of this.form.aats) {
                const pairKey = `${row.id}|${row.fk_programme ?? "null"}`;
                if (seenPairs.has(pairKey)) {
                    alert(
                        "Un meme AAT ne peut etre ajoute qu'une seule fois pour un meme programme.",
                    );
                    return;
                }
                seenPairs.add(pairKey);
            }

            const payload = {
                id: this.form.id,
                name: this.form.name,
                description: this.form.description,
                aats: this.form.aats.map((aat) => ({
                    id: aat.id,
                    contribution: aat.contribution,
                    fk_programme: aat.fk_programme ?? null,
                })),
            };

            const response = await axios.post("/aav/update", payload);
            this.$router.push({
                name: "aav-detail",
                params: { id: this.form.id },
                query: { message: response.data.message },
            });
        },
    },
};
</script>
