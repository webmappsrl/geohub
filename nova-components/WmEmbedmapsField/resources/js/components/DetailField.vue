<template>
    <panel-item :field="field">
        <template slot="value">
            <map-wm-embedmaps-field
                class="wm-embedmaps-field-map-container"
                :geojson="field.value"></map-wm-embedmaps-field>
        </template>
    </panel-item>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],
}
</script>
