import { createApp } from "vue";
import router from "./router";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";

const el = document.getElementById("app");

const app = createApp({});
app.provide("csrf", el.dataset.csrf);
app.component("QuillEditor", QuillEditor);
app.provide("routeCalendar", el.dataset.routeCalendar);
app.use(router);
app.mount("#app");
