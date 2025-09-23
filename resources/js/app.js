import { createApp, ref, onMounted } from "vue";
import FormEC from "./components/FormEC.vue";
import FormUE from "./components/FormUE.vue";
import calendar from "./components/calendar.vue";
import calendarV2 from "./components/calendarV2.vue";
import listFramework from "./components/listFramework.vue";
import axios from "axios";

const app = createApp({
    setup() {
        const showForm = ref(false);
        const activeForm = ref(null);
        const activeView = ref("calendarV2");

        const toggleForm = (form) => {
            activeForm.value = activeForm.value === form ? null : form;
        };

        const toggleView = (view) => {
            activeView.value = view;
        };

        const hideForm = () => {
            showForm.value = false; // cacher le formulaire
        };

        return {
            showForm,
            activeForm,
            toggleForm,
            activeView,
            toggleView,
            hideForm,
        };
    },
});

app.component("calendar", calendar);
app.component("calendarv2", calendarV2);
app.component("form-ue", FormUE);
app.component("form-ec", FormEC);
app.component('list-framework',listFramework);
app.mount("#app");
