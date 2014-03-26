<?php
require_once('../vendor/guzzle.phar');
use Guzzle\Http\Client;

class APIBaseTests
{

	public $results = [];
	public $passResults = [];

	public function __construct($exceptions = false)
	{
		$this->client = new Client('http://beta.passwordexploder.com');

		// We'll show assert failures manually.  Using an assert callback could make this
		// much more automated.
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);

		// Stop Guzzle from throwing Exceptions on 400/500 (Default behavior).
		// It should report these, not die.
		if (!$exceptions){
			$this->client->getEventDispatcher()->addListener(
				'request.error',
				function($event) {
					if ($event['response']->getStatusCode() != 200) {
						$event->stopPropagation();
					}
				}
			);
		}
	}

	public function run_all(){}

	public function isSuccess($code){
		return (substr($code,0,1) === '2');
	}

	public function isFail($code){
		return (substr($code,0,1) === '4');
	}

	public function isError($code){
		return (substr($code,0,1) === '5');
	}

	public function get_pass_fail(){
		return in_array(false, $this->passResults);
	}

	public function print_results(){
		foreach ($this->results as $test => $results) {
			$this->pretty_print($test, $results);
		}
	}

	public function pretty_print($name, $results){
		echo "\033[1;32m" . $name . "\033[0m" . "\n";
		foreach ($results as $test => $result) {
			echo "   " . $test . ": \t" . (($result) ? "\033[1;37mPassed\033[0m" : "\033[1;31mFailed\033[0m") . "\n";
		}
	}

}

