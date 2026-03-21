<template>
    <div
        class="modal fade"
        :class="{ show: show }"
        tabindex="-1"
        style="display: block"
        v-if="show"
    >
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation</h5>
                </div>

                <div class="modal-body">
                    <p>
                        Êtes-vous sûr de vouloir supprimer
                        <strong>{{ name }}</strong> ?
                    </p>

                    <div v-if="type == 'AAV'" class="alert alert-danger mt-3">
                        <div class="mb-3">
                            <i
                                class="fa-solid fa-triangle-exclamation"
                                style="color: crimson; font-size: 24px"
                            ></i>
                            Attention, supprimer un acquis d'apprentissage visé a des
                            conséquences. Cela influe sur les unités d'enseignement liées.
                        </div>
                        <div v-if="ueVise.length">
                            <div>
                                cet acquis d'apprentissage est visé par la/les unité(s)
                                d'enseignement suivante(s)
                            </div>
                            <ul class="mt-3">
                                <li v-for="ue in ueVise" :key="ue.id">
                                    <strong>{{ ue.name }}</strong>
                                </li>
                            </ul>
                        </div>
                        <div v-if="uePre.length">
                            <div>
                                cet acquis d'apprentissage est un prérequis de la/des unité(s)
                                d'enseignement suivante(s)
                            </div>
                            <ul class="mt-3">
                                <li v-for="ue in uePre" :key="ue.id">
                                    <strong>{{ ue.name }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="type == 'UE'" class="alert alert-danger mt-3">
                        <div class="mb-3">
                            <i
                                class="fa-solid fa-triangle-exclamation"
                                style="color: crimson; font-size: 24px"
                            ></i>
                            Attention, supprimer une unité d'enseignement a des
                            conséquences. Cela influe sur les programmes et les acquis
                            d'apprentissage terminaux.
                        </div>
                        <div v-if="ues.length">
                            <div>
                                cette unité d'enseignement est utilisée dans le/les programme(s)
                                suivant(s)
                            </div>
                            <ul class="mt-3">
                                <li
                                    v-for="pro in ues"
                                    :key="`${pro.id}-${pro.semester ?? ''}`"
                                >
                                    <strong>{{ pro.code }}</strong> {{ pro.name }}
                                    <span v-if="pro.semester">
                                        (semestre {{ pro.semester }})
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="aavs.length">
                            <div>
                                cette unité d'enseignement a des acquis d'apprentissage visés
                                qui se retrouveront sans unité d'enseignement si celle-ci est
                                supprimée
                            </div>
                            <ul class="mt-3">
                                <li v-for="aav in aavs" :key="aav.id">
                                    <strong>{{ aav.name }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="type == 'AAT'" class="alert alert-danger mt-3">
                        <div class="mb-3">
                            <i
                                class="fa-solid fa-triangle-exclamation"
                                style="color: crimson; font-size: 24px"
                            ></i>
                            Attention, supprimer un acquis d'apprentissage terminal a des
                            conséquences. Cela influe sur les acquis d'apprentissage liés.
                        </div>
                        <div v-if="aavs.length">
                            <div>
                                cet acquis d'apprentissage terminal a des acquis
                                d'apprentissage visés qui se retrouveront sans acquis
                                d'apprentissage terminal si celui-ci est supprimé
                            </div>
                            <ul class="mt-3">
                                <li v-for="aav in aavs" :key="aav.id">
                                    <strong>{{ aav.name }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="type == 'PRO'" class="alert alert-danger mt-3">
                        <div class="mb-3">
                            <i
                                class="fa-solid fa-triangle-exclamation"
                                style="color: crimson; font-size: 24px"
                            ></i>
                            Attention, supprimer un programme a des conséquences. Cela influe
                            sur les unités d'enseignement liées.
                        </div>
                        <div v-if="ues.length">
                            <div>
                                ce programme a des unités d'enseignement liées qui se
                                retrouveront sans programme si celui-ci est supprimé
                            </div>
                            <ul class="mt-3">
                                <li v-for="ue in ues" :key="ue.id">
                                    <strong>{{ ue.name }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="$emit('cancel')"
                    >
                        Annuler
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="$emit('confirm')"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    name: String,
    type: { type: String, default: "GENERAL" },
    idToDelete: [String, Number],
});

const ueVise = ref([]);
const aavs = ref([]);
const uePre = ref([]);
const ues = ref([]);
const loading = ref(false);

defineEmits(["confirm", "cancel"]);

watch(
    () => props.show,
    async (value) => {
        if (value) {
            ueVise.value = [];
            aavs.value = [];
            uePre.value = [];
            ues.value = [];
        }

        if (value && props.type === "AAV") {
            loading.value = true;
            try {
                const resVise = await axios.get("/aav/UEvise/get", {
                    params: { id: props.idToDelete },
                });
                ueVise.value = Array.isArray(resVise.data) ? resVise.data : [];

                const resPre = await axios.get("/aav/UEPrerequis/get", {
                    params: { id: props.idToDelete },
                });
                uePre.value = Array.isArray(resPre.data) ? resPre.data : [];
            } catch (err) {
                console.error(err);
                ueVise.value = [];
                uePre.value = [];
            } finally {
                loading.value = false;
            }
        }

        if (value && props.type === "AAT") {
            loading.value = true;
            try {
                const res = await axios.get("/aat/aavs/get", {
                    params: { id: props.idToDelete },
                });
                aavs.value = Array.isArray(res.data) ? res.data : [];
            } catch (err) {
                console.error(err);
                aavs.value = [];
            } finally {
                loading.value = false;
            }
        }

        if (value && props.type === "PRO") {
            loading.value = true;
            try {
                const res = await axios.get("/pro/ue/get", {
                    params: { id: props.idToDelete },
                });
                ues.value = Array.isArray(res.data) ? res.data : [];
            } catch (err) {
                console.error(err);
                ues.value = [];
            } finally {
                loading.value = false;
            }
        }

        if (value && props.type === "UE") {
            loading.value = true;
            try {
                const [resAAV, resPro] = await Promise.all([
                    axios.get("/ue/aavvise/get/onlyParent", {
                        params: { id: props.idToDelete },
                    }),
                    axios.get("/ue/pro/get", {
                        params: { id: props.idToDelete },
                    }),
                ]);

                aavs.value = Array.isArray(resAAV.data) ? resAAV.data : [];
                ues.value = Array.isArray(resPro.data) ? resPro.data : [];
            } catch (err) {
                console.error(err);
                aavs.value = [];
                ues.value = [];
            } finally {
                loading.value = false;
            }
        }

        if (!value) {
            ueVise.value = [];
            aavs.value = [];
            uePre.value = [];
            ues.value = [];
        }
    }
);
</script>

<style scoped>
.modal {
    background: rgba(0, 0, 0, 0.4);
}

.modal-content {
    border-radius: 8px;
}
</style>
