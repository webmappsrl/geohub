<template>
    <panel-item :field="field">
        <template slot="value">
            <map-wm-embedmaps-field
                class="wm-embedmaps-field-map-container"
                v-if="field.value && (field.value['feature'] || field.value['features'])"
                :feature="field.value['feature']"
                :related="field.value['related'] ? field.value['related'] : []"
                :features="field.value['features']"
            ></map-wm-embedmaps-field>
            <span v-if="!field.value || (!field.value['feature'] && !field.value['features'])">
                -
            </span>
        </template>
    </panel-item>
</template>

<script>
export default {
    props: ["resource", "resourceName", "resourceId", "field"]
};
</script>
