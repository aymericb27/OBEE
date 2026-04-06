<template>
    <header class="app-header">
        <div class="listBtn m-3">
            <router-link to="/list">
                <button
                    class="btn btn_fa"
                    title="affichage des élements sous forme de liste"
                >
                    <i class="fa-solid fa-list"></i>
                </button>
            </router-link>

            <router-link to="/levels">
                <button
                    class="btn ml-1 btn_fa"
                    title="liste des acquis d'apprentissages terminaux sous forme de contribution"
                >
                    <i class="fa-solid fa-layer-group"></i>
                </button>
            </router-link>

            <router-link to="/tree">
                <button
                    class="btn ml-1 btn_fa"
                    title="affichage des programmes sous forme de semestre"
                >
                    <i class="fa-solid fa-folder-tree"></i>
                </button>
            </router-link>
        </div>

        <h1 class="title">OBEE-tool</h1>

        <div class="userBtn m-3">
            <div class="mr-3">
                <button
                    class="btn btn-outline-warning btn-sm"
                    :disabled="isRefreshingAnomalies"
                    @click="refreshAnomalies"
                    title="Recalculer toutes les anomalies"
                >
                    <i
                        class="fa-solid fa-rotate-right mr-1"
                        :class="{ 'fa-spin': isRefreshingAnomalies }"
                    ></i>
                    {{
                        isRefreshingAnomalies
                            ? "Mise à jour..."
                            : "Refresh anomalies"
                    }}
                </button>
            </div>
            <div class="current-program mr-3">
                <span class="label">Programme courant</span>
                <div class="d-flex align-items-center">
                    <strong>
                        <router-link
                            v-if="currentProgram.id"
                            :to="{
                                name: 'pro-detail',
                                params: { id: currentProgram.id },
                            }"
                            class="PRO current-program-link"
                        >
                            {{ currentProgram.code || currentProgram.name }}
                        </router-link>

                        <span
                            v-if="currentProgram.code && currentProgram.name"
                            class="name"
                        >
                            - {{ currentProgram.name }}
                        </span>
                        <span v-else class="name">tous</span>
                    </strong>
                </div>
            </div>
            <router-link v-if="isAdmin" to="/admin/users">
                <button class="btn ml-1 btn_fa">
                    <i class="fa-solid fa-users-gear"></i>
                </button>
            </router-link>
            <button class="btn btn-danger ml-1" @click="logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </button>
        </div>
    </header>

    <router-view />
    <div
        v-if="refreshMessage"
        class="anomaly-refresh-toast"
        :class="refreshMessageType === 'error' ? 'error' : 'success'"
    >
        {{ refreshMessage }}
    </div>
</template>

<script>
import axios from "axios";
import { currentProgramState } from "./stores/currentProgram";

export default {
    data() {
        return {
            isRefreshingAnomalies: false,
            refreshMessage: "",
            refreshMessageType: "success",
        };
    },
    computed: {
        isAdmin() {
            return window.__USER__ && window.__USER__.role === "admin";
        },
        currentProgram() {
            return currentProgramState;
        },
    },
    methods: {
        async refreshAnomalies() {
            this.isRefreshingAnomalies = true;
            this.refreshMessage = "";
            try {
                const response = await axios.post("/anomalies/refresh");
                const payload = response?.data?.data;
                this.refreshMessage =
                    response?.data?.message ||
                    "Anomalies recalculées avec succès.";
                if (payload && typeof payload.ues === "number") {
                    this.refreshMessage += ` (${payload.ues} UE, ${payload.anomalies} anomalies)`;
                }
                this.refreshMessageType = "success";
                setTimeout(() => {
                    this.refreshMessage = "";
                }, 3500);
            } catch (e) {
                this.refreshMessageType = "error";
                this.refreshMessage =
                    e?.response?.data?.message ||
                    "Erreur lors du recalcul des anomalies.";
            } finally {
                this.isRefreshingAnomalies = false;
            }
        },
        async logout() {
            try {
                await axios.post("/logout");
                window.location.href = "/login";
            } catch (e) {
                console.error("Logout failed", e);
            }
        },
    },
};
</script>
<style>
.app-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.title {
    margin: auto;
}

.userBtn {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.current-program {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    line-height: 1.1;
}

.current-program .label {
    font-size: 0.75rem;
    color: #6c757d;
}

.current-program .name {
    font-weight: 400;
}

.listBtn {
    left: 0px;
}

.anomaly-refresh-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9999;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.anomaly-refresh-toast.success {
    background: #ecfdf3;
    color: #0f5132;
    border: 1px solid #b7ebc9;
}

.anomaly-refresh-toast.error {
    background: #fff2f0;
    color: #842029;
    border: 1px solid #f5c2c7;
}
</style>
