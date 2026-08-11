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

	'MSG_ID'					=> 'Messaggio ID',
	'FROM'						=> 'Mittente',
	'TO'						=> 'Destinatario',
	'DATETIME'					=> 'Data messaggio',
	'SUBJECT'					=> 'Titolo messaggio',
	'NO_MESSAGES'				=> 'Nessun messaggio',
	'EXPLAIN'					=> 'Qui puoi visualizzare tutti i messaggi personali degli utenti del tuo forum. Puoi selezionare e cancellare i messaggi; l’eliminazione è irreversibile e rimuove i dati dal database.',
	'TOTAL_ITEMS'				=> 'Totale messaggi: <strong>%d</strong>',

	'DELETE_MARKED'				=> 'Cancella selezionati',
	'DELETE_ALL'				=> 'Cancella Tutti i Messaggi',
	'MARK_ALL'					=> 'Seleziona tutti',
	'UNMARK_ALL'				=> 'Deseleziona tutti',
	'NO_MESSAGES_SELECTED'		=> 'Nessun messaggio selezionato.',
	'CONFIRM_DELETE_MARKED'		=> 'Sei sicuro di voler eliminare definitivamente i messaggi selezionati dal database?',
	'CONFIRM_DELETE_ALL'		=> 'Sei sicuro di voler eliminare DEFINITIVAMENTE TUTTI i messaggi personali dal database? Questa operazione non può essere annullata.',
	'MESSAGES_DELETED'			=> array(
		1	=> '%d messaggio eliminato dal database.',
		2	=> '%d messaggi eliminati dal database.',
	),
	'MESSAGES_DELETED_ALL'		=> array(
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
));
