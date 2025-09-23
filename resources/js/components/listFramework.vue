<template>
    <div id="listUniteEnseignement" class="mt-3">
        <ul>
            <li v-for="ue in ues" :key="ue.id" class="mb-3">
                <div class="row mb-2">
                    <div class="box_code">{{ ue.UEcode }}</div>
                    <div class="col-md-9">
                        <h3 class="primary_color mb-0">{{ ue.UEname }}</h3>
                    </div>
                </div>
                <p>{{ ue.UEdescription }}</p>
                <div class="row">
                    <a href="#" class="col-md-3 linkedbtn" @click="openDataUE('listEC')"
                        >voir les éléments constitutifs</a
                    >
                </div>
                <div class="listChildUE" v-if="showData == 'listEC'">
                    <ul class="p-0">
                        <li v-for="ec in ue.ecs">
                            <div class="row mb-2">
                                <div class="box_code">{{ ec.ECcode }}</div>
                                <div class="col-md-9">
                                    <h4 class="primary_color mb-0">
                                        {{ ec.ECname }}
                                    </h4>
                                </div>
                            </div>
                            <p>{{ ec.ECdescription }}</p>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            showData: false,
            ues: [], // liste des UE
        };
    },
    methods: {
        async loadUEs() {
            try {
                const response = await axios.get("/UEGet", {
                    params: {
                        withUE: true,
                    },
                });
                this.ues = response.data;
                console.log(this.ues);
            } catch (error) {
                console.error(error);
            }
        },
        openDataUE(listData) {
            this.showData = listData === this.showData ? false : listData;
        },
    },
    mounted() {
        this.loadUEs();
    },
};
</script>
