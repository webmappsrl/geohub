> Ticket: oc:8502

# Come impostare correttamente l'immagine di un layer — Implementation Plan

> **For agentic workers:** piano lineare, un dominio (documentazione). Usa `superpowers:executing-plans` in esecuzione.
>
> **⚠️ Webmapp override:** non eseguire `git commit` / `git add` / `git push` durante l’implementazione. I blocchi “Commit” sotto sono istruzioni testuali per il developer dopo il review-gate. Scrivi solo i file.

**Goal:** Guida HTML illustrata in italiano, inviabile in privato, che spiega la zona sicura dei loghi sulla feature image del **layer** (size S3 reali: 400×200 e 1440×500; non 1440×720).

**Architecture:** Nessun cambio a GeoHub/wm-core. Un solo artefatto inviabile: `guida-immagine-layer.html` (immagini inline JPEG). Sorgenti JPEG nella stessa cartella. Niente figura 225×100 (`size="225x100"` in wm-core non cambia l’URL).

**Tech Stack:** HTML + JPEG. Esempi da Ec Media 104362 (richiesta in esecuzione). Nessun test PHP.

## Global Constraints

- Repo: **geohub** — nessun submodule, nessun file PHP/Nova/wm-core
- Lingua guida: italiano, registro tecnico-comprensibile
- Un template master: overlay sulla miniatura **400×200** reale (file 1600×800 per nitidezza etichette)
- **1440×500**: confronto caso ticket (lati esclusi dal ritaglio 2:1)
- Nessuna figura 225×100; cover spiegato in prosa (card ~176 px)
- Paletti: nuovo media, lato ≥ 1440 e 2:1, non riusare track/POI
- In testa: settembre 2026 + size 400×200 / 1440×500
- Niente Canvas, niente helper Nova
- Commit message scope: `oc:8502` — solo dopo review-gate

## File map (as-built)

| File | Responsabilità |
|------|----------------|
| `esempio-originale-1440x500.jpg` | Originale S3 1440×500 (Ec Media 104362) |
| `confronto-1440x500.jpg` | Stesso originale + riquadro 2:1 (400×200) e lati esclusi |
| `template-400x200-zona-sicura.jpg` | Miniatura 400×200 ×4, overlay icona/titolo/zona visibile |
| `guida-immagine-layer.html` | Pagina autonoma, JPEG inline, un `<main>` |

---

### Task 1: Overlay 400×200

- [x] Annotare la thumbnail 400×200 reale (upscale 4× → 1600×800)
- [x] Icona alto-sinistra, fascia titolo in basso, tratteggio zona visibile

### Task 2: Confronto 1440×500 + originale

- [x] Salvare l’originale S3
- [x] Overlay riquadro verde 1000×500 centrato (2:1 su 1440×500) e fasce laterali
- [x] Nessun file 225×100

### Task 3: HTML guida

- [x] Testata: titolo, settembre 2026, size 400×200 e 1440×500
- [x] Ritaglio dal centro, procedura (nuovo media, 2:1 ≥ 1440, solo layer, zona sicura)
- [x] Tre figure; cover in prosa (~176 px); fallback tassonomia; ambito layer
- [x] JPEG inline (`data:image/jpeg`); un solo `<main>`; nota invio come allegato
- [x] 1440×720 solo come esempio di rapporto 2:1, non come cartella S3

### Task 4: Check overview

- [x] Requisiti overview spuntati contro l’HTML

---

### Commit (dopo review-gate, non in esecuzione)

```
docs(oc:8502): add layer feature-image safe-zone guide
```
