<template>
    <span
        v-if="normalized.has_anomaly"
        class="anomaly-badge"
        :class="`severity-${normalized.severity}`"
        :title="tooltipText"
    >
        <i class="fa-solid fa-triangle-exclamation"></i>
    </span>
</template>

<script>
export default {
    props: {
        summary: {
            type: Object,
            default: () => ({
                has_anomaly: false,
                count: 0,
                severity: "info",
            }),
        },
        tooltip: {
            type: String,
            default: "",
        },
        showCount: {
            type: Boolean,
            default: true,
        },
    },
    computed: {
        normalized() {
            const value = this.summary || {};
            return {
                has_anomaly: Boolean(value.has_anomaly),
                count: Number(value.count || 0),
                severity: value.severity || "info",
            };
        },
        tooltipText() {
            if (this.tooltip) return this.tooltip;
            return `${this.normalized.count} anomalie(s) détectée(s)`;
        },
    },
};
</script>

<style scoped>
.anomaly-badge {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 999px;
    font-weight: 600;
}

.anomaly-badge i {
    margin: 0;
    line-height: 1;
    display: block;
}

.severity-error {
    color: orange;
}

.severity-warning {
    color: orange;
}

.severity-info {
    color: #0f2f5f;
    background: #d7e8ff;
}
</style>
