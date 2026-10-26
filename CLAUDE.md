# geohub

Backend Laravel 8 (PHP 7.4, Nova 3) di Geohub. Il repo è **pubblico**: nessun IP, hostname
interno, credenziale o dettaglio di configurazione dei server nei file e nei commit — i dettagli
operativi vivono nei ticket Orchestrator e sulle macchine.

## Conoscenza

| Argomento | Cosa copre | Pagina |
|---|---|---|
| Sicurezza PHP del server (post PHP2Shell) | Da dove arrivano i fix PHP di prod, perché non si fa l'upgrade major, riesame agosto 2027 | [docs/knowledge/php2shell-sicurezza-server-geohub.md](docs/knowledge/php2shell-sicurezza-server-geohub.md) |
| Ricerca delle tracce con Elastic | Chi scrive e chi legge gli indici, perché il nodo è privato, come si ripristina il servizio | [docs/knowledge/elastic-geohub.md](docs/knowledge/elastic-geohub.md) |
