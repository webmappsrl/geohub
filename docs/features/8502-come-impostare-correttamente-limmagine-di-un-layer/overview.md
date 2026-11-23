> Ticket: oc:8502

# Come impostare correttamente l'immagine di un layer

## Cosa cambia

Nasce una **guida illustrata HTML** (testo + template con zona sicura) che spiega al cliente come preparare l'immagine di un layer perché loghi e testo non vengano tagliati in app.

Un solo artefatto: HTML + JPEG in `docs/features/8502-come-impostare-correttamente-limmagine-di-un-layer/` (niente Canvas). In testa alla pagina: data e size S3 di riferimento (400×200 / 1440×500). Nessuna figura né size di testata 225×100: in wm-core `size="225x100"` non cambia l’URL (arriva il 400×200); il secondo ritaglio è solo CSS cover.

La guida si basa sui **formati thumbnail realmente generati su S3** (stesso elenco visibile in Ec Media, es. [104362](https://geohub.webmapp.it/resources/ec-medias/104362?tab=images)), non su size inventati (es. 1440×720 non esiste tra i crop AWS).

Per ora la guida **non** viene linkata da Nova: si invia in privato se il cliente ha di nuovo il problema (come oc:8474). Un helper GeoHub resta da valutare in un ciclo successivo.

## Perché

I clienti caricano spesso un banner 1440×500 (formato track/POI) come feature image del layer. In app il layer usa la thumbnail **400×200** (fit dal centro) e la card `layer-box` in wm-core fa un secondo ritaglio (`object-fit: cover`, altezza 176px). Loghi vicini ai bordi vengono “ghigliottinati”. Oggi si corregge a mano l’immagine; serve un artefatto riusabile.

Dump locale (settembre 2026): 515 layer, 291 con feature image propria. Di questi, 208 hanno il crop 400×200 e solo 98 il 1440×500 — molti originali sono già più piccoli del banner track. 20 media layer sono anche feature image di una track, 11 di un POI. 224 layer **non** hanno immagine propria (fallback tassonomia in `ConfTrait`).

## Requisiti

- [x] Pagina HTML autonoma in italiano, apribile/inviabile senza Claude né repo Git
- [x] Un solo template **400×200** (size reale di `layer.feature_image`) con zona sicura **ristretta**: loghi/testo al centro; **niente** nella fascia bassa (titolo della card) né in alto a sinistra (icona)
- [x] Nota breve (senza figura 225×100): dopo il 400×200 la card in home usa ritaglio cover (altezza fissa); non è un secondo formato da esportare — i loghi restano dentro la zona sicura
- [x] Confronto **1440×500**: caso da ticket (banner largo, loghi ai lati → i lati spariscono nel 400×200). In produzione è il caso rumoroso, non il più frequente (solo ~1/3 dei media layer ha il crop 1440×500; molti originali sono già più piccoli)
- [x] Spiegazione breve del ritaglio: `fit()` dal centro in GeoHub + cover e chrome wm-core (titolo in basso, icona in alto a sinistra)
- [x] Tre paletti anti-danno (obbligatori nel testo della guida):
  1. **Non sostituire** l’originale in Ec Media: se serve un 2:1, caricare un **nuovo** media e associarlo solo al layer
  2. Originale **grande** (lato lungo ≥ 1440) e rapporto **2:1** — mai un file 400×200 (`fit()` scarta i crop più grandi dell’originale, es. 1440×500)
  3. **Non riusare** il feature image della track/POI; questa guida vale **solo** per il layer
- [x] Nota: il sistema non “adatta” senza tagliare (fit dal centro + cover in app)
- [x] Immagini generate in Cursor (non spostare il lavoro su claude.ai)
- [x] Un solo deliverable: HTML + JPEG in `docs/features/8502-.../` — niente Canvas. In testa alla pagina: data e size di riferimento (400×200 / 1440×500)
- [x] Nessun link nel help Nova in questo ciclo (decisione: invio privato)
- [x] Una riga sul fallback: se il layer non ha immagine propria, in home può comparire quella della tassonomia — in quel caso va impostato un media sul layer. Niente template per le taxonomy

## Rischi

- **URL non pubblico**: l’HTML in `docs/` non è un link stabile per il cliente. Mitigazione: file autonomo da allegare/copiare su Drive quando serve; pubblicazione permanente out of scope
- **Secondo crop CSS + chrome**: la card in home usa `object-fit: cover` (altezza ~176px); `size="225x100"` in wm-core non cambia l’URL. Mitigazione: un template solo 400×200, zona sicura ristretta (no fascia bassa, no alto-sinistra); il cover è spiegato in prosa, senza figura 225×100
- **Guida che invecchia**: se cambiano `thumbnail_sizes` o il size usato per il layer, i template restano sbagliati. Mitigazione: in testa data + size S3 di riferimento (400×200 / 1440×500)
- **Confusione 1440×720**: il ticket lo citava ma AWS non lo genera. Mitigazione: non presentarlo come cartella S3; al massimo come *rapporto* 2:1 consigliato per l’originale
- **Sostituzione originale / EcMedia condivisa**: un 400×200 “corretto” al posto del 1440×500 perde i crop grandi e può rovinare track/OG. Mitigazione: i tre paletti nei requisiti (nuovo media, lato ≥ 1440, non riusare la foto della track)
- **Due artefatti**: Canvas + HTML divergono. Mitigazione: solo HTML + JPEG versionati nel repo
- **Rollback operativo**: git sul repo è banale; copie Drive/mail e media già ricaricati no. Mitigazione: data/size in testa (quale file è quello buono) + paletti “non sostituire l’originale”. Nessun kill switch oltre a smettere di inviare la guida

## Out of scope

- Link/helper nel campo Feature Image del Layer in Nova (da valutare dopo)
- Modificare `UpdateEcMedia::imgResize()` / `fit()`, aggiungere size 1440×720, o cambiare `layer-box`
- Template per tutti i 12 crop (108×148, 150×150, 335×250, …): non sono la home del layer
- Traduzione inglese della guida (GeoHub default `it`; audience cliente italiana)
- Correggere immagini layer già caricate (lavoro one-shot, già fatto su oc:8474)
- Template / guida dedicata alle feature image delle taxonomy (where, theme, activity, …)
- Canvas Cursor / artifact claude.ai

## Moduli toccati

Repo **geohub** (nessun submodule da modificare):

- `docs/features/8502-come-impostare-correttamente-limmagine-di-un-layer/` — overview, plan, notes, HTML della guida, JPEG degli esempi

Riferimento sola lettura (non si modifica il codice):

- `config/geohub.php` → `ec_media.thumbnail_sizes`
- `app/Jobs/UpdateEcMedia.php` → `imgResize()` / `fit()`
- `app/Traits/ConfTrait.php` → thumbnail layer `400x200`
- wm-core `layer-box` → `object-fit: cover`, altezza 176px, `size="225x100"`
