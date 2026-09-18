<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_PMREAD'					=> 'PM Read',
	'ACP_PMREAD_MESSAGES'			=> 'Visualizza i messaggi',
	'ACP_PMREAD_SETTINGS'			=> 'Impostazioni',
	'ACP_PMREAD_SETTINGS_EXPLAIN'	=> 'Configura la cancellazione automatica dei messaggi personali in base all’età dei messaggi. Il cron di phpBB esegue l’operazione senza conferma.',

	'PMREAD_MSG_ID'					=> 'Messaggio ID',
	'PMREAD_FROM'						=> 'Mittente',
	'PMREAD_TO'						=> 'Destinatario',
	'PMREAD_DATETIME'					=> 'Data messaggio',
	'PMREAD_SUBJECT'					=> 'Titolo messaggio',
	'PMREAD_NO_MESSAGES'				=> 'Nessun messaggio',
	'PMREAD_EXPLAIN'					=> 'Qui puoi visualizzare tutti i messaggi personali degli utenti del tuo forum. Puoi selezionare e cancellare i messaggi; l’eliminazione è irreversibile e rimuove i dati dal database.',
	'PMREAD_TOTAL_ITEMS'				=> 'Totale messaggi: <strong>%d</strong>',

	'PMREAD_DELETE_MARKED'				=> 'Cancella selezionati',
	'PMREAD_DELETE_ALL'				=> 'Cancella Tutti i Messaggi',
	'PMREAD_MARK_ALL'					=> 'Seleziona tutti',
	'PMREAD_UNMARK_ALL'				=> 'Deseleziona tutti',
	'PMREAD_NO_MESSAGES_SELECTED'		=> 'Nessun messaggio selezionato.',
	'PMREAD_CONFIRM_DELETE_MARKED'		=> 'Sei sicuro di voler eliminare definitivamente i messaggi selezionati dal database?',
	'PMREAD_CONFIRM_DELETE_ALL'		=> 'Sei sicuro di voler eliminare DEFINITIVAMENTE TUTTI i messaggi personali dal database? Questa operazione non può essere annullata.',
	'PMREAD_MESSAGES_DELETED'			=> array(
		1	=> '%d messaggio eliminato dal database.',
		2	=> '%d messaggi eliminati dal database.',
	),
	'PMREAD_MESSAGES_DELETED_ALL'		=> array(
		1	=> 'Tutti i messaggi sono stati eliminati (%d messaggio).',
		2	=> 'Tutti i messaggi sono stati eliminati (%d messaggi).',
	),

	'PMREAD_AUTO_DELETE_OPTIONS'		=> 'Cancellazione automatica',
	'PMREAD_AUTO_DELETE'				=> 'Abilita cancellazione automatica',
	'PMREAD_AUTO_DELETE_EXPLAIN'		=> 'Se impostato su Sì, i messaggi personali più vecchi del numero di giorni indicato verranno eliminati automaticamente dal database tramite il cron di phpBB.',
	'PMREAD_AUTO_DELETE_DAYS'			=> 'Elimina messaggi più vecchi di',
	'PMREAD_AUTO_DELETE_DAYS_EXPLAIN'	=> 'I messaggi con data anteriore a questo numero di giorni verranno cancellati automaticamente. Valore minimo: 1.',
	'PMREAD_EXCLUDE_GROUPS'				=> 'Gruppi esclusi dalla cancellazione',
	'PMREAD_EXCLUDE_GROUPS_EXPLAIN'		=> 'I messaggi privati di utenti appartenenti ai gruppi selezionati non verranno cancellati (né dal cron, né dalle cancellazioni totali). Vale sia per mittente sia per destinatario. Tieni premuto Ctrl (o Cmd) per selezionare più gruppi.',
	'PMREAD_LAST_AUTO_DELETE'			=> 'Ultima esecuzione automatica',
	'PMREAD_PENDING_DELETE'				=> 'Messaggi attualmente eleggibili',
	'PMREAD_PENDING_DELETE_EXPLAIN'		=> 'Numero di messaggi più vecchi dei giorni configurati (esclusi i gruppi protetti) che verrebbero eliminati al prossimo passaggio del cron.',
	'PMREAD_NEVER'						=> 'Mai',
	'PMREAD_DAYS_INVALID'				=> 'Il numero di giorni deve essere almeno 1.',
	'PMREAD_SETTINGS_SAVED'				=> 'Impostazioni di PM Read salvate correttamente.',
	'PMREAD_RUN_PRUNE'					=> 'Esegui cancellazione ora',
	'PMREAD_RUN_PRUNE_EXPLAIN'			=> 'Cancella subito TUTTI i messaggi personali dal database, indipendentemente dai giorni di scadenza. Operazione irreversibile.',
	'PMREAD_RUN_PRUNE_BUTTON'			=> 'Cancella tutti i messaggi ora',
	'PMREAD_PRUNE_CONFIRM'				=> 'Confermi la cancellazione definitiva di TUTTI i messaggi personali (%d)? Questa operazione non può essere annullata.',
	'PMREAD_PRUNE_NONE'					=> 'Nessun messaggio da cancellare.',
	'PMREAD_PRUNE_DONE'					=> array(
		1	=> 'Cancellazione completata: eliminato %d messaggio dal database.',
		2	=> 'Cancellazione completata: eliminati %d messaggi dal database.',
	),
	'PMREAD_PRUNE_USER_NOTICE'			=> 'I messaggi privati (i tuoi e quelli degli altri utenti) più vecchi di %d giorni sono stati cancellati automaticamente dal sistema.',
	'PMREAD_PRUNE_USER_NOTICE_ALL'		=> 'Tutti i messaggi privati (i tuoi e quelli degli altri utenti) sono stati cancellati dal sistema.',

	'LOG_PMREAD_DELETED'			=> '<strong>PM Read:</strong> eliminati %1$s messaggi personali selezionati',
	'LOG_PMREAD_DELETED_ALL'		=> '<strong>PM Read:</strong> eliminati tutti i messaggi personali (%1$s)',
	'LOG_PMREAD_AUTO_DELETED'		=> '<strong>PM Read:</strong> cancellazione automatica: eliminati %1$s messaggi più vecchi di %2$s giorni',
	'LOG_PMREAD_SETTINGS_UPDATED'	=> '<strong>PM Read:</strong> impostazioni aggiornate',

	'PMREAD_EXPORT_MARKED'				=> 'Esporta selezionati (CSV)',
	'PMREAD_EXPORT_ALL'					=> 'Esporta tutti (CSV)',
	'PMREAD_EXPORT_COL_BCC'				=> 'Copia nascosta (Ccn)',
	'PMREAD_EXPORT_COL_TEXT'			=> 'Testo messaggio',

	'PMREAD_NOTICE_OPTIONS'				=> 'Avviso agli utenti',
	'PMREAD_PM_NOTICE_ENABLE'			=> 'Mostra avviso nel modulo di invio MP',
	'PMREAD_PM_NOTICE_ENABLE_EXPLAIN'	=> 'Se impostato su Sì, nel modulo di composizione dei messaggi privati viene mostrato un avviso che informa l’utente che i messaggi possono essere consultati dagli amministratori. Il testo dell’avviso è modificabile nei file di lingua dell’estensione (language/it/pmread.php e language/en/pmread.php).',
	'PMREAD_PM_NOTICE_PREVIEW'			=> 'Anteprima dell’avviso',
	'PMREAD_PM_NOTICE_PREVIEW_EXPLAIN'	=> 'Questo è il testo che gli utenti vedranno sopra il campo dell’oggetto.',

	'PMREAD_PM_NOTICE_TITLE'			=> 'Avviso sulla riservatezza dei messaggi privati',
	'PMREAD_PM_NOTICE'					=> 'I messaggi privati inviati tramite questo forum sono conservati sui nostri server e possono essere consultati dagli amministratori in caso di contenzioso, di segnalazione di abuso o di disputa fra utenti, nonché su richiesta dell’autorità giudiziaria o di altra autorità competente. Il trattamento avviene ai sensi dell’art. 6, par. 1, lett. c) e lett. f) del Regolamento (UE) 2016/679 (GDPR) e, per gli ordini provenienti dalle autorità degli Stati membri, degli artt. 9 e 10 del Regolamento (UE) 2022/2065 (Digital Services Act). La segretezza della corrispondenza è tutelata dall’art. 15 della Costituzione italiana e può essere limitata soltanto per atto motivato dell’autorità giudiziaria. Per i dettagli consulta l’informativa privacy del forum.',

	'PMREAD_SEARCH'						=> 'Ricerca messaggi',
	'PMREAD_SEARCH_USER'				=> 'Nome utente',
	'PMREAD_SEARCH_USER_EXPLAIN'		=> 'Cerca i messaggi legati a un utente. La ricerca è parziale: “mar” trova anche “martina33”. Puoi usare * come carattere jolly.',
	'PMREAD_FIND_USER'					=> 'Trova un utente',
	'PMREAD_SEARCH_SCOPE'				=> 'Cerca l’utente come',
	'PMREAD_SCOPE_ANY'					=> 'Mittente o destinatario',
	'PMREAD_SCOPE_FROM'					=> 'Solo mittente',
	'PMREAD_SCOPE_TO'					=> 'Solo destinatario',
	'PMREAD_SEARCH_EMAIL'				=> 'Email',
	'PMREAD_SEARCH_EMAIL_EXPLAIN'		=> 'Indirizzo email dell’utente, anche parziale. Si combina con il nome utente: se compili entrambi i campi, l’utente deve corrispondere a tutti e due.',
	'PMREAD_SEARCH_KEYWORD'				=> 'Parola chiave',
	'PMREAD_SEARCH_KEYWORD_EXPLAIN'		=> 'Cerca nell’oggetto e nel testo dei messaggi.',
	'PMREAD_SEARCH_DATE'				=> 'Intervallo di date',
	'PMREAD_SEARCH_DATE_EXPLAIN'		=> 'Per un singolo giorno imposta la stessa data in entrambi i campi. Puoi compilarne anche uno solo.',
	'PMREAD_DATE_FROM'					=> 'Dal',
	'PMREAD_DATE_TO'					=> 'Al',
	'PMREAD_SEARCH_YEAR'				=> 'Anno',
	'PMREAD_SEARCH_YEAR_EXPLAIN'		=> 'Scorciatoia per l’intero anno (1 gennaio – 31 dicembre). Viene ignorato se hai compilato le date qui sopra.',
	'PMREAD_SEARCH_SUBMIT'				=> 'Cerca',
	'PMREAD_SEARCH_RESET'				=> 'Azzera filtri',
	'PMREAD_FILTER_ON'					=> 'Filtro attivo: l’elenco e il pulsante “Esporta tutti” si riferiscono solo ai risultati della ricerca.',

	'PMREAD_PRINT_MARKED'				=> 'Stampa selezionati',
	'PMREAD_PRINT_TITLE'				=> 'Stampa messaggi personali',
	'PMREAD_PRINT_META'					=> 'Stampato da %1$s il %2$s – %3$d messaggi.',
	'PMREAD_PRINT_NOW'					=> 'Stampa',
	'PMREAD_PRINT_CLOSE'				=> 'Chiudi',
	'PMREAD_PRINT_ALL'					=> 'Stampa risultati',
	'PMREAD_PRINT_TRUNCATED'			=> 'Stampa limitata ai primi %1$s messaggi. Restringi la ricerca per stamparne altri.',
));
