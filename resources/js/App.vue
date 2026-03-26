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
                    <button
                        v-if="currentProgram.id"
                        class="btn btn-link text-danger p-0 ml-2"
                        type="button"
                        title="Retirer le programme courant"
                        @click="clearCurrentProgramSelection"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
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
</template>

<script>
import axios from "axios";
import {
    clearCurrentProgram,
    currentProgramState,
} from "./stores/currentProgram";

export default {
    computed: {
        isAdmin() {
            return window.__USER__ && window.__USER__.role === "admin";
        },
        currentProgram() {
            return currentProgramState;
        },
    },
    methods: {
        async logout() {
            try {
                await axios.post("/logout");
                window.location.href = "/login";
            } catch (e) {
                console.error("Logout failed", e);
            }
        },
        clearCurrentProgramSelection() {
            clearCurrentProgram();
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
</style>
