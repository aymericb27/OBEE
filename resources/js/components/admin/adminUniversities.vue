<template>
  <div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h3 class="mb-0">Universités</h3>
      <button class="btn btn-outline-secondary btn-sm" @click="load" :disabled="loading">
        <i class="fa-solid fa-rotate-right me-1"></i> Rafraîchir
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="toast" class="alert alert-success">{{ toast }}</div>

    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title mb-3">Créer une université</h5>

        <div class="row g-2">
          <div class="col-md-6">
            <input class="form-control" placeholder="Nom" v-model="form.name" />
          </div>
          <div class="col-md-3">
            <input class="form-control" placeholder="Code (optionnel)" v-model="form.code" />
          </div>
          <div class="col-md-3 d-grid">
            <button class="btn btn-primary" @click="create" :disabled="creating || !form.name.trim()">
              <i class="fa-solid fa-plus me-1"></i> Créer
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Code</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>

          <tbody v-if="items.length">
            <tr v-for="u in items" :key="u.id">
              <td>{{ u.name }}</td>
              <td>{{ u.code || "-" }}</td>
              <td class="text-end">
                <button class="btn btn-danger btn-sm" @click="remove(u)" :disabled="busyId === u.id">
                  <i class="fa-solid fa-trash me-1"></i> Supprimer
                </button>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="3" class="text-center p-4 text-muted">Aucune université</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "AdminUniversities",
  data() {
    return {
      items: [],
      loading: false,
      creating: false,
      busyId: null,
      error: "",
      toast: "",
      form: { name: "", code: "" },
    };
  },
  methods: {
    async load() {
      this.loading = true;
      this.error = "";
      this.toast = "";
      try {
        const { data } = await axios.get("/admin/universities/get");
        this.items = Array.isArray(data) ? data : [];
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de charger les universités.";
      } finally {
        this.loading = false;
      }
    },

    async create() {
      this.creating = true;
      this.error = "";
      this.toast = "";
      try {
        await axios.post("/admin/universities", {
          name: this.form.name.trim(),
          code: this.form.code.trim() || null,
        });
        this.form.name = "";
        this.form.code = "";
        this.toast = "Université créée ✅";
        await this.load();
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de créer l'université.";
      } finally {
        this.creating = false;
      }
    },

    async remove(uni) {
      if (!confirm(`Supprimer "${uni.name}" ?`)) return;

      this.busyId = uni.id;
      this.error = "";
      this.toast = "";
      try {
        await axios.delete(`/admin/universities/${uni.id}`);
        this.toast = "Université supprimée 🗑️";
        await this.load();
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de supprimer l'université.";
      } finally {
        this.busyId = null;
      }
    },
  },
  mounted() {
    this.load();
  },
};
</script>
