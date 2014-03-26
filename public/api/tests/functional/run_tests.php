<?php

require_once('api_create_tests.php');
require_once('api_delete_tests.php');

$suites = ['APICreateTests', 'APIDeleteTests'];

foreach ($suites as $suiteName) {
	$suite = new $suiteName();
	$suite->run_all();
	$suite->print_results();
}
