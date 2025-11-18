import { createApp } from "vue";
import router from "./router";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import App from "./App.vue";

const el = document.getElementById("app");

const app = createApp(App);
app.provide("csrf", el.dataset.csrf);
app.component("QuillEditor", QuillEditor);
app.provide("routeCalendar", el.dataset.routeCalendar);
app.use(router);
app.mount("#app");
