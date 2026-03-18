<template>
    <div class="border rounded p-3 position-relative semester-card mb-4">
        <!-- HEADER -->
        <div class="d-flex align-items-center">
            <i
                class="fa-solid"
                :class="isOpen ? 'fa-chevron-down' : 'fa-chevron-right'"
                style="cursor: pointer; font-size: 1.1rem"
                @click="isOpen = !isOpen"
            ></i>

            <h5 class="d-inline-block primary_color m-0 ml-2">
                <i class="fa-solid fa-book-open mr-1"></i>
                Semestre
                {{ number }}
            </h5>

            <span class="badge badge-success text-white ml-2">
                {{ semester?.countECTS ?? "—" }} ECTS
            </span>
            <span class="badge bg-light text-dark ml-2 border">
                {{ semester?.ects ?? "—" }} ECTS
            </span>
            <span v-if="semester.ects !== semester.countECTS"
                ><i
                    style="color: orange"
                    class="fa-solid fa-triangle-exclamation"
                ></i
            ></span>
            <button
                @click="openModalUE('UE', null)"
                class="btn btn-lg btn-primary ml-auto"
            >
                + ajout UE
            </button>
        </div>

        <!-- LISTE DES UEs -->
        <div v-if="isOpen" class="mt-3">
            <div
                v-if="semester.UES.length !== 0"
                v-for="(UE, index) in semester.UES"
                class="ue-block mb-3"
            >
                <!-- UE HEADER -->
                <div class="d-flex align-items-center mb-1">
                    <span class="d-flex flex-column mr-3">
                        <button
                            class="btn btn-sm mb-1 p-0"
                            :disabled="isReordering || index === 0"
                            title="Monter"
                            @click="moveUE(UE, 'up')"
                        >
                            <i class="fa-solid fa-chevron-up"></i>
                        </button>
                        <button
                            class="btn btn-sm p-0"
                            :disabled="
                                isReordering ||
                                index === semester.UES.length - 1
                            "
                            title="Descendre"
                            @click="moveUE(UE, 'down')"
                        >
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </span>
                    <i
                        v-if="UE.children.length"
                        class="fa-solid"
                        :class="
                            UE.show ? 'fa-chevron-down' : 'fa-chevron-right'
                        "
                        style="cursor: pointer; font-size: 0.9rem"
                        @click="UE.show = !UE.show"
                    ></i>

                    <h5 class="d-inline-block ml-2 m-0">
                        <router-link
                            v-if="UE.id"
                            :to="{
                                name: 'ue-detail',
                                params: { id: UE.id },
                            }"
                        >
                            <span class="UE mr-2">{{
                                UE.code
                            }}</span> </router-link
                        >{{ UE.name }}
                    </h5>

                    <span class="badge badge-success ml-2"
                        >{{ UE.ects }} ECTS</span
                    >
                    <span class="ml-auto d-flex align-items-center">
                        <i
                            style="font-size: 24px"
                            @click="openModalDelete(UE)"
                            class="fa-regular fa-trash-can mr-3 deleteBtn"
                        ></i>
                        <router-link
                            :to="{
                                name: 'modifyUE',
                                params: { id: UE.id },
                            }"
                        >
                            <i
                                style="font-size: 24px"
                                class="fa-regular fa-pen-to-square primary_color"
                            ></i>
                        </router-link>
                    </span>

                    <button
                        class="btn btn-lg btn-outline-secondary ml-3"
                        @click="openModalUE('EC', UE)"
                    >
                        + ajout EC
                    </button>
                </div>

                <!-- ECs -->
                <div v-if="UE.show" class="ml-4">
                    <div
                        v-for="EC in UE.children"
                        class="p-3 border rounded mb-2 d-flex align-items-center ec-card"
                    >
                        <h5 class="d-inline-block ml-2 m-0">
                            <router-link
                                v-if="UE.id"
                                :to="{
                                    name: 'ue-detail',
                                    params: { id: EC.id },
                                }"
                            >
                                <span class="UE">{{ EC.code }}</span>
                            </router-link>
                            {{ EC.name }}
                        </h5>

                        <span class="badge badge-success ml-2"
                            >{{ EC.ects }} ECTS</span
                        >
                        <span class="ml-auto">
                            <i
                                style="font-size: 24px"
                                @click="openModalDelete(EC)"
                                class="fa-regular fa-trash-can mr-3 deleteBtn"
                            ></i>
                            <router-link
                                :to="{
                                    name: 'modifyUE',
                                    params: { id: EC.id },
                                }"
                            >
                                <i
                                    style="font-size: 24px"
                                    class="fa-regular fa-pen-to-square primary_color"
                                ></i>
                            </router-link>
                        </span>
                    </div>
                </div>
            </div>
            <div v-else>
                <p>Aucune unité d'enseignement présent dans ce semestre</p>
            </div>
        </div>
    </div>
    <ConfirmDeleteModal
        :show="modalDelete"
        :name="ueSelected.name"
        @confirm="deleteItem"
        @cancel="modalDelete = false"
    />
</template>

<script>
import axios from "axios";
import ConfirmDeleteModal from "./modal/confirmDeleteModal.vue";

export default {
    components: { ConfirmDeleteModal },

    props: {
        semester: { type: Object, required: true },
        number: { type: Number, required: true },
        programId: { type: Number, required: true },
    },
    emits: ["open-ue-modal", "deleteRefresh"],

    data() {
        return {
            ueSelected: {
                name: "",
            },
            modalDelete: false,
            isOpen: false,
            isReordering: false,
        };
    },
    methods: {
        async moveUE(UE, direction) {
            if (!UE?.id || !this.semester?.id || !this.programId) return;

            const currentIndex = this.semester.UES.findIndex(
                (item) => item.id === UE.id,
            );
            const targetIndex =
                direction === "up" ? currentIndex - 1 : currentIndex + 1;

            if (
                currentIndex < 0 ||
                targetIndex < 0 ||
                targetIndex >= this.semester.UES.length
            ) {
                return;
            }

            this.isReordering = true;
            try {
                await axios.post("/programme/ues/reorder", {
                    programme_id: this.programId,
                    semester_id: this.semester.id,
                    ue_id: UE.id,
                    direction,
                });

                // Mise a jour dynamique locale sans recharger tout le programme.
                const next = [...this.semester.UES];
                [next[currentIndex], next[targetIndex]] = [
                    next[targetIndex],
                    next[currentIndex],
                ];
                this.semester.UES.splice(0, this.semester.UES.length, ...next);
            } catch (error) {
                console.error("Erreur reorder UE :", error);
            } finally {
                this.isReordering = false;
            }
        },
        async deleteItem() {
            const response = await axios.delete("ue/delete", {
                params: {
                    id: this.ueSelected.id,
                },
            });
            this.modalDelete = false;
            this.$emit("deleteRefresh");
        },
        openModalDelete(UE) {
            this.modalDelete = true;
            this.ueSelected = UE;
        },
        openModalUE(type, UE) {
            this.$emit("open-ue-modal", {
                semester: this.semester,
                type: type,
                UE: UE,
            });
        },
    },
};
</script>
