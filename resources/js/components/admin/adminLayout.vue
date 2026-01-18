<template>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-title">
                <div class="fw-bold">Administration</div>
                <div class="small text-muted">OBEE-tool</div>
            </div>

            <nav class="admin-nav">
                <router-link
                    class="admin-link "
                    :class="{ active: $route.name === 'admin-users' }"
                    :to="{ name: 'admin-users' }"
                >
                    <i class="fa-solid fa-user-check me-2 mr-2 primary_color"></i>
                    Valider les utilisateurs
                </router-link>

                <router-link
                    class="admin-link"
                    :class="{ active: $route.name === 'admin-universities' }"
                    :to="{ name: 'admin-universities' }"
                >
                    <i class="fa-solid fa-building-columns me-2 mr-2 primary_color"></i>
                    Universités
                </router-link>
            </nav>
        </aside>

        <main class="admin-content ml-3 mr-3 p-4 rounded border bg-white">
            <router-view />
        </main>
    </div>
</template>

<style scoped>
.admin-shell {
    display: flex;
    min-height: calc(100vh - 70px); /* ajuste si ton header fait ~70px */
}

.admin-sidebar {
    width: 260px;
    border: 1px solid #e5e7eb;
    background: #fff;
    padding: 16px;
}

.admin-title {
    padding: 8px 8px 16px 8px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 12px;
}

.admin-nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 12px;
}

.admin-link {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 10px;
    text-decoration: none;
    color: #111827;
}

.admin-link:hover {
    background: #f3f4f6;
}

.admin-link.active {
    background: #eef2ff;
    color: #1f2937;
    font-weight: 600;
}

.admin-content {
    flex: 1;
    padding: 18px;
    background: #f9fafb;
}
</style>
