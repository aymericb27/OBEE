import { createApp } from "vue";
import router from "./router";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import App from "./App.vue";
import axios from "axios";
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");
if (token) axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
const el = document.getElementById("app");

const app = createApp(App);
app.provide("csrf", el.dataset.csrf);
app.component("QuillEditor", QuillEditor);
app.provide("routeCalendar", el.dataset.routeCalendar);
app.use(router);
app.mount("#app");
