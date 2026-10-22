# Sicurezza PHP del server geohub (post PHP2Shell)

## Come funziona oggi

- Il PHP di produzione è il pacchetto di sistema di Ubuntu, servito da Apache: gli aggiornamenti
  di sicurezza arrivano via `apt` dal canale ESM di **Ubuntu Pro** (attivato il 22/10/2026 con un
  account di team). Niente PPA, niente immagini Docker per l'app in produzione.
- Le mitigazioni PHP2Shell vivono nella configurazione del server, non nel repo: il dettaglio
  operativo (dove stanno, come verificarle) è nel ticket oc:8837.
- L'app secondaria che condivideva la macchina e l'ambiente dev sono stati disattivati in modo
  permanente (oc:8837); la riaccensione è una decisione del team, documentata nel ticket.
- **Riesame ad agosto 2027**: se maphub non ha ancora sostituito geohub, la strategia qui sotto si
  riapre.

## Come ci siamo arrivati

- **Patch ESM invece dell'upgrade major di PHP** (oc:8837): la macchina sarà sostituita da maphub,
  quindi niente cambio di ramo PHP; il fix è la stessa versione in esecuzione con il solo cerotto
  di sicurezza. L'alternativa scartata era portare prod su Docker con un PHP recente, provando
  prima la compatibilità del codice: il tetto del codice è PHP 8.3, imposto da `nette/schema`
  (`"php": "7.1 - 8.3"` in composer.lock, rilevato il 22/10/2026).
- **Verifiche di configurazione PHP sempre attraverso il web server, mai da CLI** (oc:8833,
  confermato in oc:8837): i valori letti da shell non sono quelli del web server, e su questa
  macchina la differenza era reale (rilevato il 22/10/2026; i valori sono nel ticket).
