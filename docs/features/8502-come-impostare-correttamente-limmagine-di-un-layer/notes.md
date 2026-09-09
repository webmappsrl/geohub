> Ticket: oc:8502

# Notes — Come impostare correttamente l'immagine di un layer

## Deviazioni dal piano originale

- Esempi da Ec Media 104362 (richiesta in esecuzione), non diagrammi paesaggio Cursor. Marchi FIE/Toscana accettati per questo ciclo (caso oc:8474); se si reinvia ad altri clienti, valutare un master schematico.
- Figura 225×100 **tolta**: `size="225x100"` in wm-core non cambia l’URL (ConfTrait manda 400×200). Il cover della card è in prosa (~176 px).
- File mappa as-built: `esempio-originale-1440x500.jpg` + overlay 1440 + overlay 400×200. Estensione `.jpg` (erano JPEG salvati come `.png`).
- Overlay 400×200 è 1600×800 (4×) per etichette leggibili, non 400×200 pixel.
- HTML con JPEG inline (~435 KB): doppia sorgente voluta (file in cartella per edit, HTML unico da allegare). Non incollare nel body mail.
- Testo in registro tecnico-comprensibile; didascalia figura 3 allineata all’immagine (loghi rasentano il bordo della zona visibile, non “fuori”).
- Paletto «non sostituire» assoluto (qualsiasi Ec Media, non solo track).

## Bug trovati

Nessuno (nessun codice applicativo toccato).

## Decisioni

- Un solo template sulla miniatura 400×200 reale; 1440×500 come confronto; niente 225×100.
- Nessun Canvas; nessun helper Nova.
- Un `<main>`; header con `.wrap`.

## Follow-up

- Valutare in un ciclo successivo il link nel help Nova del Layer.
- Se cambiano `thumbnail_sizes` o il size in `ConfTrait` / `layer-box`, aggiornare data e figure in testa alla guida.
