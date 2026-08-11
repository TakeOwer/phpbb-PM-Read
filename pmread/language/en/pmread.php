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

	'MSG_ID'					=> 'Message ID',
	'FROM'						=> 'From',
	'TO'						=> 'To',
	'DATETIME'					=> 'Date',
	'SUBJECT'					=> 'Title',
	'NO_MESSAGES'				=> 'No private message',
	'EXPLAIN'					=> 'Here you can view all personal messages from users of your forum. You can select and delete messages; deletion is irreversible and removes data from the database.',
	'TOTAL_ITEMS'				=> 'Total messages: <strong>%d</strong>',

	'DELETE_MARKED'				=> 'Delete selected',
	'DELETE_ALL'				=> 'Delete All Messages',
	'MARK_ALL'					=> 'Select all',
	'UNMARK_ALL'				=> 'Deselect all',
	'NO_MESSAGES_SELECTED'		=> 'No messages selected.',
	'CONFIRM_DELETE_MARKED'		=> 'Are you sure you want to permanently delete the selected messages from the database?',
	'CONFIRM_DELETE_ALL'		=> 'Are you sure you want to permanently delete ALL private messages from the database? This cannot be undone.',
	'MESSAGES_DELETED'			=> array(
		1	=> '%d message deleted from the database.',
		2	=> '%d messages deleted from the database.',
	),
	'MESSAGES_DELETED_ALL'		=> array(
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
));
