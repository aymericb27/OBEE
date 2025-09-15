import { createApp, ref, onMounted } from 'vue';
import FormUE from './components/FormUE.vue';
import axios from 'axios';

const app = createApp({
  setup() {
    const showForm = ref(false);
    const ues = ref([]);

    const loadUEs = async () => {
      try {
        const response = await axios.get('/UEGet'); 
        ues.value = response.data;
      } catch (error) {
        console.error(error);
      }
    };

    onMounted(loadUEs);

    return { showForm, ues, loadUEs };
  }
});

app.component('form-ue', FormUE);
app.mount('#app');