<template>
    <header>
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
        <h1>OBEE-tool</h1>
    </header>
    <router-view />
</template>
