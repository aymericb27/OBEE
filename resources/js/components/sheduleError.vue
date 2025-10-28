<template>
    <div class="container pt-3">
        <div v-if="errors.length === 0">✅ Aucun conflit détecté.</div>

        <div v-else>
            <div
                v-for="(err, index) in errors"
                :key="index"
                class="alert alert-danger mb-2"
            >
                <i
                    class="fa-solid fa-triangle-exclamation"
                    style="color: #f3aa24; font-size: 24px"
                ></i>
                <span class="p-3">
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
                        elles partagent un acquis d'apprentissage dépendant.
                        L'acquis d'apprentissage ci-dessous est le prérequis de
                        l'unité d'enseignement
                        <router-link
                            :to="{
                                name: 'ue-detail',
                                params: { id: err.ueA.id },
                            }"
                        >
                            {{ err.ueA.code }}
                        </router-link>
                        et l'acquis d'apprentissage visé de l'unité
                        d'enseignement
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
                        elles partagent des acquis d'apprentissages dépendants.
                        Les acquis d'apprentissages ci-dessous sont les
                        prérequis de l'unité d'enseignement
                        <router-link
                            :to="{
                                name: 'ue-detail',
                                params: { id: err.ueA.id },
                            }"
                        >
                            {{ err.ueA.code }}
                        </router-link>
                        et les acquis d'apprentissages visés de l'unité
                        d'enseignement
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
                <ul
                    class="pt-3 list-style-type"
                    style="list-style-type: circle"
                >
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
        </div>
    </div>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
export default {
    data() {
        return {
            errors: [], // ✅ tableau d'erreurs
        };
    },

    methods: {
        async loadScheduleError() {
            const response = await axios.get("/Error/UES/shedule");
            this.errors = response.data.errorsHoraire || [];
            this.errors.forEach((err) => {
                // Vérifie que les objets ueA et ueB existent
                if (err.ueA) {
                    err.ueA.date_begin = formatDate(err.ueA.date_begin);
                    err.ueA.date_end = formatDate(err.ueA.date_end);
                }
                if (err.ueB) {
                    err.ueB.date_begin = formatDate(err.ueB.date_begin);
                    err.ueB.date_end = formatDate(err.ueB.date_end);
                }
            });
            console.log(response.data);
        },
    },

    mounted() {
        this.loadScheduleError(); // ✅ charge les données dès le montage
    },
};
</script>
