<?php
require_once('api_base_tests.php');

/**
*
*/
class APICreateTests extends APIBaseTests
{

	public function __construct()
	{
		parent::__construct();
	}

	public function run_all(){
		$this->passResults['Create Password w/ Exp'] = $this->create_pw_exp();
		$this->passResults['Create Password with all but notifications'] = $this->create_without_notifications();
	}

	/**
	 * Create a PX that expires in 5 seconds.
	 * Immediately get it, then wait for 5 seconds and verify it was deleted.
	 */
	public function create_pw_exp(){
		$pw = 'aaZZ!@#$%^&*()--+={}[]\/?';
		$date = new DateTime();
		$exp = $date->getTimestamp() + 5;

		$createResponse = $this->client->post(
			'/api/passwords',
			['Content-Type' => 'application/json'],
			json_encode([
				'password' => $pw,
				'expireDate' => $exp
				/*'user' => null,
				'note' => null,
				'viewLimit' => null,
				'ipRestrictions' => null,
				'accountId' => null,
				'useAcctPassword' => false,
				'notifications' => null*/
			])
		)->send();

		$isCreated = assert($this->isSuccess($createResponse->getStatusCode()));
		$body = json_decode($createResponse->getBody());

		$getResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$isGettable = assert($this->isSuccess($getResponse->getStatusCode()));

		$getBody = json_decode($getResponse->getBody());
		$isPwIntact = assert($getBody->password == $pw);
		$isDateIntact = assert($getBody->expiration == $exp);

		sleep(6);

		$deleteResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$isPwDeleted = assert($this->isFail($deleteResponse->getStatusCode()));

		$this->results['Create PW with Exp'] = [
			'Password was created' => $isCreated,
			'Password is gettable' => $isGettable,
			'Password matches input on get' => $isPwIntact,
			'Expiration matches input on get' => $isDateIntact,
			'Password expired correctly' => $isPwDeleted
		];

		return in_array(false, $this->results['Create PW with Exp']);

	}

	public function create_without_notifications(){
		$pw = '%passwordPASSWORD%';
		$date = new DateTime();
		$exp = $date->getTimestamp() + 3;
		$user = "userUSERuser";
		$note = "note NOTE nottey note note!!";
		$viewLimit = 1; //not testing logic, just creation
		$ipRestrictions = "*.*.*.*"; //not testing logic, just creation

		$createResponse = $this->client->post(
			'/api/passwords',
			['Content-Type' => 'application/json'],
			json_encode([
				'password' => $pw,
				'expireDate' => $exp,
				'user' => $user,
				'note' => $note,
				'viewLimit' => $viewLimit,
				'ipRestrictions' => $ipRestrictions,
				//'accountId' => null, // not implemented
				//'useAcctPassword' => false, //not implemented
				//'notifications' => null //not tested here
			])
		)->send();

		$isCreated = assert($this->isSuccess($createResponse->getStatusCode()));
		$body = json_decode($createResponse->getBody());

		$getResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$isGettable = assert($this->isSuccess($getResponse->getStatusCode()));

		$getBody = json_decode($getResponse->getBody());
		$isPwIntact = assert($getBody->password == $pw);
		$isDateIntact = assert($getBody->expiration == $exp);
		$isNoteIntact = assert($getBody->note == $note);
		$isUserIntact = assert($getBody->username == $user);

		$this->results['Create with all but notifications'] = [
			'Password was created' => $isCreated,
			'Password is gettable' => $isGettable,
			'Password matches input on get' => $isPwIntact,
			'Expiration matches input on get' => $isDateIntact,
			'Note matches input on get' => $isNoteIntact,
			'User matches input on get'=> $isUserIntact
		];

		return in_array(false, $this->results['Create with all but notifications']);
	}

}
