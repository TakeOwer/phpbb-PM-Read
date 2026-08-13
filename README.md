# phpbb-PM-Read
This extension allows board administrators to view users' private messages directly from the Admin Control Panel (ACP). Originally created by DeaDRoMeO, this version has been updated, enhanced, and maintained to ensure compatibility and improved functionality
PMRead — Documentazione tecnica

# PMRead — Documentazione tecnica

**Versione:** 1.5.0 · **Package:** `phpbbworld/pmread` · **Namespace:** `phpbbworld\pmread`
**Autore originale:** DeaDRoMeO (phpbbworld.ru) · **Licenza:** GPL-2.0
**Requisiti effettivi:** phpBB ≥ 3.2.0, PHP ≥ 5.4

> Questo documento sostituisce la versione redatta per il rilascio 1.3.2. Le modifiche introdotte dalla 1.4.0 e dalla 1.5.0 sono descritte in dettaglio nei rispettivi file di note.

---

## 1. Cosa fa

Estensione phpBB che aggiunge nell'ACP due schermate:

1. **Visualizza i messaggi** — elenco paginato di tutti i messaggi privati del forum con mittente, destinatari, copia nascosta, data, oggetto e corpo renderizzato. Da qui si cerca, si esporta in CSV, si stampa e si cancella.
2. **Impostazioni** — cancellazione automatica per età via cron, gruppi protetti, e avviso privacy mostrato agli utenti nel modulo di invio MP.

> ⚠️ **Privacy.** La funzione principale è l'accesso in chiaro a corrispondenza privata. In contesti UE serve una base giuridica e l'informativa del forum deve dichiararlo. Dalla 1.4.0 l'estensione mostra un avviso agli utenti proprio per questo. Vedi §9.

---

## 2. Struttura

```
pmread/                                   28 file
├── composer.json                         metadati, version-check remoto
├── ext.php                               classe di attivazione (default)
├── config/services.yml                   DI: listener, pm_manager, cron task
├── acp/
│   ├── main_info.php                     modulo + modes + permessi
│   ├── main_module.php                   controller ACP (lista, ricerca, export, stampa, impostazioni)
│   ├── pmread_info.php  pmread_module.php  wrapper legacy
├── adm/style/
│   ├── acp_pmread.html                   form di ricerca + elenco + azioni
│   └── acp_pmread_settings.html          impostazioni + anteprima avviso
├── service/pm_manager.php                logica condivisa: filtri, nomi, cancellazione, export
├── cron/task/prune_pms.php               pulizia automatica
├── event/listener.php                    lingua, avviso post-pulizia, flag avviso privacy
├── styles/all/template/event/
│   ├── overall_footer_after.html         alert JS dopo la pulizia
│   └── posting_pm_header_find_username_after.html   avviso privacy nel form MP
├── migrations/                           9 migrazioni
├── language/{en,it}/                     85 chiavi allineate
└── tests/smoke_test.php                  test offline senza bootstrap phpBB
```

---

## 3. Architettura

### Servizi (`config/services.yml`)

| Servizio | Classe | Dipendenze |
|---|---|---|
| `phpbbworld.pmread.listener` | `event\listener` | `config`, `template`, `user`, `request` |
| `phpbbworld.pmread.pm_manager` | `service\pm_manager` | `dbal.conn`, `config`, `service_container` |
| `phpbbworld.pmread.cron.task.prune_pms` | `cron\task\prune_pms` | `config`, `pm_manager`, `log` |

### Event listener

| Evento | Metodo | Scopo |
|---|---|---|
| `core.user_setup` | `load_language_on_setup()` | Carica il file lingua |
| `core.page_header_after` | `prepare_prune_notice()` | Avviso post-pulizia + flag `S_PMREAD_PM_NOTICE` |

L'avviso di pulizia usa il cookie `{cookie_name}_pmreadn` confrontato con `pmread_prune_notice_time`: ogni esecuzione genera al massimo un alert per utente.

---

## 4. Schermata "Visualizza i messaggi"

### Ricerca (1.5.0)

Campi tutti opzionali, combinati in AND: nome utente (parziale, jolly `*`, con selettore phpBB), ambito (mittente / destinatario / entrambi), email, parola chiave su oggetto e testo, intervallo Dal/Al, anno.

Tre scelte implementative rilevanti:

- **Nessun utente trovato → risultato vuoto**, non elenco completo. Un filtro silenziosamente ignorato mostrerebbe tutti i messaggi facendoli sembrare i risultati della ricerca.
- **Match sui destinatari**: la colonna viene racchiusa fra due punti e si cerca `:u_5:`, così `u_5` non matcha `u_50`. Coperti sia `to_address` sia `bcc_address`.
- **Date**: conversione via `create_datetime()` di phpBB, quindi nel fuso della board. Con `strtotime()` i messaggi a cavallo di mezzanotte cadrebbero nel giorno sbagliato.

I filtri viaggiano nella query string e restano attivi cambiando pagina.

### Azioni

| Pulsante | Effetto |
|---|---|
| Cancella selezionati | `delete_pms_hard($marked)`, con `confirm_box` |
| Cancella tutti i messaggi | `delete_all_pms()`, con `confirm_box` |
| Stampa selezionati | Documento di stampa dei messaggi spuntati |
| Stampa risultati | Documento di stampa di tutti i risultati della ricerca |
| Esporta selezionati (CSV) | CSV dei messaggi spuntati |
| Esporta tutti (CSV) | CSV dei **risultati della ricerca** corrente |

Le cancellazioni scrivono nel log admin. Export e stampa sono protetti da `check_form_key` (la 1.4.0 ha aggiunto l'`add_form_key` che mancava: il `{S_FORM_TOKEN}` nel template restava vuoto).

**Export CSV.** BOM UTF-8 e separatore `;` per l'apertura diretta in Excel europeo. BBCode rimosso, entità HTML decodificate. Le celle che iniziano con `=` `+` `-` `@` sono prefissate con un apice: senza questa precauzione un utente può scrivere in un MP una stringa che Excel esegue come formula quando l'amministratore apre il file. Scrittura in streaming a blocchi di 200 righe con keyset pagination, `set_time_limit(0)`.

**Stampa.** Documento HTML autonomo con intestazione (forum, operatore, data, conteggio), griglia e corpo renderizzato. CSS per la carta: `page-break-inside: avoid`, barra pulsanti nascosta in stampa, `window.print()` automatico. **Tetto di 500 messaggi**, dichiarato in pagina quando scatta.

---

## 5. Schermata "Impostazioni"

| Campo | Config | Note |
|---|---|---|
| Abilita cancellazione automatica | `pmread_auto_delete` | radio Sì/No |
| Elimina messaggi più vecchi di | `pmread_auto_delete_days` | 1–3650, default 90 |
| Gruppi esclusi | `pmread_exclude_groups` | CSV di group_id |
| Mostra avviso nel modulo MP | `pmread_pm_notice` | **attivo di default** |
| Anteprima dell'avviso | — | sola lettura |
| Messaggi attualmente eleggibili | — | `count_pms_older_than()` |
| Ultima esecuzione automatica | `pmread_auto_delete_last_gc` | sola lettura |
| Esegui cancellazione ora | — | vedi avvertenza |

> ⚠️ **"Esegui cancellazione ora" cancella TUTTI i MP**, non solo quelli oltre la soglia in giorni. La stringa lo dichiara, ma la posizione nel riquadro *Cancellazione automatica* resta ambigua.

---

## 6. Il servizio `pm_manager`

| Metodo | Descrizione |
|---|---|
| `get_excluded_group_ids()` / `get_excluded_user_ids()` | Gruppi protetti → user_id |
| `filter_deletable_msg_ids()` | Scarta i messaggi che coinvolgono utenti protetti, come autore o destinatario |
| `delete_pms_hard()` / `delete_all_pms()` / `delete_pms_older_than()` | Cancellazione fisica |
| `count_pms_older_than()` / `count_deletable_pms()` / `count_messages()` | Conteggi |
| `split_address()` | Parsa `u_2:g_5:u_9` (separatore due punti, virgola accettata per dati legacy) |
| `prime_names()` / `get_username()` / `format_address()` | Risoluzione nomi in 2 query per pagina |
| `resolve_user_ids()` | Nome utente / email → user_id |
| `build_filter_sql()` | Filtro → frammento WHERE |
| `fetch_messages()` / `fetch_messages_by_id()` / `fetch_export_batch()` | Lettura per lista, stampa, export |

**Sequenza di `delete_pms_hard()`**: filtro gruppi protetti → transazione → lettura contatori da `privmsgs_to` → `delete_notifications` → `attachment.manager->delete()` → DELETE su `privmsgs_to` e `privmsgs` → decremento contatori con `CASE WHEN … ELSE 0` (niente underflow) → commit.

`delete_all_pms()` itera con keyset pagination a batch di 250 e azzera i contatori globali **solo** se non resta alcun messaggio — corretto in presenza di gruppi protetti.

L'estensione non crea tabelle proprie.

---

## 7. Cron task

`cron_frequency` 3600 s, `max_per_run` 500 messaggi (≈12.000/giorno). Gira se `pmread_auto_delete` è attivo e `pmread_auto_delete_days > 0`. Aggiorna `last_gc`, imposta i config dell'avviso e logga `LOG_PMREAD_AUTO_DELETED`.

---

## 8. Migrazioni e configurazione

```
v314 → 1_0_0 → 1_1_0 → 1_2_0 → 1_2_1 → install_acp_module → 1_3_1 → 1_3_2 → 1_4_0 → 1_5_0
```

`release_1_4_0` aggiunge `pmread_pm_notice` (default 1) e porta la versione a 1.4.0. `release_1_5_0` fa solo il bump a 1.5.0: ricerca e stampa non richiedono schema né config.

| Chiave | Default | Dinamica |
|---|---|---|
| `pmread_version` | `1.5.0` | no |
| `pmread_auto_delete` | `0` | no |
| `pmread_auto_delete_days` | `90` | no |
| `pmread_auto_delete_last_gc` | `0` | sì |
| `pmread_exclude_groups` | `''` | no |
| `pmread_pm_notice` | `1` | no |
| `pmread_prune_notice_time` | `0` | sì |
| `pmread_prune_notice_days` | `0` | sì |
| `pmread_prune_notice_count` | `0` | sì — **scritta e mai letta** |

---

## 9. Avviso privacy nel modulo MP

Template event `posting_pm_header_find_username_after`, quindi l'avviso compare sotto il campo destinatari. Testo in italiano e inglese, modificabile nei file di lingua senza toccare il codice.

Riferimenti citati, tutti reali e correttamente caratterizzati:

| Riferimento | Contenuto |
|---|---|
| GDPR art. 6(1)(c) | Trattamento necessario per obbligo legale |
| GDPR art. 6(1)(f) | Trattamento per legittimo interesse |
| DSA (Reg. UE 2022/2065) artt. 9 e 10 | Ordini delle autorità di contrastare contenuti illegali e fornire informazioni |
| Cost. art. 15 | Segretezza della corrispondenza, limitabile solo per atto motivato dell'autorità giudiziaria |

Due precisazioni. Non sono un avvocato: il testo è un modello da far rileggere a chi cura l'informativa, che deve dire la stessa cosa. E l'avviso rende il trattamento *trasparente* (art. 13 GDPR) ma non lecito in qualsiasi forma: la lettura mirata su segnalazione o richiesta dell'autorità è difendibile, la consultazione di routine molto meno. Il testo descrive la prima.

Se lo stile non è prosilver e ha riscritto `posting_pm_header.html` rimuovendo i tag `<!-- EVENT -->`, l'avviso non compare (nessun errore). Alternativa: spostare il file in `event/posting_editor_subject_before.html` con `<!-- IF S_PRIVMSGS -->` attorno.

---

## 10. Correzioni applicate rispetto alla 1.3.2

| Difetto | Stato |
|---|---|
| `explode(',')` su `to_address` — phpBB usa i due punti, con più destinatari se ne vedeva solo uno | **Corretto** (1.4.0) |
| `bcc_address` mai letta: destinatari in Ccn invisibili | **Corretto**, colonna dedicata in lista, export e stampa (1.4.0) |
| N+1 query sui nomi utente (~30 per pagina) | **Corretto**, 2 query per pagina (1.4.0) |
| Smoke test asseriva versione `1.3.0` e falliva | **Corretto**, ora verifica il formato semver (1.4.0) |
| `{S_FORM_TOKEN}` vuoto: mancava `add_form_key` | **Corretto** (1.4.0) |
| `composer.json` dichiarava phpBB ≥3.1 ma serve `attachment.manager` (3.2+) | **Corretto** (1.4.0) |
| Link *Trova un utente* puntava al form sbagliato | **Corretto** (1.5.0) |

**Rimasti aperti**, deliberatamente:

- `pmread_prune_notice_count` scritta e mai letta (codice morto).
- L'elenco mostra e fa selezionare anche i messaggi di utenti protetti, che poi vengono scartati in silenzio: l'admin legge "0 eliminati" senza spiegazione.
- Paginazione fissa a 15 messaggi.
- `main_module` usa le globali invece della DI — pattern legacy dei moduli ACP, funzionante ma non testabile in isolamento.
- Wrapper legacy `pmread_info.php` / `pmread_module.php` ormai inerti.
- I messaggi cancellati dall'utente prima della lettura non sono recuperabili: phpBB elimina fisicamente la riga da `privmsgs`. Servirebbe un archivio popolato su `core.submit_pm_after`, scartato per le implicazioni privacy.

---

## 11. Installazione e aggiornamento

1. **Backup del database.** Tutte le cancellazioni sono fisiche e irreversibili: nessun soft-delete, nessun cestino.
2. ACP → *Gestisci estensioni* → **Disabilita** PM Read. Non usare "Elimina i dati": cancellerebbe le impostazioni.
3. Sostituire il contenuto di `ext/phpbbworld/pmread/`.
4. **Abilitare**: le migrazioni pendenti girano in sequenza.
5. Svuotare la cache di phpBB.

**Prima configurazione consigliata:** inserire *Amministratori* e *Moderatori globali* fra i gruppi esclusi prima di attivare la cancellazione automatica, e controllare *Messaggi attualmente eleggibili* per conoscere la dimensione della prima pulizia.

Test offline: `php tests/smoke_test.php` dalla cartella dell'estensione.

---

## 12. Prestazioni

La ricerca per parola chiave usa `LIKE '%…%'` su `message_text`, non indicizzabile: su archivi grandi è una scansione completa. Restringere prima per data o utente, che sono filtri indicizzabili. Verificare che esista un indice su `message_time`.
