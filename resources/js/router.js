// resources/js/router.js
import { createRouter, createWebHistory } from "vue-router";
import ListFramework from "./components/listFramework.vue";
import tree from "./components/tree.vue";
import UEDetailed from "./components/detailed/UEDetailed.vue";
import AATDetailed from "./components/detailed/AATDetailed.vue";
import AAVDetailed from "./components/detailed/AAVDetailed.vue";
import FormUE from "./components/form/formUE.vue";
import PRODetailed from "./components/detailed/PRODetailed.vue";
import sheduleError from "./components/error/sheduleError.vue";
import ProgramError from "./components/error/programError.vue";
import ExportPanel from "./components/exportPanel.vue";
import levels from "./components/levels.vue";
import FormProgram from "./components/form/formProgram.vue";
import FormImport from "./components/form/formImport.vue";
const routes = [
    {
        path: "/",
        name: "index",
        component: ListFramework,
    },
    {
        path: "/tree",
        name: "tree",
        component: tree,
    },
    {
        path: "/levels",
        name: "levels",
        component: levels,
    },
    {
        path: "/list",
        name: "list",
        component: ListFramework,
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
        path: "/pro/:id",
        name: "pro-detail",
        component: PRODetailed,
        props: true,
    },
    {
        path: "/modifyUE/:id",
        name: "modifyUE",
        component: FormUE,
        props: true, // transmet automatiquement tous les params comme props
    },
    {
        path: "/createUE",
        name: "createUE",
        component: FormUE,
        props: true,
    },
    {
        path: "/formImport",
        name: "formImport",
        component: FormImport,
        props: true,
    },
    {
        path: "/createProgram",
        name: "createProgram",
        component: FormProgram,
        props: true,
    },
    {
        path: "/exportPanel",
        name: "exportPanel",
        component: ExportPanel,
    },
    {
        path: "/scheduleError",
        name: "sheduleError",
        component: sheduleError,
    },
    {
        path: "/programmeError",
        name: "programmeError",
        component: ProgramError,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
