<template>
  <div class="container-fluid mt-2">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h3 class="mb-0">Gestion des utilisateurs</h3>
      <button class="btn btn-outline-secondary btn-sm" @click="load" :disabled="loading">
        <i class="fa-solid fa-rotate-right me-1"></i> Rafraichir
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="toast" class="alert alert-success">{{ toast }}</div>

    <div class="row mb-2">
      <div class="col-md-5 position-relative my-2">
        <input class="form-control ps-4" v-model="search" placeholder="Rechercher nom, prenom, email" />
        <i class="fa fa-search position-absolute search-icon"></i>
      </div>
      <div class="col-md-2 my-2">
        <select class="form-control" v-model="roleFilter">
          <option value="">Tous roles</option>
          <option value="admin">Admin</option>
          <option value="user">Utilisateur</option>
        </select>
      </div>
      <div class="col-md-2 my-2">
        <select class="form-control" v-model="statusFilter">
          <option value="">Tous statuts</option>
          <option value="approved">Approuves</option>
          <option value="pending">En attente</option>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-center justify-content-end">
        <span class="text-muted small">{{ filteredUsers.length }} utilisateur(s)</span>
      </div>
    </div>

    <div class="rounded border">
      <div class="row m-auto bg-light border-bottom fw-bold">
        <div class="col-md-2 p-2 pl-3">Nom</div>
        <div class="col-md-2 p-2 pl-3">Email</div>
        <div class="col-md-1 p-2 pl-3">Role</div>
        <div class="col-md-2 p-2 pl-3">Universite</div>
        <div class="col-md-1 p-2 pl-3">Statut</div>
        <div class="col-md-2 p-2 pl-3">Inscription</div>
        <div class="col-md-2 p-2 pl-3 text-end">Actions</div>
      </div>

      <div v-if="filteredUsers.length">
        <div
          v-for="(u, index) in filteredUsers"
          :key="u.id"
          class="row m-auto align-items-center"
          :class="index !== filteredUsers.length - 1 ? 'border-bottom' : ''"
        >
          <div class="col-md-2 p-3 secondary_color">
            <div class="fw-bold">{{ u.firstname || '-' }} {{ u.name || '' }}</div>
          </div>

          <div class="col-md-2 p-3 secondary_color">{{ u.email }}</div>

          <div class="col-md-1 p-3">
            <select class="form-control form-control-sm" v-model="drafts[u.id].role">
              <option value="admin">Admin</option>
              <option value="user">Utilisateur</option>
            </select>
          </div>

          <div class="col-md-2 p-3">
            <select class="form-control form-control-sm" v-model="drafts[u.id].university_id">
              <option :value="null">Aucune</option>
              <option v-for="uni in universities" :key="uni.id" :value="uni.id">{{ uni.name }}</option>
            </select>
          </div>

          <div class="col-md-1 p-3">
            <select class="form-control form-control-sm" v-model="drafts[u.id].is_approved">
              <option :value="true">Approuve</option>
              <option :value="false">En attente</option>
            </select>
          </div>

          <div class="col-md-2 p-3 secondary_color">{{ formatDate(u.created_at) }}</div>

          <div class="col-md-2 p-3 text-end">
            <button
              class="btn btn-primary btn-sm me-2"
              @click="save(u)"
              :disabled="savingId === u.id || !hasChanges(u)"
            >
              Enregistrer
            </button>
            <button
              class="btn btn-danger btn-sm ml-2"
              @click="remove(u)"
              :disabled="deletingId === u.id"
            >
              Supprimer
            </button>
          </div>
        </div>
      </div>

      <div v-else>
        <p class="p-3 text-center mb-0 text-muted">Aucun utilisateur</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "AdminUsersManagement",
  data() {
    return {
      users: [],
      universities: [],
      drafts: {},
      search: "",
      roleFilter: "",
      statusFilter: "",
      loading: false,
      savingId: null,
      deletingId: null,
      error: "",
      toast: "",
    };
  },
  computed: {
    filteredUsers() {
      const q = this.search.trim().toLowerCase();
      return this.users.filter((u) => {
        if (this.roleFilter && u.role !== this.roleFilter) return false;
        if (this.statusFilter === "approved" && !u.is_approved) return false;
        if (this.statusFilter === "pending" && u.is_approved) return false;

        if (!q) return true;
        const full = `${u.firstname || ""} ${u.name || ""}`.toLowerCase();
        const mail = (u.email || "").toLowerCase();
        return full.includes(q) || mail.includes(q);
      });
    },
  },
  methods: {
    formatDate(value) {
      if (!value) return "";
      try {
        return new Date(value).toLocaleString();
      } catch {
        return value;
      }
    },

    syncDrafts() {
      const next = {};
      this.users.forEach((u) => {
        next[u.id] = {
          role: u.role || "user",
          university_id: u.university_id ?? null,
          is_approved: !!u.is_approved,
        };
      });
      this.drafts = next;
    },

    hasChanges(user) {
      const draft = this.drafts[user.id];
      if (!draft) return false;

      return (
        draft.role !== user.role ||
        (draft.university_id ?? null) !== (user.university_id ?? null) ||
        !!draft.is_approved !== !!user.is_approved
      );
    },

    async load() {
      this.loading = true;
      this.error = "";
      this.toast = "";
      try {
        const [usersRes, uniRes] = await Promise.all([
          axios.get("/admin/users"),
          axios.get("/admin/universities/get"),
        ]);
        this.users = Array.isArray(usersRes.data) ? usersRes.data : [];
        this.universities = Array.isArray(uniRes.data) ? uniRes.data : [];
        this.syncDrafts();
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de charger les utilisateurs.";
      } finally {
        this.loading = false;
      }
    },

    async save(user) {
      const draft = this.drafts[user.id];
      if (!draft) return;

      this.error = "";
      this.toast = "";
      this.savingId = user.id;

      try {
        const payload = {
          role: draft.role,
          university_id: draft.university_id,
          is_approved: !!draft.is_approved,
        };

        const { data } = await axios.patch(`/admin/users/${user.id}`, payload);
        if (data?.user) {
          const idx = this.users.findIndex((x) => x.id === user.id);
          if (idx !== -1) this.users[idx] = data.user;
        }
        this.syncDrafts();
        this.toast = `Utilisateur ${user.email} mis a jour.`;
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de mettre a jour cet utilisateur.";
      } finally {
        this.savingId = null;
      }
    },

    async remove(user) {
      if (!confirm(`Supprimer ${user.email} ?`)) return;

      this.error = "";
      this.toast = "";
      this.deletingId = user.id;

      try {
        await axios.delete(`/admin/users/${user.id}`);
        this.users = this.users.filter((u) => u.id !== user.id);
        this.syncDrafts();
        this.toast = `Utilisateur ${user.email} supprime.`;
      } catch (e) {
        this.error = e?.response?.data?.message || "Impossible de supprimer cet utilisateur.";
      } finally {
        this.deletingId = null;
      }
    },
  },
  mounted() {
    this.load();
  },
};
</script>

<style scoped>
.search-icon {
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  color: darkgray;
  pointer-events: none;
}
</style>
