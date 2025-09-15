<template>
  <div v-if="show" class="p-3 border mt-2 rounded bg-light w-50">
    <form @submit.prevent="submitForm">
      <div class="mb-3">
        <label for="ECname" class="form-label">Nom de l'élément constitutif</label>
        <input type="text" class="form-control" v-model="name" id="ECname" required>
      </div>
      <div class="mb-3">
        <label for="UEList" class="form-label">liste des unités d'enseignements</label>
        <select v-model="selectedUE" class="form-select form-control" required>
          <option disabled value="">-- Choisir un UE --</option>
          <option v-for="ue in ues" :key="ue.id" :value="ue.id">
          {{ ue.nom }}
          </option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Ajouter l'élément constitutif</button>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    show: Boolean,
    csrf: String,
    route: String
  },
  data() {
    return {
      name: '',
      selectedUE: '', // id de l’UE choisi
      ues: []         // liste des UE
    }
  },
  methods: {
    async loadUEs() {
      try {
        const response = await axios.get("/UEGet");
        console.log(response.data);
        this.ues = response.data;
        console.log('ues chargé');
      } catch (error) {
        console.error("Erreur chargement UE :", error);
      }
    },
    async submitForm() {
      try {
        await axios.post(this.route, {
          name: this.name,
          _token: this.csrf
        });

        this.nom = '';
        this.$emit('submitted');
        this.$emit('refresh');
      } catch (error) {
        console.error(error);
        alert('Erreur lors de l\'ajout de l\'EC.');
      }
    }
  },
  mounted() {
    this.loadUEs();
  },
  
  watch: {
    show(newVal) {
      if (newVal) {
        this.loadUEs();
      }
    }
  },
  
}
</script>