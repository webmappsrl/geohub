<template>
  <div>
    <heading class="mb-6">Import</heading>

    <card class="bg-dark bg-90 flex flex-col text-white" style="min-height: 300px;padding:5rem">
      <p class="text-white mb-2">Import massivo di dati: da questa interfaccia é possibile
        caricare file di dati da importare nel geohub. Al momento sono ammessi i seguenti tipi di file:</p>
      <ul class="text-white">
        <li>geojson (FeatureCollection)</li>
      </ul>
      <form action="import/geojson" method="POST" enctype="multipart/form-data">
        <div class="row">
          <div class="col text-center">
            <input type="file" id="file-geojson" name="geojson" accept=".geojson">
            <input type="hidden" name="_token" :value="csrf">
            <br>
          </div>
        </div>
        <p class="text-right">
          <button type="submit" class="btn btn-primary btn-default text-right">Confirm</button>
        </p>
      </form>
    </card>
  </div>
</template>

<script>
export default {
  data() {
    return {
      csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
  },
  metaInfo() {
    return {
      title: 'Import',
    }
  },
  mounted() {
    //
  },
}
</script>

<style>
/* Scoped Styles */
</style>
