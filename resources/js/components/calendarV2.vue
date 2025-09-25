<template>
    <div class="p-4 container">
        <div class="menu">
            <button
                class="btn btn-primary m-1"
                @click="openFormAddLesson"
                v-if="activeView === 'calendar'"
            >
                Ajouter un cours
            </button>
        </div>
        <!-- Formulaire modal (simple) -->
        <div v-if="showFormAddLesson" class="p-4 border mb-3 rounded bg-light">
            <h2 class="text-lg font-bold mb-4">Nouveau cours</h2>
            <form @submit.prevent="addEvent">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input
                            list="unite_enseignement"
                            name="selectedEC"
                            placeholder="Choisir une unité d'enseignement"
                            v-model="form.selectedEC"
                            class="h-100 custom-select p-2"
                        />

                        <datalist
                            id="unite_enseignement"
                            class="form-datalist"
                            required
                        >
                            <option
                                v-for="ec in ecs"
                                :key="ec.id"
                                :value="ec.name"
                            ></option>
                        </datalist>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        <input
                            placeholder="date"
                            v-model="form.date_lesson"
                            :disabled="form.is_recurring"
                            type="date"
                            class="form-control"
                            required
                        />
                    </div>
                    <div class="col-md-4 box_checkbox">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            v-model="form.is_recurring"
                            value=""
                            id="flexCheckChecked"
                            checked
                        />
                        <label class="form-check-label" for="flexCheckChecked">
                            répeter le cours pour chaque semaine
                        </label>
                    </div>
                </div>
                <div v-if="form.is_recurring" class="row mb-3">
                    <div class="col-md-2">
                        <label class="d-inline-block">répéter du </label>
                        <input
                            placeholder="date"
                            v-model="form.date_lesson_begin_recurring"
                            type="date"
                            class="form-control d-inline-block"
                            required
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="d-inline-block"> au </label>
                        <input
                            placeholder="date"
                            v-model="form.date_lesson_end_recurring"
                            type="date"
                            class="form-control d-inline-block"
                            required
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="d-inline-block"> chaque </label>
                        <select
                            v-model="form.day_week_recurring"
                            class="form-control d-inline-block"
                        >
                            <option value="1" selected>Lundi</option>
                            <option value="2">Mardi</option>
                            <option value="3">Mercredi</option>
                            <option value="4">Jeudi</option>
                            <option value="5">Vendredi</option>
                            <option value="6">Samedi</option>
                            <option value="7">Dimanche</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <input
                            placeholder="heure de début"
                            class="form-control h-100"
                            type="time"
                            v-model="form.time_begin"
                            name="time_begin"
                            min="09:00"
                            max="18:00"
                            required
                        />
                    </div>
                    <div class="col-md-2">
                        <input
                            placeholder="heure de fin"
                            class="form-control h-100"
                            type="time"
                            name="time_end"
                            v-model="form.time_end"
                            min="09:00"
                            max="18:00"
                            required
                        />
                    </div>
                </div>
                <div class="flex justify-end mt-4 space-x-2">
                    <button
                        type="button"
                        @click="closeFormAddLesson"
                        class="mr-2 bg-gray-300 btn-primary rounded"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        @click="submitFormAddLesson"
                        class="btn-primary rounded"
                    >
                        Ajouter
                    </button>
                </div>
            </form>
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
    props: {
        csrf: String,
        route: String,
        routeCalendar: {
            type: String,
            required: true,
        },
    },
    components: { FullCalendar },
    data() {
        return {
            lessons: [],
            selectedDate: "",
            currentView: "week", // vue par défaut
            showFormAddLesson: false,
            selectedEC: "",
            selected_ec: "",

            ecs: [],
            form: {
                selectedEC: "",
                date_lesson: "",
                is_recurring: false,
                time_begin: "",
                time_end: "",
                date_lesson_begin_recurring: "",
                date_lesson_end_recurring: "",
                day_week_recurring: "",
            },
            calendarOptions: {
                plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin],
                initialView: "timeGridWeek",
                dateClick: this.loadDateClick,
                eventClick: this.handleEventClick,

                headerToolbar: {
                    left: "prev,next",
                    center: "title",
                    right: "dayGridMonth,timeGridWeek,timeGridDay",
                },
                locales: [frLocale],
                locale: "fr",
            },
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
        handleEventClick(info) {
            // info.event contient toutes les infos de l'événement
            console.log("ID de l'événement :", info.event.id);
            console.log("Titre :", info.event.title);
            console.log("Début :", info.event.start);

            // Si tu veux faire autre chose, par ex. ouvrir un modal :
            // this.selectedEventId = info.event.id;
            // this.showEventModal = true;
        },
        loadDateClick(info) {
            this.form.date_lesson = info.dateStr; // date cliquée
        },
        openFormAddLesson() {
            this.loadElementConstitutifs();
            this.showFormAddLesson = true;
        },
        async addEvent() {
            const ec = this.ecs.find(
                (item) => item.name === this.form.selectedEC
            );
            this.selected_ec = ec ? ec.id : null;
            try {
                const response = await axios.post(this.route, {
                    selected_ec: this.selected_ec,
                    is_recurring: this.form.is_recurring,
                    date_lesson: this.form.date_lesson,
                    time_begin: this.form.time_begin,
                    time_end: this.form.time_end,
                    date_lesson_begin_recurring:
                        this.form.date_lesson_begin_recurring,
                    date_lesson_end_recurring:
                        this.form.date_lesson_end_recurring,
                    day_week_recurring: this.form.day_week_recurring,
                    _token: this.csrf,
                });
                this.ecs = response.data;
            } catch (error) {
                console.error("Erreur chargement UE :", error);
            }
            this.loadCalendarLesson();
            this.closeFormAddLesson();
        },
        closeFormAddLesson() {
            console.log("close");
            this.showFormAddLesson = false;
        },
        async loadCalendarLesson() {
            try {
                const res = await axios.get("/calendarLesson/index");
                this.lessons = res.data;
                const calendarApi = this.$refs.calendrier.getApi();
				console.log(res);
                this.lessons.forEach((element) => {
                    calendarApi.addEvent({
						idcal: element.idcal,
						id : element.idec,
                        title: element.title,
                        start: element.start,
                        end: element.end,
                    });
                });

                console.log(res);
            } catch (error) {
                console.error("Erreur chargement events :", error);
            }
        },
    },
    async mounted() {
        this.loadCalendarLesson();
    },
};
</script>
