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
                    <span class="box_code ec pl-3 pr-3">{{ ec.ECCode }}</span>

                    {{ ec.ECname }}
                </h3>
            </div>
            <p class="mb-4">{{ ec.ECDescription }}</p>
        </div>
    </div>
</template>
<script>
import axios from "axios";
export default {
    props: {
        ecid: {
            type: [String, Number],
            required: true,
        },
        csrfform: String,
        ueroutestore: String,
        ecroutestore: String,
    },

    emits: ["close"],
    data() {
        return {
            ec: {
                ECname: "",
                ECDescription: "",
                ECCode: "",
            },
        };
    },
    methods: {
        async loadEC() {
            try {
                const response = await axios.get("/ECGet/detailed", {
                    params: {
                        ecid: this.ecid,
                    },
                });
                this.ec = response.data;
                console.log(response);
            } catch (error) {
                console.log(error);
            }
        },
    },

    mounted() {
        this.loadEC();
    },
};
</script>
