<template>
  <div class="container-fluid mt-2">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h3 class="mb-0">Universites</h3>
      <button class="btn btn-outline-secondary btn-sm" @click="load" :disabled="loading">
        <i class="fa-solid fa-rotate-right me-1"></i> Rafraichir
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="toast" class="alert alert-success">{{ toast }}</div>

    <div class="rounded border mb-3 p-3 bg-light">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="small text-muted mb-1">Nom</label>
          <input class="form-control" placeholder="Nom" v-model="form.name" />
        </div>
        <div class="col-md-3 d-grid">
          <button class="btn btn-primary" @click="create" :disabled="creating || !form.name.trim()">
            <i class="fa-solid fa-plus me-1"></i> Creer
          </button>
        </div>
      </div>
    </div>

    <div class="rounded border">
      <div class="row m-auto bg-light border-bottom fw-bold">
        <div class="col-md-8 p-2 pl-3">Nom</div>
        <div class="col-md-4 p-2 pl-3 text-end">Actions</div>
      </div>

      <div v-if="items.length">
        <div
          v-for="(u, index) in items"
          :key="u.id"
          class="row m-auto align-items-center"
          :class="index !== items.length - 1 ? 'border-bottom' : ''"
        >
          <div class="col-md-8 p-3 secondary_color">{{ u.name }}</div>
          <div class="col-md-4 p-3 text-end">
            <button class="btn btn-danger btn-sm" @click="remove(u)" :disabled="busyId === u.id">
              <i class="fa-solid fa-trash me-1"></i> Supprimer
            </button>
          </div>
        </div>
      </div>

      <div v-else>
        <p class="p-3 text-center mb-0 text-muted">Aucune universite</p>
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
        this.error = e?.response?.data?.message || "Impossible de charger les universites.";
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
        this.toast = "Universite creee";
        await this.load();
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de creer l'universite.";
      } finally {
        this.creating = false;
      }
    },

    async remove(uni) {
      if (!confirm(`Supprimer \"${uni.name}\" ?`)) return;

      this.busyId = uni.id;
      this.error = "";
      this.toast = "";
      try {
        await axios.delete(`/admin/universities/${uni.id}`);
        this.toast = "Universite supprimee";
        await this.load();
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de supprimer l'universite.";
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
