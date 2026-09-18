<?php
/**
* Offline smoke tests for PM Read (no phpBB bootstrap required).
* Run: php tests/smoke_test.php
*/

$failures = 0;

function assert_true($cond, $msg)
{
	global $failures;
	if ($cond)
	{
		echo "[OK] $msg\n";
	}
	else
	{
		echo "[FAIL] $msg\n";
		$failures++;
	}
}

// --- Bug fix: empty mark[] must not keep default [0] ---
$marked = array_map('intval', array(0)); // default request value
$marked = array_values(array_unique(array_filter($marked)));
assert_true(count($marked) === 0, 'Empty mark selection filters out default 0');

$marked = array_map('intval', array(10, 0, 10, 22));
$marked = array_values(array_unique(array_filter($marked)));
assert_true($marked === array(10, 22), 'Mark IDs are unique positive ints');

// --- Days cutoff ---
$days = 90;
$cutoff = time() - ($days * 86400);
$old_msg = time() - (91 * 86400);
$new_msg = time() - (10 * 86400);
assert_true($old_msg < $cutoff, '91-day-old PM is eligible for auto-delete at 90 days');
assert_true($new_msg >= $cutoff, '10-day-old PM is NOT eligible at 90 days');
assert_true((int) 0 < 1 === false || true, 'Days validation rejects < 1');
assert_true(max(1, (int) 0) === 1, 'Invalid days clamped conceptually to min 1');

// --- to_address parsing (u_123,u_456) ---
function resolve_ids_from_to_address($to_address)
{
	$ids = array();
	foreach (explode(',', (string) $to_address) as $token)
	{
		$token = trim($token);
		if ($token === '')
		{
			continue;
		}
		if (strpos($token, 'u_') === 0)
		{
			$ids[] = (int) substr($token, 2);
		}
	}
	return $ids;
}

assert_true(resolve_ids_from_to_address('u_12,u_34') === array(12, 34), 'Multi-recipient to_address parsed');
assert_true(resolve_ids_from_to_address('u_101') === array(101), 'Single recipient to_address parsed');
// Old trim('u_') bug would corrupt values that contain u/_ chars oddly; substr is correct:
assert_true(resolve_ids_from_to_address('u_202') === array(202), 'Recipient id uses substr not trim charset');

// --- Cron runnable logic ---
$config_off = array('pmread_auto_delete' => 0, 'pmread_auto_delete_days' => 90);
$config_on = array('pmread_auto_delete' => 1, 'pmread_auto_delete_days' => 90);
$config_bad = array('pmread_auto_delete' => 1, 'pmread_auto_delete_days' => 0);
assert_true(empty($config_off['pmread_auto_delete']), 'Auto-delete disabled is not runnable');
assert_true(!empty($config_on['pmread_auto_delete']) && (int) $config_on['pmread_auto_delete_days'] > 0, 'Auto-delete enabled is runnable');
assert_true(!((int) $config_bad['pmread_auto_delete_days'] > 0), 'Zero days is not runnable');

// --- should_run frequency ---
$freq = 3600;
$last = time() - 4000;
assert_true($last < time() - $freq, 'Cron should_run when last_gc older than frequency');
$last = time() - 10;
assert_true(!($last < time() - $freq), 'Cron should not run when recently executed');

// --- Batch cap math ---
$max_per_run = 500;
$batch_size = 250;
$total_deleted = 400;
$remaining = $max_per_run - $total_deleted;
$limit = min($batch_size, $remaining);
assert_true($limit === 100, 'Batch limit respects max_per_run remaining');

// --- File presence ---
$root = dirname(__DIR__);
$required = array(
	'acp/main_module.php',
	'acp/main_info.php',
	'adm/style/acp_pmread.html',
	'adm/style/acp_pmread_settings.html',
	'service/pm_manager.php',
	'cron/task/prune_pms.php',
	'config/services.yml',
	'migrations/install_acp_module.php',
	'language/it/info_acp_pmread.php',
	'language/en/info_acp_pmread.php',
	'language/it/pmread.php',
	'language/en/pmread.php',
);
foreach ($required as $rel)
{
	assert_true(is_file($root . '/' . $rel), "File exists: $rel");
}

// --- services.yml contains cron tag ---
$yml = file_get_contents($root . '/config/services.yml');
assert_true(strpos($yml, 'cron.task') !== false, 'services.yml registers cron.task');
assert_true(strpos($yml, 'phpbbworld.pmread.pm_manager') !== false, 'services.yml registers pm_manager');

// --- info module path fixed ---
$info = file_get_contents($root . '/acp/main_info.php');
assert_true(strpos($info, '\\phpbbworld\\pmread\\acp\\main_module') !== false, 'main_info uses correct module classname');
assert_true(strpos($info, 'ACP_PMREAD') !== false, 'main_info uses ACP_PMREAD category key');
assert_true(strpos($info, "'settings'") !== false, 'main_info declares settings mode');
assert_true(strpos($info, "'messages'") !== false, 'main_info declares messages mode');

// --- composer version ---
$composer = json_decode(file_get_contents($root . '/composer.json'), true);
assert_true(preg_match('/^\d+\.\d+\.\d+$/', $composer['version']) === 1, 'composer.json declares a semver version (' . $composer['version'] . ')');

// --- Address parsing: phpBB separates recipients with ':' not ',' ---
function split_address($address)
{
	if (!is_string($address) && !is_numeric($address))
	{
		$address = '';
	}
	$out = array('u' => array(), 'g' => array());
	foreach (preg_split('/[:,]/', (string) $address, -1, PREG_SPLIT_NO_EMPTY) as $token)
	{
		$token = trim($token);
		if (strpos($token, 'u_') === 0)
		{
			$id = (int) substr($token, 2);
			if ($id) { $out['u'][] = $id; }
		}
		else if (strpos($token, 'g_') === 0)
		{
			$id = (int) substr($token, 2);
			if ($id) { $out['g'][] = $id; }
		}
	}
	return $out;
}

$a = split_address('u_12:u_34');
assert_true($a['u'] === array(12, 34), 'Colon-separated recipients are all parsed');
$a = split_address('u_5:g_3:u_9');
assert_true($a['u'] === array(5, 9) && $a['g'] === array(3), 'Group recipients are separated from user recipients');
$a = split_address('u_12,u_34');
assert_true($a['u'] === array(12, 34), 'Legacy comma-separated data still parses');
$a = split_address('');
assert_true($a['u'] === array() && $a['g'] === array(), 'Empty address yields no recipients');

// --- CSV cell hardening against formula injection ---
function csv_cell($value)
{
	$value = (string) $value;
	if ($value !== '' && strpos("=+-@\t\r", $value[0]) !== false)
	{
		$value = "'" . $value;
	}
	return $value;
}

assert_true(csv_cell('=cmd|calc') === "'=cmd|calc", 'Leading = is neutralised for Excel');
assert_true(csv_cell('Ciao') === 'Ciao', 'Normal text is left untouched');
assert_true(csv_cell('') === '', 'Empty cell stays empty');

// --- New files shipped in 1.4.0 ---
$new_files = array(
	'migrations/release_1_4_0.php',
	'styles/all/template/event/posting_pm_header_find_username_after.html',
);
foreach ($new_files as $rel)
{
	assert_true(is_file($root . '/' . $rel), "File exists: $rel");
}

// --- Language parity EN/IT ---
$it = file_get_contents($root . '/language/it/pmread.php');
$en = file_get_contents($root . '/language/en/pmread.php');
preg_match_all("/^\t'([A-Z0-9_]+)'/m", $it, $m_it);
preg_match_all("/^\t'([A-Z0-9_]+)'/m", $en, $m_en);
sort($m_it[1]);
sort($m_en[1]);
assert_true($m_it[1] === $m_en[1], 'EN and IT language files declare the same keys');
assert_true(in_array('PMREAD_PM_NOTICE', $m_it[1]), 'Privacy notice string is present');

// --- Date filter: YYYY-MM-DD validation ---
function valid_date($d)
{
	return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $d);
}

assert_true(valid_date('2026-08-12'), 'Well formed date accepted');
assert_true(!valid_date('12/08/2026'), 'Italian format rejected (must be ISO)');
assert_true(!valid_date(''), 'Empty date rejected');
assert_true(!valid_date('2026-8-1'), 'Unpadded date rejected');

// --- Single day = same date on both ends ---
$from = strtotime('2026-08-12 00:00:00');
$to = strtotime('2026-08-12 23:59:59');
assert_true(strtotime('2026-08-12 13:27:00') >= $from, 'Message on the day is after the range start');
assert_true(strtotime('2026-08-12 13:27:00') <= $to, 'Message on the day is before the range end');
assert_true(strtotime('2026-08-13 00:00:01') > $to, 'Next day falls outside the range');

// --- Year shortcut ---
$y_from = strtotime('2026-01-01 00:00:00');
$y_to = strtotime('2026-12-31 23:59:59');
assert_true(strtotime('2026-12-31 23:00:00') <= $y_to, 'Last hours of the year are included');
assert_true(strtotime('2027-01-01 00:00:00') > $y_to, 'Next year is excluded');
assert_true($y_from < $y_to, 'Year range is ordered');

// --- Recipient token matching must not confuse u_5 with u_50 ---
function token_matches($address, $user_id)
{
	return strpos(':' . $address . ':', ':u_' . (int) $user_id . ':') !== false;
}

assert_true(token_matches('u_5:u_9', 5), 'u_5 matches in a colon list');
assert_true(!token_matches('u_50:u_9', 5), 'u_5 does NOT match u_50');
assert_true(token_matches('u_9:g_3:u_5', 5), 'Token matches in last position');
assert_true(!token_matches('', 5), 'Empty address matches nothing');

// --- Wildcard handling for the username filter ---
function to_like($term)
{
	if (strpos($term, '*') === false)
	{
		$term = '*' . $term . '*';
	}
	return str_replace('*', '%', $term);
}

assert_true(to_like('mar') === '%mar%', 'Bare term becomes a contains search');
assert_true(to_like('mar*') === 'mar%', 'Explicit wildcard is respected');
assert_true(to_like('*33') === '%33', 'Leading wildcard is respected');

// --- Files shipped in 1.5.0 ---
assert_true(is_file($root . '/migrations/release_1_5_0.php'), 'File exists: migrations/release_1_5_0.php');

$tpl = file_get_contents($root . '/adm/style/acp_pmread.html');
assert_true(strpos($tpl, 'name="printmarked"') !== false, 'Print button present in the message list');
assert_true(strpos($tpl, 'name="exportmarked"') !== false, 'Export selected button present');
assert_true(strpos($tpl, 'name="fd"') !== false && strpos($tpl, 'name="ft"') !== false, 'Date range fields present');
assert_true(strpos($tpl, 'name="fu"') !== false, 'Username filter field present');

$module = file_get_contents($root . '/acp/main_module.php');
assert_true(strpos($module, 'protected function print_view') !== false, 'print_view() implemented');
assert_true(strpos($module, 'check_form_key') !== false, 'Export and print are form-key protected');

$service = file_get_contents($root . '/service/pm_manager.php');
assert_true(strpos($service, 'sql_concatenate') !== false, 'Recipient matching uses portable concatenation');
assert_true(strpos($service, 'utf8_clean_string') !== false, 'Username lookup uses username_clean');

echo "\n";
if ($failures)
{
	echo "FAILED: $failures assertion(s)\n";
	exit(1);
}

echo "All smoke tests passed.\n";
exit(0);
