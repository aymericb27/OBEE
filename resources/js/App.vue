<template>
    <header>
        <div class="listBtn m-3">
            <router-link to="/list">
                <button class="btn_fa">
                    <i class="fa-solid fa-list"></i>
                </button>
            </router-link>
            <router-link to="/tree">
                <button class="ml-1 btn_fa">
                    <i class="fa-solid fa-tree"></i>
                </button>
            </router-link>
            <router-link to="/form">
                <button class="ml-1 btn_fa">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </router-link>
        </div>
        <h1>OBEE-tool</h1>
    </header>
    <router-view />
</template>
