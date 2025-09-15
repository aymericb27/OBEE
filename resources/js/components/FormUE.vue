<template>
  <div v-if="show" class="p-3 border mt-2 rounded bg-light w-50">
    <form @submit.prevent="submitForm">
      <div class="mb-3">
        <label for="UEname" class="form-label">Nom de l'unité d'enseignement</label>
        <input type="text" class="form-control" v-model="name" id="UEname" required>
      </div>

      <button type="submit" class="btn btn-primary">Ajouter l'unité d'enseignement</button>
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
      name: ''
    }
  },
  methods: {
    async submitForm() {
      try {
        await axios.post(this.route, {
          name: this.name,
          _token: this.csrf
        });

        this.name = '';
        this.$emit('submitted');
        this.$emit('refresh');
      } catch (error) {
        console.error(error);
        alert('Erreur lors de l\'ajout de l\'UE.');
      }
    }
  }
}
</script>