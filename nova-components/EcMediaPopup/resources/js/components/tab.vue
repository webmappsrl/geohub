<template>
  <div>
    <slot v-if="isActive"></slot>
  </div>
</template>

<script>
export default {
  name: "tab",
  props: {
    name: { required: true },
    selected: { default: false },
  },
  data() {
    return {
      isActive: false,
    };
  },
  computed: {
    href() {
      return "#" + this.name.toLowerCase().replace(/ /g, "-");
    },
  },
  mounted() {
    this.isActive = this.selected;
  },
};
</script>

<style scoped>
</style>
