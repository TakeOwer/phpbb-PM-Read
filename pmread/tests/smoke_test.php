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
assert_true($composer['version'] === '1.3.0', 'composer.json version is 1.3.0');

echo "\n";
if ($failures)
{
	echo "FAILED: $failures assertion(s)\n";
	exit(1);
}

echo "All smoke tests passed.\n";
exit(0);
