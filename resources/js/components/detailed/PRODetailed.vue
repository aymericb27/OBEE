<template>
    <div class="back_btn">
        <a href="#" @click="$router.back()">
            <i class="fa-solid fa-circle-arrow-left primary_color"></i> Retour
        </a>
    </div>
    <div class="container">
        <div class="p-4 border rounded bg-light mt-3">
            <div class="row mb-2">
                <h3 class="primary_color ml-2 mb-0">
                    <span class="box_code PRO pl-2 pr-2">{{ pro.code }}</span>

                    {{ pro.name }}
                </h3>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";
import list from "../list.vue";
export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },
    components: {
        list,
    },

    data() {
        return {
            pro: {
                name: "",
                code: "",
            },
        };
    },
    methods: {
        async loadPRO() {
            try {
                const response = await axios.get("/pro/get/detailed", {
                    params: {
                        id: this.id,
                    },
                });
                this.pro = response.data;
            } catch (error) {
                console.log(error);
            }
        },
    },

    mounted() {
        this.loadPRO();
    },
};
</script>
