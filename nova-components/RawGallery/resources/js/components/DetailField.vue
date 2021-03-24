<template>
    <panel-item :field="field">
        <template slot="value">
            <div class="raw-gallery-container">
                <div class="raw-gallery-image" v-for="src of field.value">
                    <img v-bind:src="src">
                </div>
            </div>
        </template>
    </panel-item>

</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],
}
</script>
