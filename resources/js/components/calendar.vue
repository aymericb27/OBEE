<template>
    <div class="p-4">
        <!-- Formulaire modal (simple) -->
        <div
            v-if="showFormCalendar"
            class="modal_calendar shadow-lg inset-0 bg-black bg-opacity-40 flex items-center justify-center"
        >
            <div class="bg-white p-3 rounded-xl w-96">
                <h2 class="text-lg font-bold mb-4">Nouveau cours</h2>
                <form @submit.prevent="ajouterEvenement">
                    <div class="row">
                        <div class="col-md-3 mb-2 mr-2">
                            <select
                                v-model="selectedEC"
                                class="form-select form-control"
                                required
                            >
                                <option disabled value="" selected>
                                    -- Choisir une unité d'enseignement --
                                </option>
                                <option
                                    v-for="ec in ecs"
                                    :key="ec.id"
                                    :value="ec.id"
                                >
                                    {{ ec.nom }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="d-inline mb-2">
                                Date :
                                <input
                                    v-model="form.date"
                                    type="date"
                                    class="border rounded w-full p-2"
                                    required
                                />
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end mt-4 space-x-2">
                        <button
                            type="button"
                            @click="fermerFormulaire"
                            class="mr-2 bg-gray-300 btn-primary rounded"
                        >
                            Annuler
                        </button>
                        <button type="submit" class="btn-primary rounded">
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <FullCalendar ref="calendrier" :options="calendarOptions" />
    </div>
    <!-- Le calendrier -->
</template>

<script>
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import frLocale from "@fullcalendar/core/locales/fr";
import interactionPlugin from "@fullcalendar/interaction";
import axios from "axios";

export default {
    components: { FullCalendar },
    data() {
        return {
            selectedEC: "",
            ecs: [],
            form: {
                selectedEC: "",
                date: "",
            },
            calendarOptions: {
                plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin],
                initialView: "dayGridMonth",
                events: [],
                dateClick: this.ouvrirFormulaire,
                headerToolbar: {
                    left: "prev,next",
                    center: "title",
                    right: "dayGridMonth,timeGridWeek,timeGridDay",
                },
                locales: [frLocale],
                locale: "fr",
            },
            showFormCalendar: false,
        };
    },
    methods: {
        async loadElementConstitutifs() {
            try {
                const response = await axios.get("/ECGet");
                this.ecs = response.data;
            } catch (error) {
                console.error("Erreur chargement UE :", error);
            }
        },
        ouvrirFormulaire(info) {
            this.loadElementConstitutifs();
            this.form.date = info.dateStr; // date cliquée
            this.form.title = "";
            this.showFormCalendar = true;
        },
        async ajouterEvenement() {
            const calendarApi = this.$refs.calendrier.getApi();
            console.log("ok");
            try {
                const response = await axios.get("/CalendarStore");
                this.ecs = response.data;
            } catch (error) {
                console.error("Erreur chargement UE :", error);
            }
            calendarApi.addEvent({
                title: this.form.selectedEC,
                start: this.form.date,
                allDay: true,
            });
            this.fermerFormulaire();
        },
        fermerFormulaire() {
            this.showFormCalendar = false;
        },
    },
};
</script>

<style>
/* Optionnel : animations, styles */
</style>
