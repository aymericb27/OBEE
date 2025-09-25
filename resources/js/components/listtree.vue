<template>
    <div class="p-3">
        <div class="row justify-content-center">
            <div
                class="col-md-3 box_AAT mr-3"
                @click="loadChildrenAAT(aat.id)"
                v-for="aat in aats"
                :key="aat.id"
            >
                <h3>{{ aat.description }}</h3>
            </div>
        </div>
        <div>
            <thead>
                <tr>
                    <th scope="col">Acquis d'apprentissage visé</th>
                    <th scope="col">Unite d'enseignement</th>
                    <th scope="col">ECTS</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="aav in aavs">
                    <td scope="row">{{ aav.AAVDescription }}</td>
                    <td><span v-for="ues in aav.ues">{{ ues.UEname }}</span></td>
                    <td><span v-for="ues in aav.ues">{{ ues.ueects }}</span></td>
                </tr>
            </tbody>
            <div v-for="aav in aavs">
                {{ aav.AAVDescription }}
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {},
    data() {
        return {
            aats: {},
            aavs: {
				ues:{}
			},
        };
    },
    methods: {
        async loadChildrenAAT(aatid) {
            console.log(aatid);
            try {
                const response = await axios.get("/AATGetChildren", {
                    params: {
                        aatid: aatid,
                    },
                });
                this.aavs = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
        async loadAAT() {
            try {
                const response = await axios.get("/AATGet");
                this.aats = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
    },

    mounted() {
        this.loadAAT();
    },
};
</script>
