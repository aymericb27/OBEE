// resources/js/router.js
import { createRouter, createWebHistory } from "vue-router";
import ListFramework from "./components/listFramework.vue";
import UEDetailed from "./components/UEDetailed.vue";
import AATDetailed from "./components/AATDetailed.vue";
import AAVDetailed from "./components/AAVDetailed.vue";
import Calendar from "./components/calendar.vue";
import AddForm from "./components/addForm.vue";
import sheduleError from "./components/sheduleError.vue";
const routes = [
    {
        path: "/",
        name: "index",
        component: ListFramework,
    },
    {
        path: "/calendar",
        name: "calendar",
        component: Calendar,
        props: {
            csrfform: "{{ csrf_token() }}",
            routeCalendar: "{{ route('calendar.store') }}",
        },
    },
    {
        path: "/form",
        name: "form",
        component: AddForm,
    },
    {
        path: "/list",
        name: "list",
        component: ListFramework,
        props: {
            csrfform: "{{ csrf_token() }}",
        },
    },
    {
        path: "/ue/:id",
        name: "ue-detail",
        component: UEDetailed,
        props: true, // 👈 active la transmission des params comme props
    },
    {
        path: "/aat/:id",
        name: "aat-detail",
        component: AATDetailed,
        props: true,
    },
        {
        path: "/aav/:id",
        name: "aav-detail",
        component: AAVDetailed,
        props: true,
    },
    {
        path: "/scheduleError",
        name:'sheduleError',
        component: sheduleError,
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});


export default router;
