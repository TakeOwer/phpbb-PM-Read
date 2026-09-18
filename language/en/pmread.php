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
	'ACP_PMREAD_MESSAGES'			=> 'Show messages',
	'ACP_PMREAD_SETTINGS'			=> 'Settings',
	'ACP_PMREAD_SETTINGS_EXPLAIN'	=> 'Configure automatic deletion of private messages by age. phpBB’s cron runs this without confirmation.',

	'PMREAD_MSG_ID'					=> 'Message ID',
	'PMREAD_FROM'						=> 'From',
	'PMREAD_TO'						=> 'To',
	'PMREAD_DATETIME'					=> 'Date',
	'PMREAD_SUBJECT'					=> 'Title',
	'PMREAD_NO_MESSAGES'				=> 'No private message',
	'PMREAD_EXPLAIN'					=> 'Here you can view all personal messages from users of your forum. You can select and delete messages; deletion is irreversible and removes data from the database.',
	'PMREAD_TOTAL_ITEMS'				=> 'Total messages: <strong>%d</strong>',

	'PMREAD_DELETE_MARKED'				=> 'Delete selected',
	'PMREAD_DELETE_ALL'				=> 'Delete All Messages',
	'PMREAD_MARK_ALL'					=> 'Select all',
	'PMREAD_UNMARK_ALL'				=> 'Deselect all',
	'PMREAD_NO_MESSAGES_SELECTED'		=> 'No messages selected.',
	'PMREAD_CONFIRM_DELETE_MARKED'		=> 'Are you sure you want to permanently delete the selected messages from the database?',
	'PMREAD_CONFIRM_DELETE_ALL'		=> 'Are you sure you want to permanently delete ALL private messages from the database? This cannot be undone.',
	'PMREAD_MESSAGES_DELETED'			=> array(
		1	=> '%d message deleted from the database.',
		2	=> '%d messages deleted from the database.',
	),
	'PMREAD_MESSAGES_DELETED_ALL'		=> array(
		1	=> 'All messages have been deleted (%d message).',
		2	=> 'All messages have been deleted (%d messages).',
	),

	'PMREAD_AUTO_DELETE_OPTIONS'		=> 'Automatic deletion',
	'PMREAD_AUTO_DELETE'				=> 'Enable automatic deletion',
	'PMREAD_AUTO_DELETE_EXPLAIN'		=> 'If set to Yes, private messages older than the configured number of days are permanently removed from the database by phpBB’s cron.',
	'PMREAD_AUTO_DELETE_DAYS'			=> 'Delete messages older than',
	'PMREAD_AUTO_DELETE_DAYS_EXPLAIN'	=> 'Messages with a date older than this many days will be deleted automatically. Minimum value: 1.',
	'PMREAD_EXCLUDE_GROUPS'				=> 'Groups excluded from deletion',
	'PMREAD_EXCLUDE_GROUPS_EXPLAIN'		=> 'Private messages of users in the selected groups will not be deleted (neither by cron nor by wipe-all actions). Applies to both sender and recipient. Hold Ctrl (or Cmd) to select multiple groups.',
	'PMREAD_LAST_AUTO_DELETE'			=> 'Last automatic run',
	'PMREAD_PENDING_DELETE'				=> 'Messages currently eligible',
	'PMREAD_PENDING_DELETE_EXPLAIN'		=> 'Number of messages older than the configured days (excluding protected groups) that would be deleted on the next cron run.',
	'PMREAD_NEVER'						=> 'Never',
	'PMREAD_DAYS_INVALID'				=> 'The number of days must be at least 1.',
	'PMREAD_SETTINGS_SAVED'				=> 'PM Read settings saved successfully.',
	'PMREAD_RUN_PRUNE'					=> 'Run deletion now',
	'PMREAD_RUN_PRUNE_EXPLAIN'			=> 'Immediately delete ALL private messages from the database, regardless of the expiry days. This cannot be undone.',
	'PMREAD_RUN_PRUNE_BUTTON'			=> 'Delete all messages now',
	'PMREAD_PRUNE_CONFIRM'				=> 'Are you sure you want to permanently delete ALL private messages (%d)? This cannot be undone.',
	'PMREAD_PRUNE_NONE'					=> 'No messages to delete.',
	'PMREAD_PRUNE_DONE'					=> array(
		1	=> 'Deletion complete: removed %d message from the database.',
		2	=> 'Deletion complete: removed %d messages from the database.',
	),
	'PMREAD_PRUNE_USER_NOTICE'			=> 'Private messages (yours and those of other users) older than %d days have been automatically deleted by the system.',
	'PMREAD_PRUNE_USER_NOTICE_ALL'		=> 'All private messages (yours and those of other users) have been deleted by the system.',

	'LOG_PMREAD_DELETED'			=> '<strong>PM Read:</strong> deleted %1$s selected private messages',
	'LOG_PMREAD_DELETED_ALL'		=> '<strong>PM Read:</strong> deleted all private messages (%1$s)',
	'LOG_PMREAD_AUTO_DELETED'		=> '<strong>PM Read:</strong> auto-delete: removed %1$s messages older than %2$s days',
	'LOG_PMREAD_SETTINGS_UPDATED'	=> '<strong>PM Read:</strong> settings updated',

	'PMREAD_EXPORT_MARKED'				=> 'Export selected (CSV)',
	'PMREAD_EXPORT_ALL'					=> 'Export all (CSV)',
	'PMREAD_EXPORT_COL_BCC'				=> 'Blind copy (BCC)',
	'PMREAD_EXPORT_COL_TEXT'			=> 'Message text',

	'PMREAD_NOTICE_OPTIONS'				=> 'Notice to users',
	'PMREAD_PM_NOTICE_ENABLE'			=> 'Show notice in the PM compose form',
	'PMREAD_PM_NOTICE_ENABLE_EXPLAIN'	=> 'When set to Yes, a notice is displayed in the private message compose form telling users that their messages can be read by administrators. The wording can be changed in the extension language files (language/en/pmread.php and language/it/pmread.php).',
	'PMREAD_PM_NOTICE_PREVIEW'			=> 'Notice preview',
	'PMREAD_PM_NOTICE_PREVIEW_EXPLAIN'	=> 'This is the text users will see above the subject field.',

	'PMREAD_PM_NOTICE_TITLE'			=> 'Notice on the confidentiality of private messages',
	'PMREAD_PM_NOTICE'					=> 'Private messages sent through this board are stored on our servers and may be accessed by the administrators in the event of a dispute, an abuse report or a disagreement between users, as well as at the request of a judicial or other competent authority. Such processing is carried out under Article 6(1)(c) and Article 6(1)(f) of Regulation (EU) 2016/679 (GDPR) and, for orders issued by Member State authorities, under Articles 9 and 10 of Regulation (EU) 2022/2065 (Digital Services Act). The secrecy of correspondence is protected by Article 15 of the Italian Constitution and may only be restricted by a reasoned act of the judicial authority. See the board privacy policy for details.',

	'PMREAD_SEARCH'						=> 'Search messages',
	'PMREAD_SEARCH_USER'				=> 'Username',
	'PMREAD_SEARCH_USER_EXPLAIN'		=> 'Find messages involving a user. Matching is partial: “mar” also finds “martina33”. You may use * as a wildcard.',
	'PMREAD_FIND_USER'					=> 'Find a member',
	'PMREAD_SEARCH_SCOPE'				=> 'Match the user as',
	'PMREAD_SCOPE_ANY'					=> 'Sender or recipient',
	'PMREAD_SCOPE_FROM'					=> 'Sender only',
	'PMREAD_SCOPE_TO'					=> 'Recipient only',
	'PMREAD_SEARCH_EMAIL'				=> 'Email',
	'PMREAD_SEARCH_EMAIL_EXPLAIN'		=> 'The user email address, partial matches allowed. It combines with the username: fill in both and the user must match both.',
	'PMREAD_SEARCH_KEYWORD'				=> 'Keyword',
	'PMREAD_SEARCH_KEYWORD_EXPLAIN'		=> 'Searches the message subject and body.',
	'PMREAD_SEARCH_DATE'				=> 'Date range',
	'PMREAD_SEARCH_DATE_EXPLAIN'		=> 'For a single day set the same date in both fields. Either field may be left empty.',
	'PMREAD_DATE_FROM'					=> 'From',
	'PMREAD_DATE_TO'					=> 'To',
	'PMREAD_SEARCH_YEAR'				=> 'Year',
	'PMREAD_SEARCH_YEAR_EXPLAIN'		=> 'Shortcut for a whole year (1 January – 31 December). Ignored when the dates above are filled in.',
	'PMREAD_SEARCH_SUBMIT'				=> 'Search',
	'PMREAD_SEARCH_RESET'				=> 'Reset filters',
	'PMREAD_FILTER_ON'					=> 'Filter active: the list and the “Export all” button only cover the search results.',

	'PMREAD_PRINT_MARKED'				=> 'Print selected',
	'PMREAD_PRINT_TITLE'				=> 'Private messages printout',
	'PMREAD_PRINT_META'					=> 'Printed by %1$s on %2$s – %3$d messages.',
	'PMREAD_PRINT_NOW'					=> 'Print',
	'PMREAD_PRINT_CLOSE'				=> 'Close',
	'PMREAD_PRINT_ALL'					=> 'Print results',
	'PMREAD_PRINT_TRUNCATED'			=> 'Printing is limited to the first %1$s messages. Narrow the search to print more.',
));
