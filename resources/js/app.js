import { createApp, ref, onMounted } from "vue";
import FormEC from "./components/FormEC.vue";
import FormUE from "./components/FormUE.vue";

import axios from "axios";

const app = createApp({
    setup() {
        const showForm = ref(false);
        const activeForm = ref(null);
        const ues = ref([]);

        const toggleForm = (form) => {
            activeForm.value = activeForm.value === form ? null : form;
        };

        const loadUEs = async () => {
            try {
                const response = await axios.get("/UEGet");
                ues.value = response.data;
            } catch (error) {
                console.error(error);
            }
        };

        const loadECs = async () => {
            try {
                const response = await axios.get("/ECGet");
                ues.value = response.data;
            } catch (error) {
                console.error(error);
            }
        };

        const hideForm = () => {
            showForm.value = false; // cacher le formulaire
        };

        onMounted(loadUEs);

        return {
            showForm,
            activeForm,
            toggleForm,
            ues,
            loadUEs,
            loadECs,
            hideForm,
        };
    },
});

app.component("form-ue", FormUE);
app.component("form-ec", FormEC);
app.mount("#app");
