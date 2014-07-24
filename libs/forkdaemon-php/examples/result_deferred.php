<?php

/**
 * Sample of passing in work and getting back results using a call at the
 * end of the child processing
 */

declare(ticks=1);

require_once(__DIR__ . '/../fork_daemon.php');

/* setup forking daemon */
$server = new fork_daemon();
$server->max_children_set(100);
$server->max_work_per_child_set(3);
$server->store_result_set(true);
$server->register_child_run("process_child_run");
$server->register_parent_child_exit("process_child_exit");
$server->register_logging("logger", fork_daemon::LOG_LEVEL_ALL);
// no callback with this method since we check results at the end

test_nonblocking();

// since deferred results, check at the end
$results = $server->get_all_results();
var_dump($results);
echo "Sum:   " . array_sum($results) . PHP_EOL;
echo "Count: " . count($results) . PHP_EOL;

function test_nonblocking()
{
	global $server;

	echo "Adding 100 units of work\n";

	/* add work */
	$data_set = array();
	for($i=0; $i<100; $i++) $data_set[] = $i;
	shuffle($data_set);
	$server->addwork($data_set);

	echo "Processing work in non-blocking mode\n";
	echo "Sum:   " . array_sum($data_set) . PHP_EOL;

	/* process work non blocking mode */
	$server->process_work(false);

	/* wait until all work allocated */
	while ($server->work_sets_count() > 0)
	{
		echo "work set count: " . $server->work_sets_count() . "\n";
		$server->process_work(false);
		sleep(1);
	}

	/* wait until all children finish */
	while ($server->children_running() > 0)
	{
		echo "waiting for " . $server->children_running() . " children to finish\n";
		sleep(1);
	}
}

/*
 * CALLBACK FUNCTIONS
 */

/* registered call back function */
function process_child_run($data_set, $identifier = "")
{
	echo "I'm child working on: " . implode(",", $data_set) . ($identifier == "" ? "" : " (id:$identifier)") . "\n";

	sleep(rand(1,3));

	// just do a sum and return it as the result
	$result = array_sum($data_set);

	// return results
	return $result;
}

/* registered call back function */
function process_child_exit($pid, $identifier = "")
{
	echo "Child $pid just finished" . ($identifier == "" ? "" : " (id:$identifier)") . "\n";
}

/* registered call back function */
function logger($message)
{
	echo "logger: " . $message . PHP_EOL;
}
