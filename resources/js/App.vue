<template>
    <header class="app-header">
        <div class="listBtn m-3">
            <router-link to="/list">
                <button class="btn btn_fa">
                    <i class="fa-solid fa-list"></i>
                </button>
            </router-link>

            <router-link to="/levels">
                <button class="btn ml-1 btn_fa">
                    <i class="fa-solid fa-layer-group"></i>
                </button>
            </router-link>

            <router-link to="/tree">
                <button class="btn ml-1 btn_fa">
                    <i class="fa-solid fa-folder-tree"></i>
                </button>
            </router-link>
        </div>

        <h1 class="title">OBEE-tool</h1>

        <div class="userBtn m-3">
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

export default {
    computed: {
        isAdmin() {
            return window.__USER__ && window.__USER__.role === "admin";
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

.listBtn {
    left: 0px;
}
</style>
