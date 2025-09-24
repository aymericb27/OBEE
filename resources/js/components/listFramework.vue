<template>
    <div class="p-3 border mb-3 rounded bg-light">
        <div id="filter" v-if="activeForm === null">
            <form @submit.prevent="submitFormFilter">
                <i class="fa-solid fa-filter mr-2"></i>
                <select
                    v-model="formFilter.displayElement"
                    class="mr-2 w-25 form-control d-inline-block"
                >
                    <option disabled value="" selected>
                        -- Affichage par --
                    </option>
                    <option value="UE" selected>Unité d'enseignement</option>
                    <option value="EC">éléments constitutifs</option>
                </select>
                <button type="submit" class="align-bottom btn btn-primary">
                    rechercher
                </button>
            </form>
        </div>
        <div v-if="activeForm === 'UE'">
            <form
                @submit.prevent="submitFormUniteEnseignement"
                v-if="activeForm === 'UE'"
                class="w-75"
            >
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Nom de l'unité d'enseignement"
                            v-model="formUE.name"
                            id="nom"
                            required
                        />
                    </div>
                    <div class="col-md-3">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="code"
                            v-model="formUE.code"
                            id="code"
                            required
                        />
                    </div>
                    <div class="col-md-3">
                        <input
                            type="number"
                            class="form-control"
                            placeholder="ects"
                            v-model="formUE.ects"
                            id="ects"
                            required
                        />
                    </div>
                </div>
                <div class="mb-3">
                    <textarea
                        class="form-control"
                        v-model="formUE.description"
                        placeholder="description"
                        id="description"
                        required
                    >
                    </textarea>
                </div>
                <div class="flex justify-end mt-4 space-x-2">
                    <button type="submit" class="btn btn-primary">
                        Ajouter l'unité d'enseignement
                    </button>
                    <button
                        type="button"
                        @click="hideForm"
                        class="ml-2 btn btn-primary"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
        <div v-if="activeForm === 'AAT'">
            <form
                @submit.prevent="submitFormAAT"
                class="w-75"
            >
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Nom de l'unité d'enseignement"
                            v-model="formAAT.name"
                            id="nom"
                            required
                        />
                    </div>
                    <div class="col-md-3">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="code"
                            v-model="formAAT.code"
                            id="code"
                            required
                        />
                    </div>
                    <div class="col-md-3">
                        <input
                            type="number"
                            class="form-control"
                            placeholder="ects"
                            v-model="formAAT.ects"
                            id="ects"
                            required
                        />
                    </div>
                </div>
                <div class="mb-3">
                    <textarea
                        class="form-control"
                        v-model="formAAT.description"
                        placeholder="description"
                        id="description"
                        required
                    >
                    </textarea>
                </div>
                <div class="flex justify-end mt-4 space-x-2">
                    <button type="submit" class="btn btn-primary">
                        Ajouter l'acquis d'apprentissage terminaux
                    </button>
                    <button
                        type="button"
                        @click="hideForm"
                        class="ml-2 btn btn-primary"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
        <div v-if="activeForm === 'EC'">
            <form @submit.prevent="submitFormElementConstitutif" class="w-75">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input
                            placeholder="Nom de l'élément constitutif"
                            type="text"
                            class="form-control"
                            v-model="formEC.name"
                            id="ECName"
                            required
                        />
                    </div>
                    <div class="col-md-3">
                        <input
                            placeholder="code"
                            type="text"
                            class="form-control"
                            v-model="formEC.code"
                            id="code"
                            required
                        />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <select
                            v-model="formEC.currentUE"
                            class="form-select form-control"
                            @change="addUEFormEC"
                        >
                            <option value="" disabled>
                                -- Choisir une unité d'enseignement --
                            </option>
                            <option
                                v-for="ue in ues"
                                :key="ue.UEid"
                                :value="ue.UEid"
                            >
                                {{ ue.UEname }}
                            </option>
                        </select>
                        <div class="mt-3">
                            <p
                                v-for="id in formEC.selectedUE"
                                :key="id"
                                class="primary_color me-2"
                            >
                                {{ getUEName(id) }}

                                <i
                                    class="fa-solid fa-xmark cursor_pointer"
                                    @click="removeUE(id)"
                                ></i>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <textarea
                            placeholder="description"
                            class="form-control"
                            v-model="formEC.description"
                            id="description"
                            required
                        >
                        </textarea>
                    </div>
                </div>
                <div class="flex justify-end mt-4 space-x-2">
                    <button type="submit" class="btn btn-primary">
                        Ajouter l'élément constitutif
                    </button>
                    <button
                        type="button"
                        @click="hideForm"
                        class="ml-2 btn btn-primary"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="listUniteEnseignement" class="mt-3">
        <ul>
            <li v-for="ue in ues" :key="ue.id" class="mb-3">
                <div class="row mb-2">
                    <div class="box_code">{{ ue.UEcode }}</div>
                    <div class="col-md-9">
                        <h3 class="primary_color mb-0">{{ ue.UEname }}</h3>
                    </div>
                </div>
                <p>{{ ue.UEdescription }}</p>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a
                            href="#"
                            class="linkedbtn"
                            @click="openDataUE('listEC', ue.UEid)"
                        >
                            <i
                                class="fa-solid fa-chevron-right"
                                v-if="!openedUE.includes(ue.UEid)"
                            ></i>
                            <i
                                class="fa-solid fa-chevron-down"
                                v-if="openedUE.includes(ue.UEid)"
                            ></i>
                            voir les éléments constitutifs</a
                        >
                    </div>
                </div>
                <div class="listChildUE" v-if="openedUE.includes(ue.UEid)">
                    <ul class="p-0">
                        <li v-for="ec in ue.ecs">
                            <div class="row mb-2">
                                <div class="box_code">{{ ec.ECcode }}</div>
                                <div class="col-md-9">
                                    <h4 class="primary_color mb-0">
                                        {{ ec.ECname }}
                                    </h4>
                                </div>
                            </div>
                            <p>{{ ec.ECdescription }}</p>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        csrfform: String,
        ueroutestore: String,
        ecroutestore: String,
    },
    data() {
        return {
            activeForm: null,
            showData: false,
            openedUE: [],
            formEC: {
                currentUE: "",
                selectedUE: [],
            },
            formUE: {},
            ues: [], // liste des UE
            formFilter: {},
        };
    },
    methods: {
        async submitFormFilter() {},
        async submitFormUniteEnseignement() {
            try {
                await axios.post(this.ueroutestore, {
                    name: this.formUE.name,
                    description: this.formUE.description,
                    code: this.formUE.code,
                    ects: this.formUE.ects,
                    _token: this.formUE.csrfform,
                });

                this.name = "";
                this.description = "";
                this.code = "";
                this.ects = "";
                this.loadUEs();
                this.hideForm();
            } catch (error) {
                console.error(error);
                alert("Erreur lors de l'ajout de l'UE.");
            }
        },
        async submitFormElementConstitutif() {
            try {
                await axios.post(this.ecroutestore, {
                    name: this.formEC.name,
                    code: this.formEC.code,
                    selectedUE: this.formEC.selectedUE,
                    description: this.formEC.description,
                    _token: this.csrfform,
                });
                this.loadUEs();
                this.hideForm();
            } catch (error) {
                console.error(error);
                alert("Erreur lors de l'ajout de l'EC.");
            }
        },
        toggleForm(form) {
            console.log(form);
            this.activeForm = this.activeForm === form ? null : form;
        },
        hideForm() {
            this.activeForm = null;
        },
        addUEFormEC() {
            if (
                this.formEC.currentUE &&
                !this.formEC.selectedUE.includes(this.formEC.currentUE)
            ) {
                this.formEC.selectedUE.push(this.formEC.currentUE);
            }
            // reset le select après ajout
            this.formEC.currentUE = "";
            console.log(this.formEC.selectedUE);
        },
        removeUE(id) {
            this.formEC.selectedUE = this.formEC.selectedUE.filter(
                (ueId) => ueId !== id
            );
        },
        getUEName(id) {
            const ue = this.ues.find((u) => u.UEid === id);
            return ue ? ue.UEname : id;
        },
        async loadUEs() {
            try {
                const response = await axios.get("/UEGet", {
                    params: {
                        withUE: true,
                    },
                });
                this.ues = response.data;
            } catch (error) {}
        },
        openDataUE(listData, ueID) {
            if (this.openedUE.includes(ueID)) {
                // déjà dedans → on le retire
                this.openedUE = this.openedUE.filter((id) => id !== ueID);
            } else {
                // pas dedans → on l’ajoute
                this.openedUE.push(ueID);
            }
            console.log(this.openedUE);
        },
    },
    mounted() {
        this.loadUEs();
    },
};
</script>
