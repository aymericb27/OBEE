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

            <span class="badge bg-light text-dark ml-2 border">
                {{ semester?.countECTS ?? "—" }} ECTS
            </span>

            <button
                @click="openModalUE()"
                class="btn btn-lg btn-primary ml-auto"
            >
                + ajout UE
            </button>
        </div>

        <!-- LISTE DES UEs -->
        <div v-if="isOpen" class="mt-3">
            <div v-for="UE in semester.UES" class="ue-block mb-3">
                <!-- UE HEADER -->
                <div class="d-flex align-items-center mb-1">
                    <i
                        class="fa-solid"
                        :class="
                            !UE.show ? 'fa-chevron-down' : 'fa-chevron-right'
                        "
                        style="cursor: pointer; font-size: 0.9rem"
                        @click="UE.show = !UE.show"
                    ></i>

                    <h5 class="d-inline-block ml-2 m-0"><span class="UE">{{ UE.code }}</span> {{ UE.name }}</h5>

                    <span class="badge badge-success ml-2"
                        >{{ UE.ects }} ECTS</span
                    >
                    <span class="ml-auto">
                            <i
                                style="font-size: 24px; color: #e70c0c"
                                class="fa-regular fa-trash-can mr-3"
                            ></i>
                        <router-link
                            :to="{
                                name: 'modifyUE',
                                params: { id: UE.id },
                            }"
                        >
                            <i
                                style="font-size: 24px"
                                class="fa-regular fa-pen-to-square"
                            ></i>
                        </router-link>
                    </span>

                    <button class="btn btn-lg btn-outline-secondary ml-3">
                        + ajout EC
                    </button>
                </div>

                <!-- ECs -->
                <div v-if="UE.show" class="ml-4">
                    <div
                        v-for="EC in UE.EC"
                        class="p-2 border rounded mb-2 d-flex align-items-center ec-card"
                    >
                        <span class="dot-green mr-2"></span>

                        <span class="flex-grow-1">
                            {{ EC.code }} - {{ EC.name }}
                        </span>

                        <span class="badge bg-light text-dark border">
                            {{ EC.ects }} ECTS
                        </span>

                        <i
                            class="fa-regular fa-pen-to-square ml-3"
                            style="cursor: pointer"
                        ></i>
                        <i
                            class="fa-solid fa-trash ml-3 text-danger"
                            style="cursor: pointer"
                        ></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        semester: { type: Object, required: true },
        number: { type: Number, required: true },
    },
    data() {
        return {
            isOpen: true,
        };
    },
    methods: {
        openModalUE() {
            console.log(this.semester.UES);
            this.$emit("open-ue-modal", this.semester);
        },
    },
};
</script>
