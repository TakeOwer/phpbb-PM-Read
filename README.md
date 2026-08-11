# phpbb-PM-Read
This extension allows board administrators to view users' private messages directly from the Admin Control Panel (ACP). Originally created by DeaDRoMeO, this version has been updated, enhanced, and maintained to ensure compatibility and improved functionality
PMRead — Documentazione tecnica

Versione: 1.3.2 · Package: phpbbworld/pmread · Namespace: phpbbworld\pmread Autore: DeaDRoMeO (phpbbworld.ru) · Licenza: GPL-2.0 Homepage: http://phpbbworld.ru/viewtopic.php?f=25&t=81

1. Cosa fa

Estensione per phpBB che aggiunge nell'ACP (Area di Controllo Amministratore) due funzioni:

Lettura dei messaggi privati di tutti gli utenti del forum, con elenco paginato, mittente, destinatario, data, oggetto e corpo del messaggio renderizzato.
Cancellazione dei messaggi privati, sia manuale (selettiva o totale) sia automatica tramite cron in base all'età dei messaggi, con possibilità di proteggere interi gruppi utenti.

Quando avviene una cancellazione, agli utenti registrati viene mostrato una sola volta un avviso phpbb.alert nel front-end.

⚠️ Nota su privacy e conformità. La funzione principale è l'accesso in chiaro a corrispondenza privata tra utenti. In contesti UE questo ha implicazioni GDPR concrete: serve una base giuridica, l'informativa del forum dovrebbe dichiarare esplicitamente che lo staff può leggere i MP, e l'accesso andrebbe limitato al minor numero possibile di amministratori. La cancellazione automatica, al contrario, gioca a favore (minimizzazione e limitazione della conservazione). Vedi anche §10.

2. Requisiti
Requisito	Dichiarato	Effettivo
PHP	>= 5.3.3	ok, ma il codice usa sintassi compatibile 5.3+
phpBB	>=3.1.0,<4.0.0@dev (soft-require)	di fatto 3.2+ — vedi nota

Nota sulla versione minima reale: service/pm_manager.php richiama dal container il servizio attachment.manager, introdotto in phpBB 3.2. Su phpBB 3.1 la cancellazione dei MP con allegati solleverebbe un'eccezione di servizio inesistente. Il vincolo >=3.1.0 in composer.json è quindi ottimistico e andrebbe alzato a >=3.2.0.

3. Struttura dei file
pmread/
├── composer.json                     # metadati estensione, version-check remoto
├── ext.php                           # classe di attivazione (vuota, default)
├── config/
│   └── services.yml                  # DI: listener, pm_manager, cron task
├── acp/
│   ├── main_info.php                 # dichiarazione modulo + modes + permessi
│   ├── main_module.php               # controller ACP (messaggi + impostazioni)
│   ├── pmread_info.php               # wrapper legacy → main_info
│   └── pmread_module.php             # wrapper legacy → main_module
├── adm/style/
│   ├── acp_pmread.html               # template elenco messaggi
│   └── acp_pmread_settings.html      # template impostazioni
├── service/
│   └── pm_manager.php                # logica di cancellazione condivisa
├── cron/task/
│   └── prune_pms.php                 # task cron di pulizia automatica
├── event/
│   └── listener.php                  # lingua + avviso utente post-pulizia
├── styles/all/template/event/
│   └── overall_footer_after.html     # iniezione JS dell'alert front-end
├── migrations/                       # 7 migrazioni (vedi §8)
├── language/{en,it}/
│   ├── info_acp_pmread.php           # voci di menu ACP
│   └── pmread.php                    # stringhe complete (46 chiavi, EN/IT allineate)
└── tests/
    └── smoke_test.php                # test offline senza bootstrap phpBB
4. Architettura
4.1 Servizi registrati (config/services.yml)
Servizio	Classe	Dipendenze
phpbbworld.pmread.listener	event\listener	config, template, user, request
phpbbworld.pmread.pm_manager	service\pm_manager	dbal.conn, config, service_container
phpbbworld.pmread.cron.task.prune_pms	cron\task\prune_pms	config, pm_manager, log

Il cron task è registrato con il tag cron.task e il nome phpbbworld.pmread.cron.task.prune_pms.

4.2 Event listener

Sottoscrive due eventi core:

Evento	Metodo	Scopo
core.user_setup	load_language_on_setup()	Carica il file lingua pmread
core.page_header_after	prepare_prune_notice()	Prepara l'avviso post-cancellazione

Logica dell'avviso. Viene mostrato solo a utenti registrati non-bot. Confronta il cookie {cookie_name}_pmreadn con il valore di pmread_prune_notice_time: se differiscono, mostra il messaggio e riscrive il cookie (durata 1 anno). Così ogni "run" di pulizia genera al massimo un avviso per utente. Il testo cambia in base a pmread_prune_notice_days: se > 0 usa PMREAD_PRUNE_USER_NOTICE (con i giorni), se 0 usa PMREAD_PRUNE_USER_NOTICE_ALL.

Il template overall_footer_after.html stampa un <div hidden> con gli attributi data-title / data-message e uno script che chiama phpbb.alert() al DOMContentLoaded.

5. Interfaccia ACP

Il modulo si installa sotto ACP_CAT_DOT_MODS → PM Read, con due modes. Permessi richiesti per entrambe: ext_phpbbworld/pmread && acl_a_board.

5.1 Mode messages — Visualizza i messaggi

Template acp_pmread.html. Elenco paginato (15 per pagina, valore hardcoded) ordinato per msg_id DESC.

Per ogni riga: checkbox, ID, mittente, destinatario, data formattata, oggetto e — nella riga sottostante — il corpo del messaggio in un box scrollabile alto 60px, renderizzato con generate_text_for_display() rispettando BBCode, smilies e magic URL.

Azioni disponibili:

Cancella selezionati (delmarked) → pm_manager::delete_pms_hard($marked)
Cancella Tutti i Messaggi (delall) → pm_manager::delete_all_pms()
Link JS Seleziona / Deseleziona tutti (funzione marklist ridefinita localmente nel template)

Entrambe passano da confirm_box(), sono protette da {S_FORM_TOKEN} e scrivono nel log admin (LOG_PMREAD_DELETED / LOG_PMREAD_DELETED_ALL).

Risoluzione dei nomi. Il mittente si ricava da author_id; i destinatari dal campo to_address, parsando i token u_<id> separati da virgola. Ogni nome è una query separata su USERS_TABLE con cache 600 s → pattern N+1 (fino a ~30 query per pagina, mitigate ma non eliminate dalla cache).

5.2 Mode settings — Impostazioni

Template acp_pmread_settings.html. Campi:

Campo	Config	Note
Abilita cancellazione automatica	pmread_auto_delete	radio Sì/No
Elimina messaggi più vecchi di	pmread_auto_delete_days	number, min 1 / max 3650, default 90
Gruppi esclusi dalla cancellazione	pmread_exclude_groups	multi-select, CSV di group_id
Messaggi attualmente eleggibili	(sola lettura)	count_pms_older_than($days)
Ultima esecuzione automatica	(sola lettura)	pmread_auto_delete_last_gc formattata
Esegui cancellazione ora	(pulsante)	run_prune

La select dei gruppi è popolata da GROUPS_TABLE ordinata per group_type DESC, group_name ASC, con traduzione dei gruppi speciali via chiavi G_*.

Il salvataggio è protetto da check_form_key('acp_pmread_settings'); days < 1 genera errore PMREAD_DAYS_INVALID. Il salvataggio riuscito logga LOG_PMREAD_SETTINGS_UPDATED.

⚠️ Attenzione al pulsante "Esegui cancellazione ora". Nonostante sia collocato nel fieldset Cancellazione automatica, non applica la soglia in giorni: chiama delete_all_pms() e cancella tutti i MP del forum (esclusi i gruppi protetti). La stringa PMREAD_RUN_PRUNE_EXPLAIN lo dichiara, ma la posizione nell'interfaccia è ambigua.

6. Il servizio pm_manager

Cuore della logica di cancellazione. Metodi pubblici:

Metodo	Descrizione
get_excluded_group_ids()	Parsa il CSV pmread_exclude_groups in array di int
get_excluded_user_ids()	Espande i gruppi in user_id (user_pending = 0), con cache di istanza
filter_deletable_msg_ids(array)	Rimuove i msg_id che coinvolgono utenti protetti, come autore o destinatario
delete_pms_hard(array $msg_ids)	Cancellazione fisica; ritorna il numero di righe eliminate
delete_all_pms()	Cancella tutto a batch di 250; ritorna il totale
delete_pms_older_than($days, $max_per_run = 500)	Cancellazione per età, a batch, con tetto per esecuzione
count_pms_older_than($days)	Conteggio eleggibili oltre soglia, esclusi i protetti
count_deletable_pms()	Conteggio totale eleggibili, esclusi i protetti
6.1 Sequenza di delete_pms_hard()
Filtro dei gruppi protetti.
sql_transaction('begin').
Lettura da PRIVMSGS_TO_TABLE per accumulare, per utente, i contatori pm_unread e pm_new e, per cartella, il numero di messaggi (escluse le cartelle speciali INBOX, OUTBOX, SENTBOX, NO_BOX).
notification_manager->delete_notifications('notification.type.pm', $msg_ids).
attachment.manager->delete('message', $msg_ids, false).
DELETE da PRIVMSGS_TO_TABLE, poi da PRIVMSGS_TABLE; il conteggio viene da sql_affectedrows().
Decremento di user_unread_privmsg / user_new_privmsg con clausola CASE WHEN … ELSE 0 END (nessun underflow).
Decremento di pm_count in PRIVMSGS_FOLDER_TABLE, stessa protezione.
sql_transaction('commit').

delete_all_pms() itera con keyset pagination (msg_id > $last_id) e, solo se non resta alcun messaggio, azzera in blocco user_new_privmsg, user_unread_privmsg e pm_count. La condizione è corretta: se ci sono gruppi protetti, i contatori globali non vengono toccati.

Le query usano cast a int e sql_in_set() del DBAL — nessuna concatenazione di input non sanificato.

6.2 Tabelle coinvolte

PRIVMSGS_TABLE, PRIVMSGS_TO_TABLE, PRIVMSGS_FOLDER_TABLE, USERS_TABLE, USER_GROUP_TABLE, GROUPS_TABLE, più le tabelle gestite indirettamente da notification manager e attachment manager. L'estensione non crea tabelle proprie.

7. Cron task

cron\task\prune_pms extends \phpbb\cron\task\base

Parametro	Valore
cron_frequency	3600 s (1 ora)
max_per_run	500 messaggi
is_runnable() → pmread_auto_delete attivo e pmread_auto_delete_days > 0
should_run() → pmread_auto_delete_last_gc < time() - 3600
run() → chiama delete_pms_older_than(), aggiorna last_gc, e se ha cancellato qualcosa imposta i tre config dell'avviso e logga LOG_PMREAD_AUTO_DELETED (user_id ANONYMOUS, IP 127.0.0.1).

Con questi valori il ritmo massimo è 500 messaggi/ora, cioè ~12.000/giorno — da tenere presente sul primo passaggio in un forum con archivio storico ampio.

8. Migrazioni

Catena delle dipendenze:

\phpbb\db\migration\data\v31x\v314
  └─ release_1_0_0
       └─ release_1_1_0
            └─ release_1_2_0
                 └─ release_1_2_1
                      └─ install_acp_module
                           └─ release_1_3_1
                                └─ release_1_3_2
Migrazione	Effetti
release_1_0_0	Aggiunge pmread_version; installa categoria ACP_PMREAD e modulo con modes messages/settings
release_1_1_0	Solo bump di versione a 1.1.0
release_1_2_0	Aggiunge pmread_auto_delete, pmread_auto_delete_days (90), pmread_auto_delete_last_gc (dinamico)
release_1_2_1	custom: forza module_enabled = 1 e module_display = 1 sulle righe modulo legacy (PMR, PMR_CONFIG, PMR_SETTINGS) e nuove
install_acp_module	Rimuove tutte le vecchie righe modulo e le reinstalla in modo pulito; versione → 1.3.0
release_1_3_1	Aggiunge i tre config dinamici dell'avviso
release_1_3_2	Aggiunge pmread_exclude_groups; versione → 1.3.2

release_1_1_0 e release_1_2_1 non definiscono revert_data(), quindi non sono reversibili in modo completo.

9. Chiavi di configurazione
Chiave	Default	Dinamica	Uso
pmread_version	1.3.2	no	Mostrata nei titoli ACP
pmread_auto_delete	0	no	Abilita il cron
pmread_auto_delete_days	90	no	Soglia di età
pmread_auto_delete_last_gc	0	sì	Timestamp ultima esecuzione
pmread_exclude_groups	''	no	CSV di group_id protetti
pmread_prune_notice_time	0	sì	Identificatore del "run" per il cookie
pmread_prune_notice_days	0	sì	0 = cancellazione totale, >0 = per età
pmread_prune_notice_count	0	sì	Scritta ma mai letta (vedi §10)
10. Osservazioni e criticità

Rilevate leggendo il codice; non sono necessariamente bug bloccanti, ma vale la pena conoscerle.

Il test di fumo fallisce. tests/smoke_test.php asserisce $composer['version'] === '1.3.0', mentre composer.json dichiara 1.3.2. Eseguendo php tests/smoke_test.php si ottiene 1 assertion fallita ed exit code 1. L'asserzione andrebbe resa dinamica o allineata.
pmread_prune_notice_count non viene mai usata. Il numero di messaggi cancellati viene salvato da ACP e cron ma il listener non lo legge e nessuna stringa lingua lo riceve come parametro. Codice morto o funzione incompiuta.
Vincolo phpBB troppo permissivo. attachment.manager è 3.2+ (vedi §2).
main_module usa le globali (global $request, $config, $phpbb_container, …) invece della dependency injection. Funziona — è il pattern legacy dei moduli ACP — ma rende la classe non testabile in isolamento ed è fuori standard rispetto al resto dell'estensione, che usa correttamente il container.
L'elenco messaggi ignora i gruppi protetti. La lista mostra e permette di selezionare anche MP di utenti protetti; delete_pms_hard() li scarta silenziosamente. L'amministratore vede quindi "0 messaggi eliminati" senza alcuna spiegazione. Un'indicazione visiva (riga disabilitata o badge "protetto") migliorerebbe molto la UX.
N+1 sui nomi utente nell'elenco messaggi (§5.1). Una JOIN o un prefetch in blocco degli user_id della pagina risolverebbe.
Paginazione hardcoded a 15. Non configurabile.
Il pulsante "Esegui cancellazione ora" cancella tutto, non solo i messaggi scaduti (§5.2).
L'avviso front-end è indiscriminato: viene mostrato a tutti gli utenti registrati, compresi quelli che non avevano alcun MP coinvolto.
Il cookie dell'avviso ha durata 1 anno ma viene sovrascritto a ogni nuovo run, quindi non c'è accumulo — comportamento corretto.
Wrapper legacy pmread_info.php / pmread_module.php restano nel pacchetto per compatibilità con righe modulo vecchie; dopo install_acp_module sono di fatto inerti e potrebbero essere rimossi in una futura major.
File lingua: EN e IT hanno esattamente le stesse 46 chiavi, nessuna mancante da entrambi i lati. Le stringhe plurali (MESSAGES_DELETED, PMREAD_PRUNE_DONE, ecc.) usano correttamente il formato array di phpBB.
11. Installazione e aggiornamento

Installazione

Copiare la cartella in ext/phpbbworld/pmread/ (il percorso deve corrispondere al namespace).
ACP → Personalizza → Gestisci estensioni → Abilita PM Read.
Verificare che il cron di phpBB sia attivo (system cron consigliato) se si intende usare la cancellazione automatica.

Aggiornamento

Disabilitare l'estensione (non disinstallarla: la disinstallazione esegue i revert_data e rimuove le config).
Sostituire i file.
Riabilitare — le migrazioni pendenti girano automaticamente.
Svuotare la cache di phpBB.

Prima configurazione consigliata

Aggiungere i gruppi Amministratori e Moderatori globali tra i gruppi esclusi, prima di attivare la cancellazione automatica.
Controllare il valore Messaggi attualmente eleggibili prima di abilitare il cron: dà la dimensione esatta della prima pulizia.
Fare un backup del database prima della prima esecuzione. Tutte le cancellazioni sono fisiche e irreversibili: non c'è soft-delete né cestino.
