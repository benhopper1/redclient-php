<?php

//==================================================================
//--  HOPPER UTILITIES  --------------------------------------------
//==================================================================
class HopperUtil{
	private function __construct(){}
	private function __clone(){}
	private static $__instance = NULL;
	public static function getInstance(){
		if(self::$__instance == NULL){
			if(self::$__instance == NULL) self::$__instance = new HopperUtil;
			return self::$__instance;
		}
		return self::$__instance;
	}

	public function test(){
		echo('test');
	}

	public function phpToJson($inPhp){
		return json_encode($inPhp);
	}

	public function jsonToPhp($inJson){
		return json_decode($inJson);
	}

	public function guid($inLength = 50){
		return substr( md5(rand()), 0, $inLength);
	}
}

require __DIR__.'/libs/predis/autoload.php';
//==================================================================
//--  RED CLIENT  --------------------------------------------------
//==================================================================
Class RedClient{
	private $redClient_sub;
	private $redClient_pub;
	private $returnFunction;

	function __construct($inOptions = array()){
		$options = array
			(
				'host'     				=> '127.0.0.1',
				'port'     				=> 6379,
				'database' 				=> 15,
				'read_write_timeout' 	=> 0,
			)
		;
		$options = array_merge($options, $inOptions);
		$this->redClient_sub = new Predis\Client($options);
		$this->redClient_pub = new Predis\Client($options);
	}

	public function sendTransaction($inChannel, $inPassData, $inFunction){
		$this->returnFunction = $inFunction;
		$pubsub = $this->redClient_sub->pubSubLoop();
		$pubsub->subscribe('theCallback');

		$theArray = array();
		$theArray['returnChannel'] = 'theCallback';
		$theArray['data'] = $inPassData;

		$this->redClient_pub->publish($inChannel, HopperUtil::getInstance()->phpToJson($theArray));

		foreach ($pubsub as $message) {
			switch ($message->kind) {
				case 'subscribe':
					break;
				case 'message':
					if($message->channel == 'theCallback'){
						$pubsub->unsubscribe();
						unset($pubsub);
						$inFunction($message->payload);
					}
					break;
			}
		}
	}

	public function send($inChannel, $inPassData){
		$this->redClient_pub->publish($inChannel, HopperUtil::getInstance()->phpToJson($inPassData));
	}




}



function runTest(){
	echo('GUID:' . HopperUtil::getInstance()->guid());
	echo('<br>');

	echo('<br>==================================================================<br>');
	echo('--  INSTANATE & TEST TRANSACTION ---------------------------------<br>');
	echo('==================================================================<br>');
	$redClient = new RedClient();
	$redClient->sendTransaction('CIP_query', array
		(
			'command' 	=> 'allUserData',
			'params' 	=> false,
			'data' 		=> array
				(
					'userId' 	=> '494',
				),
		), function($msg){
			echo('<br>CALLBACK------<br>');
			echo($msg);
	});

	echo('<br>==================================================================<br>');
	echo('--  INSTANATE & TEST TRANSACTION ---------------------------------<br>');
	echo('==================================================================<br>');
	$redClient = new RedClient();
	$redClient->sendTransaction('CIP_query', array
		(
			'command' 	=> 'allUserData',
			'params' 	=> false,
			'data' 		=> array
				(
					'userId' 	=> '494',
				),
		), function($msg){
			echo('<br>CALLBACK------<br>');
			echo($msg);
	});


	echo('<br>==================================================================<br>');
	echo('--  INSTANATE & TEST NON-TRANSACTION -----------------------------<br>');
	echo('==================================================================<br>');
	$redClient = new RedClient();
	$redClient->send('CIP', array
		(
			'command' 	=> 'serverKilled',
			'params' 	=> false,
			'data' 		=> array
				(
					'anythingKey' 	=> 'anythingValue',
				),
		)
	);

	echo('DONE');
}

//runTest();
