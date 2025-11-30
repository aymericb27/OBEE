<template>
    <div
        class="modal fade"
        :class="{ show: show }"
        tabindex="-1"
        style="display: block"
        v-if="show"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation</h5>
                </div>

                <div class="modal-body">
                    <p>
                        Êtes-vous sûr de vouloir supprimer
                        <strong>{{ name }}</strong> ?
                    </p>
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
defineProps({
    show: { type: Boolean, required: true },
    name: { type: String, required: true },
});

defineEmits(["confirm", "cancel"]);
</script>

<style scoped>
.modal {
    background: rgba(0, 0, 0, 0.4);
}

.modal-content {
    border-radius: 8px;
}
</style>
