import { createApp, ref, onMounted } from "vue";
import FormUE from "./components/FormUE.vue";
import axios from "axios";

const app = createApp({
    setup() {
        const showForm = ref(false);
        const ues = ref([]);

        const loadUEs = async () => {
            try {
                const response = await axios.get("/UEGet");
                ues.value = response.data;
            } catch (error) {
                console.error(error);
            }
        };

        const hideForm = () => {
            showForm.value = false; // cacher le formulaire
        };

        onMounted(loadUEs);

        return { showForm, ues, loadUEs, hideForm };
    },
});

app.component("form-ue", FormUE);
app.mount("#app");
