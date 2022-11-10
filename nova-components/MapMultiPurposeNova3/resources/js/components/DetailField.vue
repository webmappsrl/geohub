<template>
    <panel-item :field="field">
        <template #value>
        <wm-map-multi-purpose-nova3 :field="field"></wm-map-multi-purpose-nova3>
        </template>
    </panel-item>
</template>

<script>
export default {
    props: ['index','resource', 'resourceName', 'resourceId', 'field'],
}
</script>