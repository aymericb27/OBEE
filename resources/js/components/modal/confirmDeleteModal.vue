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
                    <div v-if="type == 'UE'" class="alert alert-danger mt-3">
                        <div class="mb-3">
                            <i
                                class="fa-solid fa-triangle-exclamation"
                                style="color: crimson; font-size: 24px"
                            ></i>
                            Attention, supprimer une unité d'enseignement a des
                            conséquences ! Cela influe sur le programme et
                            l'acquis d'apprentissage terminal.
                        </div>
                        <div v-if="aavs.length">
                            <div>
                                cette unité d'enseignement a des acquis
                                d'apprentissages visés qui se retrouveront sans
                                unité d'enseignement si celle ci est supprimé
                            </div>
                            <ul class="mt-3">
                                <li v-for="aav in aavs">
                                    <strong>{{ aav.name }}</strong>
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

        <!-- Backdrop -->
        <div class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    name: String,
    type: { type: String, default: "GENERAL" }, // UE, AAV, AAT, etc.
    idToDelete: [String, Number], // id de l'objet à supprimer
});

const aavs = ref([]);
const loading = ref(false);
defineEmits(["confirm", "cancel"]);

watch(
    () => props.show,
    async (value) => {
        // Si c'est une UE : appeler les AAV avant suppression
        if (value && props.type === "UE") {
            loading.value = true;
            try {
                const res = await axios.get("/ue/aavvise/get/onlyParent", {
                    params: { id: props.idToDelete },
                });
                aavs.value = res.data;
            } catch (err) {
                console.error(err);
                aavs.value = [];
            } finally {
                loading.value = false;
            }
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
