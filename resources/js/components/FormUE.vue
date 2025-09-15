<template>
  <div v-if="show" class="p-3 border mt-2 rounded bg-light w-50">
    <form @submit.prevent="submitForm">
        <div class="mb-3 w-75">
            <label for="nom" class="form-label">Nom de l'unité d'enseignement</label>
            <input type="text" class="form-control" v-model="nom" id="nom" required>
        </div>
        <div class="mb-3 w-50">
            <label for="code" class="form-label">code</label>
            <input type="text" class="form-control" v-model="code" id="code" required>
        </div>
        <div class="mb-3 w-50">
            <label for="ects" class="form-label">ects</label>
            <input type="number" class="form-control" v-model="ects" id="ects" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">description</label>
            <textarea class="form-control" v-model="description" id="description" required>
            </textarea>
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
      nom: ''
    }
  },
  methods: {
    async submitForm() {
      try {
        await axios.post(this.route, {
          nom: this.nom,
          description: this.description,
          code : this.code,
          ects: this.ects,
          _token: this.csrf
        });

        this.nom = '';
        this.description = '';
        this.code = '';
        this.ects = '';
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