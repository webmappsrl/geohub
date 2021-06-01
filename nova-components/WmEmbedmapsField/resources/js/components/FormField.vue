<template>
    <default-field
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
    >
        <template slot="field">
            <map-wm-embedmaps-field
                class="wm-embedmaps-field-map-container"
                v-if="field.value && field.value['feature']"
                :feature="field.value['feature']"
                :editable="true"
                :related="field.value['related'] ? field.value['related'] : []"
            ></map-wm-embedmaps-field>
        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from "laravel-nova";

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ["resourceName", "resourceId", "field"],

    methods: {
        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
            this.value = this.field.value || "";
        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            let lat = this.value.feature.geometry.coordinates[0],
                lng = this.value.feature.geometry.coordinates[1];
            formData.append(this.field.attribute, "ST_GeomFromText('POINT(" + lat + " " + lng + ")')");
        }
    }
};
</script>
