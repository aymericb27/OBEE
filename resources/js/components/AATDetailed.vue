<template>
    <div class="back_btn">
        <a href="#" @click.prevent="$emit('close')">
            <i class="fa-solid fa-circle-arrow-left"></i> Retour
        </a>
    </div>
    <div class="container">
        <div class="p-4 border rounded bg-light mt-3">
            <div class="row mb-2">
                <h3 class="primary_color ml-2 mb-0">
                    <span class="box_code ec pl-3 pr-3">{{ aat.AATCode }}</span>

                    {{ aat.AATName }}
                </h3>
            </div>
            <p class="mb-4">{{ aat.AATDescription }}</p>
        </div>
    </div>
</template>
<script>
import axios from "axios";
export default {
    props: {
        aatid: {
            type: [String, Number],
            required: true,
        },
    },

    emits: ["close"],
    data() {
        return {
            aat: {
                AATName: "",
                AATDescription: "",
                AATCode: "",
            },
        };
    },
    methods: {
        async loadAAT() {
            try {
                const response = await axios.get("/AATGet/detailed", {
                    params: {
                        aatid: this.aatid,
                    },
                });
                this.aat = response.data;
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
