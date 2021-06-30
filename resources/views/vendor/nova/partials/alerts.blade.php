@if (isset($_GET['success-import']) && $_GET['success-import'] == 1)
    <div id="import-success" class="alert alert-success import-success">
        <p>Import completato con successo
            <button onclick="document.getElementById('import-success').style.display='none';">X</button>
        </p>
    </div>
@endif

@if (isset($_GET['error-import']) && $_GET['error-import'] == 'no-file')
    <div id="import-error" class="alert alert-error import-error">
        <p>Nessun File caricato.
            <button onclick="document.getElementById('import-error').style.display='none';">X</button>
        </p>
    </div>
@endif

@if (isset($_GET['error-import']) && $_GET['error-import'] == 'no-collection')
    <div id="import-error" class="alert alert-error import-error">
        <p>Il file caricato è una singola Feature. Caricare un geojson FeatureCollection.
            <button onclick="document.getElementById('import-error').style.display='none';">X</button>
        </p>
    </div>
@endif