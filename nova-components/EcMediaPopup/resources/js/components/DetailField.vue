<template>
  <panel-item :field="field" :full-width-content="true"/>
</template>

<script>
export default {
  props: ['resource', 'resourceName', 'resourceId', 'field'],
}
</script>
