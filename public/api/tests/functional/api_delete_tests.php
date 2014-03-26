<?php
require_once('api_base_tests.php');

class APIDeleteTests extends APIBaseTests{

	public function __construct(){
		parent::__construct();
	}

	public function run_all(){
		$this->passResults['Manually delete password'] = $this->manually_delete_password();
		$this->passResults['Viewlimit denies access appropriately'] = $this->password_deleted_at_viewlimit();
	}

	public function manually_delete_password(){
		$pw = 'aaZZ!@#$%^&*()--+={}[]\/?';
		$date = new DateTime();
		$exp = $date->getTimestamp() + 5;

		$createResponse = $this->client->post(
			'/api/passwords',
			['Content-Type' => 'application/json'],
			json_encode([
				'password' => $pw,
				'expireDate' => $exp
			])
		)->send();

		$body = json_decode($createResponse->getBody());

		$getResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$isGettable = assert($this->isSuccess($getResponse->getStatusCode()));

		$delete = $this->client->delete('/api/passwords/' . $body->id)->send();
		$deletedResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$isDeleted = assert($this->isFail($deletedResponse->getStatusCode()));

		$this->results['Manually delete password'] = [
			'Password created and accessible' => $isGettable,
			'Password subsequently deleted' => $isDeleted
		];

		return in_array(false, $this->results['Manually delete password']);
	}

	public function password_deleted_at_viewlimit(){
		$pw = 'aaZZ!@#$%^&*()--+={}[]\/?';
		$date = new DateTime();
		$exp = $date->getTimestamp() + 5;
		$viewlimit = 3;

		$createResponse = $this->client->post(
			'/api/passwords',
			['Content-Type' => 'application/json'],
			json_encode([
				'password' => $pw,
				'expireDate' => $exp,
				'viewLimit' => $viewlimit
			])
		)->send();

		$body = json_decode($createResponse->getBody());

		$getResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$getResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$getResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$isGettable = assert($this->isSuccess($getResponse->getStatusCode()));

		$deniedResponse = $this->client->get('/api/passwords/' . $body->id)->send();
		$isDenied = assert($this->isFail($deniedResponse->getStatusCode()));

		$this->results['Viewlimit Obeyed'] = [
			'Password created and accessible for 3 views' => $isGettable,
			'Password access denied on 4th get' => $isDenied
		];

		return in_array(false, $this->results['Viewlimit Obeyed']);
	}

}
