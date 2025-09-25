<template>
    <div>
        <div class="p-3 menu">
            <button @click="toggleForm('AAT')" class="btn btn-primary mb-3">
                Créer acquis d'apprentissage terminaux
            </button>
            <button @click="toggleForm('UE')" class="btn btn-primary mb-3">
                Créer unité d'enseignement
            </button>
            <button
                @click="toggleForm('EC') && loadUEs()"
                class="btn btn-primary mb-3"
            >
                Créer élément constitutif
            </button>
            <button @click="toggleForm('AAV')" class="btn btn-primary mb-3">
                Créer acquis d'apprentissage visé
            </button>
            <button @click="toggleForm('PR')" class="btn btn-primary mb-3">
                Créer prérequis
            </button>
        </div>
        <div class="container border mt-3 p-3 rounded bg-light">
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
                <form @submit.prevent="submitFormAAT" class="w-75">
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
                <form
                    @submit.prevent="submitFormElementConstitutif"
                    class="w-75"
                >
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
			formAAT : {},
            formEC: {
                currentUE: "",
                selectedUE: [],
            },
            formUE: {},
        };
    },
    methods: {
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
    },
};
</script>
