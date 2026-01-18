<template>
  <div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h3 class="mb-0">Validation des utilisateurs</h3>

      <button class="btn btn-outline-secondary btn-sm" @click="loadPending" :disabled="loading">
        <i class="fa-solid fa-rotate-right me-1"></i>
        Rafraîchir
      </button>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-md-6">
        <input
          class="form-control"
          placeholder="Rechercher par nom ou email..."
          v-model="search"
        />
      </div>
      <div class="col-md-6 d-flex justify-content-md-end align-items-center">
        <span class="text-muted small">
          {{ filtered.length }} en attente
        </span>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-if="loading" class="alert alert-info">
      Chargement...
    </div>

    <div v-else class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Date d'inscription</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>

          <tbody v-if="filtered.length">
            <tr v-for="u in filtered" :key="u.id">
              <td>{{ u.name }}</td>
              <td>{{ u.email }}</td>
              <td>{{ formatDate(u.created_at) }}</td>
              <td class="text-end">
                <button
                  class="btn btn-success btn-sm me-2"
                  @click="approve(u)"
                  :disabled="isBusy(u.id)"
                >
                  <i class="fa-solid fa-check me-1"></i>
                  Approuver
                </button>

                <!-- Optionnel : refuser/supprimer -->
                <button
                  class="btn btn-danger btn-sm ml-1"
                  @click="remove(u)"
                  :disabled="isBusy(u.id)"
                >
                  <i class="fa-solid fa-trash me-1"></i>
                  Refuser
                </button>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="4" class="text-center p-4 text-muted">
                Aucun utilisateur en attente 
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="toast" class="alert alert-success mt-3">
      {{ toast }}
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "AdminUsers",

  data() {
    return {
      pending: [],
      loading: false,
      error: "",
      toast: "",
      search: "",
      busyIds: new Set(),
    };
  },

  computed: {
    filtered() {
      const s = this.search.trim().toLowerCase();
      if (!s) return this.pending;

      return this.pending.filter((u) => {
        return (
          (u.name && u.name.toLowerCase().includes(s)) ||
          (u.email && u.email.toLowerCase().includes(s))
        );
      });
    },
  },

  methods: {
    isBusy(id) {
      return this.busyIds.has(id);
    },

    formatDate(iso) {
      if (!iso) return "";
      try {
        return new Date(iso).toLocaleString();
      } catch {
        return iso;
      }
    },

    async loadPending() {
      this.loading = true;
      this.error = "";
      this.toast = "";

      try {
        const { data } = await axios.get("/admin/users/pending");
        this.pending = Array.isArray(data) ? data : [];
      } catch (e) {
        this.error =
          e?.response?.data?.message ||
          "Impossible de charger les utilisateurs en attente.";
      } finally {
        this.loading = false;
      }
    },

    async approve(user) {
      this.error = "";
      this.toast = "";
      this.busyIds.add(user.id);

      try {
        await axios.post(`/admin/users/${user.id}/approve`);
        this.pending = this.pending.filter((u) => u.id !== user.id);
        this.toast = `✅ ${user.email} approuvé`;
      } catch (e) {
        this.error =
          e?.response?.data?.message || "Impossible d'approuver l'utilisateur.";
      } finally {
        this.busyIds.delete(user.id);
      }
    },

    async remove(user) {
      if (!confirm(`Refuser / supprimer ${user.email} ?`)) return;

      this.error = "";
      this.toast = "";
      this.busyIds.add(user.id);

      try {
        // optionnel : si tu n’as pas la route DELETE, enlève ce bouton
        await axios.delete(`/admin/users/${user.id}`);
        this.pending = this.pending.filter((u) => u.id !== user.id);
        this.toast = `🗑️ ${user.email} supprimé`;
      } catch (e) {
        this.error =
          e?.response?.data?.message || "Impossible de supprimer l'utilisateur.";
      } finally {
        this.busyIds.delete(user.id);
      }
    },
  },

  mounted() {
    this.loadPending();
  },
};
</script>
