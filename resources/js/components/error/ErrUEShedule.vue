<template>
    <div
        v-for="(err, index) in errors.errorsShedule"
        :key="index"
        class="alert alert-danger mb-2"
    >
        <i
            class="fa-solid fa-triangle-exclamation"
            style="color: #f3aa24; font-size: 24px"
        ></i>
        <span class="p-2">
            <strong>Erreur :</strong> L’Unité d'enseignement
            <router-link
                :to="{
                    name: 'ue-detail',
                    params: { id: err.ueA.id },
                }"
            >
                {{ err.ueA.code }}
            </router-link>
            ({{ err.ueA.name }}) ne peut pas commencer avant
            <router-link
                :to="{
                    name: 'ue-detail',
                    params: { id: err.ueB.id },
                }"
            >
                {{ err.ueB.code }}
            </router-link>
            ({{ err.ueB.name }}) car
            <span v-if="err.aav.length === 1">
                elles partagent un acquis d'apprentissage dépendant. L'acquis
                d'apprentissage ci-dessous est le prérequis de l'unité
                d'enseignement
                <router-link
                    :to="{
                        name: 'ue-detail',
                        params: { id: err.ueA.id },
                    }"
                >
                    {{ err.ueA.code }}
                </router-link>
                et l'acquis d'apprentissage visé de l'unité d'enseignement
                <router-link
                    :to="{
                        name: 'ue-detail',
                        params: { id: err.ueB.id },
                    }"
                >
                    {{ err.ueB.code }} </router-link
                >.
            </span>
            <span v-else>
                elles partagent des acquis d'apprentissages dépendants. Les
                acquis d'apprentissages ci-dessous sont les prérequis de l'unité
                d'enseignement
                <router-link
                    :to="{
                        name: 'ue-detail',
                        params: { id: err.ueA.id },
                    }"
                >
                    {{ err.ueA.code }}
                </router-link>
                et les acquis d'apprentissages visés de l'unité d'enseignement
                <router-link
                    :to="{
                        name: 'ue-detail',
                        params: { id: err.ueB.id },
                    }"
                >
                    {{ err.ueB.code }} </router-link
                >.
            </span>
        </span>
        <ul class="pt-3 list-style-type" style="list-style-type: circle">
            <li v-for="aav in err.aav" class="smaller">
                <span>
                    <router-link
                        :to="{
                            name: 'aav-detail',
                            params: { id: aav.id },
                        }"
                    >
                        {{ aav.code }}
                    </router-link></span
                >
                {{ aav.name }}
            </li>
        </ul>
        <div>
            <strong>{{ err.ueA.name }} : </strong>
            du {{ err.ueA.date_begin }} au
            {{ err.ueA.date_end }}
        </div>
        <div>
            <strong>{{ err.ueB.name }} : </strong>
            du {{ err.ueB.date_begin }} au
            {{ err.ueB.date_end }}
        </div>
    </div>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    data() {
        return {

            errors: {
                errorsECTS: [],
                errorsShedule: [],
                isError: false,
            },
        };
    },
    methods: {
        async loadUE() {
            try {
                console.log(this.id);
                const responseError = await axios.get("/Error/UE", {
                    params: { id: this.id },
                });

                // ✅ Appliquer le format sur chaque UE des erreurs horaires
                const errorsData = responseError.data;
                if (errorsData.errorsShedule) {
                    errorsData.errorsShedule.forEach((err) => {
                        if (err.ueA) {
                            err.ueA.date_begin = formatDate(err.ueA.date_begin);
                            err.ueA.date_end = formatDate(err.ueA.date_end);
                        }
                        if (err.ueB) {
                            err.ueB.date_begin = formatDate(err.ueB.date_begin);
                            err.ueB.date_end = formatDate(err.ueB.date_end);
                        }
                    });
                }

                this.errors = errorsData;
            } catch (error) {
                console.error("Erreur lors du chargement des erreurs :", error);
            }
        },
    },

    mounted() {
        this.loadUE();
    },
};
</script>
