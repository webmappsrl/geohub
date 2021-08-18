<template>
  <default-field
    :field="field"
    :errors="errors"
    :show-help-text="showHelpText"
    :full-width-content="true"
  >
    <template slot="field">
      <map-wm-embedmaps-field
        class="wm-embedmaps-field-map-container"
        :feature="field.value['feature']"
        v-model="field.value['feature']"
        :editable="!this.field.viewOnly"
        :related="field.value['related'] ? field.value['related'] : []"
      ></map-wm-embedmaps-field>
    </template>
  </default-field>
</template>

<script>
import {FormField, HandlesValidationErrors} from "laravel-nova";

export default {
  mixins: [FormField, HandlesValidationErrors],
  props: ["resourceName", "resourceId", "field"],
  methods: {
    /*
     * Set the initial, internal value for the field.
     */
    setInitialValue() {
      if (!this.field.value)
        this.field.value = {};
      if (!this.field.value.feature)
        this.field.value.feature = {};
      this.value = this.field.value || "";
    },

    /**
     * Fill the given FormData object with the field's internal value.
     */
    fill(formData) {
      formData.append(this.field.attribute, this.value.feature.geometry.coordinates);
    }
  }
};
</script>
