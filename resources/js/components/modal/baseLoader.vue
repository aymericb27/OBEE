<template>
  <div
    class="p-4 d-flex justify-content-center align-items-center"
    :class="fullHeight ? 'min-vh-50' : ''"
    role="status"
    aria-live="polite"
  >
    <div class="text-center">
      <div class="spinner-border primary_color" :class="sizeClass" aria-hidden="true"></div>
      <div v-if="text" class="mt-2 small text-muted">{{ text }}</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "BaseLoader",
  props: {
    text: { type: String, default: "Chargement..." },
    size: { type: String, default: "md" }, // sm | md | lg
    fullHeight: { type: Boolean, default: false },
  },
  computed: {
    sizeClass() {
      if (this.size === "sm") return "spinner-border-sm";
      // bootstrap n’a pas de "lg" natif -> on scale en css
      if (this.size === "lg") return "loader-lg";
      return "";
    },
  },
};
</script>

<style scoped>
/* optionnel : grande taille */
.loader-lg {
  width: 3rem;
  height: 3rem;
  border-width: 0.3em;
}

/* optionnel : hauteur de zone */
.min-vh-50 {
  min-height: 50vh;
}
</style>
