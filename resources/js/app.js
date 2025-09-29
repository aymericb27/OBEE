import { createApp } from "vue";
import router from "./router";
const el = document.getElementById("app");

const app = createApp({});
app.provide("csrf", el.dataset.csrf);
app.provide("routeCalendar", el.dataset.routeCalendar);
app.use(router);
app.mount("#app");