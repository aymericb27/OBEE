// resources/js/router.js
import { createRouter, createWebHistory } from "vue-router";
import ListFramework from "./components/listFramework.vue";
import tree from "./components/tree.vue";
import UEDetailed from "./components/detailed/UEDetailed.vue";
import AATDetailed from "./components/detailed/AATDetailed.vue";
import AAVDetailed from "./components/detailed/AAVDetailed.vue";
import FormUE from "./components/form/formUE.vue";
import PRODetailed from "./components/detailed/PRODetailed.vue";
import ProgramAnalysis from "./components/detailed/programAnalysis.vue";
import sheduleError from "./components/error/sheduleError.vue";
import ProgramError from "./components/error/programError.vue";
import ExportPanel from "./components/exportPanel.vue";
import levels from "./components/levels.vue";
import FormProgram from "./components/form/formProgram.vue";
import FormImport from "./components/form/formImport.vue";
import FormAAT from "./components/form/formAAT.vue";
import FormAAV from "./components/form/formAAV.vue";
import AdminLayout from "./components/admin/adminLayout.vue";
import AdminUsersManagement from "./components/admin/adminUsersManagement.vue";
import AdminUniversities from "./components/admin/adminUniversities.vue";

const routes = [
    {
        path: "/",
        name: "index",
        component: ListFramework,
    },
    {
        path: "/admin",
        component: AdminLayout,
        children: [
            { path: "", redirect: { name: "admin-users-manage" } },
            { path: "users", name: "admin-users-manage", component: AdminUsersManagement },
            {
                path: "universities",
                name: "admin-universities",
                component: AdminUniversities,
            },
        ],
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
        props: true,
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
        path: "/pro/:id/analysis",
        name: "programAnalysis",
        component: ProgramAnalysis,
        props: true,
    },
    {
        path: "/modifyUE/:id",
        name: "modifyUE",
        component: FormUE,
        props: true,
    },
    {
        path: "/modifyAAV/:id",
        name: "modifyAAV",
        component: FormAAV,
        props: true,
    },
    {
        path: "/modifyAAT/:id",
        name: "modifyAAT",
        component: FormAAT,
        props: true,
    },
    {
        path: "/modifyPRO/:id",
        name: "modifyPRO",
        component: FormProgram,
        props: true,
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
        path: "/createAAT",
        name: "createAAT",
        component: FormAAT,
    },
    {
        path: "/createAAV",
        name: "createAAV",
        component: FormAAV,
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

router.beforeEach((to) => {
    const isAdmin = window.__USER__?.role === "admin";

    if (to.path.startsWith("/admin") && !isAdmin) {
        return { name: "list" };
    }
});

export default router;
