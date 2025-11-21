<template>
    <div class="m-3 border p-4 bg-white rounded">
        <h5 class="mb-4">
            Contribution Des unités d'enseignements aux Acquis d'apprentissages
            Terminaux
        </h5>
        <h5 class="d-inline-block">Niveau de contribution :</h5>
        <span><span class="strong_mapping ml-2 mr-1">3</span>forte </span>
        <span><span class="medium_mapping ml-2 mr-1">2</span>moyenne </span>
        <span><span class="weak_mapping ml-2 mr-1">1</span>faible </span>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="border m-3 p-3 bg-white rounded">
                <h2 class="secondary_color">
                    <i class="fa-brands fa-gg-circle primary_color"></i> Acquis
                    d'apprentissage terminaux
                </h2>
                <ul class="secondary_color">
                    <li
                        class="p-2 aat-item"
                        :class="{ active: selectedAATId === aat.id }"
                        style="list-style-type: none; cursor: pointer"
                        v-for="aat in aats"
                        :key="aat.id"
                        @click="selectAAT(aat.id)"
                    >
                        <h5 class="m-0">{{ aat.name }}</h5>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-md-9">
            <div class="border m-3 p-4 bg-white rounded secondary_color">
                <div class="bg-primary rounded p-4">
                    <h5>
                        <i
                            class="fa-solid fa-book-bookmark"
                            style="
                                font-size: 24px;
                                color: rgb(90 171 255) !important;
                            "
                        ></i>
                        <router-link
                            v-if="aat.id"
                            :to="{
                                name: 'aat-detail',
                                params: { id: aat.id },
                            }"
                        >
                            {{ aat.code }}
                        </router-link>

                        {{ aat.name }}
                    </h5>
                </div>

                <div class="ml-4 mt-2" v-for="ue in aat.ues">
                    <div class="rounded p-4 bg-green">
                        <h5 class="d-inline-block">
                            <i
                                class="fa-solid fa-arrow-right"
                                style="color: #3ad55d"
                            ></i>
                            {{ ue.code }} {{ ue.name }}
                        </h5>
                        <span
                            :class="{
                                strong_mapping: ue.contribution === 3,
                                medium_mapping: ue.contribution === 2,
                                weak_mapping: ue.contribution === 1,
                            }"
                            class="float-right ml-2 mr-1"
                            >{{ ue.contribution }}</span
                        >
                    </div>
                    <div class="ml-4 mt-2" v-for="child in ue.children">
                        <div class="p-4 rounded bg-grey">
                            <div>
                                <h5 class="d-inline-block">
                                    <i
                                        class="fa-solid fa-arrow-right"
                                        style="color: rgb(167 167 167)"
                                    ></i>
                                    {{ child.code }} {{ child.name }}
                                </h5>
                                <span
                                    :class="{
                                        strong_mapping:
                                            child.contribution === 3,
                                        medium_mapping:
                                            child.contribution === 2,
                                        weak_mapping: child.contribution === 1,
                                    }"
                                    class="float-right ml-2 mt-2 mr-1"
                                    >{{ child.contribution }}</span
                                >
                            </div>
                            <div>
                                Contribution à l'unité d'enseignement parent :
                                <span v-if="child.ECContribution === 1"
                                    >faible</span
                                ><span v-if="child.ECContribution === 2"
                                    >modéré</span
                                ><span v-if="child.ECContribution === 3"
                                    >forte</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";

export default {
    data() {
        return {
            aats: [],
            selectedAATId: "",
            aat: {},
        };
    },
    components: {},

    methods: {
        selectAAT(id) {
            this.selectedAATId = id;
            this.loadAATTree(id);
        },
        async loadAATTree(id) {
            const responseAAT = await axios.get("aat/get/tree", {
                params: { id },
            });
            console.log(this.aat);

            this.aat = responseAAT.data;
        },
        async loadAAT() {
            const responseAAT = await axios.get("aat/get");
            this.aats = responseAAT.data;
            this.selectAAT(this.aats[0].id);
        },
    },
    mounted() {
        this.loadAAT();
    },
};
</script>
<style>
.bg-primary {
    background-color: #d2e6fb !important;
}

.aat-item {
    border-radius: 5px;
    transition: 0.2s;
}

.aat-item:hover {
    background: #f3f3f3;
}

.aat-item.active {
    background: rgba(42, 113, 205, 0.89);
    color: white;
}
.bg-green {
    background-color: #deffe9 !important;
}
.bg-grey {
    background-color: #f5f5f5 !important;
}
</style>
