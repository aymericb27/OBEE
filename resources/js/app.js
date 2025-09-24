import { createApp, ref } from "vue";
import calendar from "./components/calendar.vue";
import calendarV2 from "./components/calendarV2.vue";
import listtree from "./components/listtree.vue";
import listFramework from "./components/listFramework.vue";

const app = createApp({
    setup() {
        const activeView = ref("calendar");

        const toggleView = (view) => {
            activeView.value = view;
        };
        const openFormAddLesson = () => {
            const comp = app._instance.refs.calendarComp // instance Vue
            comp.openFormAddLesson()
        };

        const toggleFormListFramework = (form) => {
            const comp = app._instance.refs.listFrameworkComp
            comp.toggleForm(form)
        }

        return {
            activeView,
            toggleView,
            openFormAddLesson,
            toggleFormListFramework,
        };
    },
});

app.component("listtree", listtree);
app.component("calendar", calendar);
app.component("calendarv2", calendarV2);
app.component("list-framework", listFramework);
app.mount("#app");
