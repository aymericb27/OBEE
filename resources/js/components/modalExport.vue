<template>
    <div
        v-if="visible"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
    >
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Exportation</h5>
                    <button type="button" class="close btn" @click="close">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div>
                        <!-- 🔍 Barre de recherche -->
                        <div class="mb-3">
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Rechercher par code ou nom..."
                            />
                        </div>

                        <div class="row border-bottom">
                            <div class="col-md-1"></div>
                            <div class="col-md-1 p-2">Code</div>
                            <div class="col-md-10 p-2">Nom</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="$emit('close')">
                        Annuler
                    </button>
                    <button class="btn btn-primary" @click="confirmSelection">
                        Ajouter
                    </button>
                </div>
            </div>
        </div>

        <!-- Overlay sombre -->
        <div class="modal-backdrop fade show"></div>
    </div>
</template>
<script>
export default {
    props: {
        visible: {
            type: Boolean,
            required: true,
        },
    },
    methods: {
        close() {
            this.visible = false;
        },
    },
};
</script>
<style scoped>
.modal-backdrop {
    z-index: 1040;
}
.modal-dialog {
    z-index: 1050;
}
</style>
