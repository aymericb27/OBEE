<template>
    <vue-cal style="height: 600px" :events="events" default-view="week" />
</template>

<script>
import VueCal from "vue-cal";
import "vue-cal/dist/vuecal.css";

export default {
    components: { VueCal },
    data() {
        return {
            events: [
                {
                    start: "2025-09-16 10:00",
                    end: "2025-09-16 11:00",
                    title: "Réunion",
                },
                {
                    start: "2025-09-17 14:00",
                    end: "2025-09-17 15:30",
                    title: "Cours de yoga",
                },
            ],
        };
    },
};
</script>
