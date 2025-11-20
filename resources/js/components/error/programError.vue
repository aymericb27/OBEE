<template>
    <div class="container pt-3">
        <div v-if="errors.length === 0">✅ Aucun conflit détecté.</div>

        <div v-else>
            <div
                v-for="(prog, index) in errors"
                :key="index"
                class="alert alert-danger mb-2"
            >
                <i
                    class="fa-solid fa-triangle-exclamation"
                    style="color: #f3aa24; font-size: 24px"
                ></i>
                <span class="">
                    <strong>Erreur :</strong> Le programme
                    <router-link
                        :to="{
                            name: 'pro-detail',
                            params: { id: prog.id },
                        }"
                        >{{ prog.code }}</router-link
                    >
                </span>
                ({{ prog.name }})
                <span v-if="prog.ects < prog.UEECts"
                    >a trop de crédits attribués.</span
                >
                <span v-else>n'a pas assez de crédits attribués.</span>
                <div class="mb-3">
                    Voici la liste des unités d'enseignements relié à ce
                    programme :
                </div>
                <ul class="px-5">
                    <li
                        class="row p-1"
                        style="border-bottom: 1px solid #721c24"
                    >
                        <span class="col-md-9">Nom</span>
                        <span class="col-md-3 text-center">ECTS</span>
                    </li>
                    <li v-for="(ue, index) in prog.ues" class="row p-1">
                        <span class="col-md-9">
                            <router-link
                                :to="{
                                    name: 'ue-detail',
                                    params: { id: ue.id },
                                }"
                            >
                                {{ ue.code }}
                            </router-link>
                            {{ ue.name }}
                        </span>
                        <span class="col-md-3 text-center">{{ ue.ects }}</span>
                    </li>
                    <li class="row p-1" style="border-top: 1px solid #721c24">
                        <div class="col-md-9">
                            Nombre de crédit requis :
                            <strong>
                                {{ prog.ects }}
                            </strong>
                        </div>
                        <div class="col-md-3 text-center">
                            <strong>
                                {{ prog.UEECts }}
                            </strong>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";

export default {
    data() {
        return {
            errors: [], // ✅ tableau d'erreurs
        };
    },

    methods: {
        async loadScheduleError() {
            const response = await axios.get("/Error/pro/ects/number");
            this.errors = response.data || [];
            console.log(response.data);
        },
    },

    mounted() {
        this.loadScheduleError(); // ✅ charge les données dès le montage
    },
};
</script>
