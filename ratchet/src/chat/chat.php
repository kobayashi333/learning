<?php

namespace Chat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
	protected $clients;
	
	public function __construct() {
		$this->clients = new \SplObjectStorage ();
	}
	
	public function onOpen(ConnectionInterface $conn) {
		// Store the new connection to send messages to later
		$this->clients->attach ( $conn );
		echo "New connection! ({$conn->resourceId})\n";

		$array = [];
		$status = ['type' => 'status', 'data' => [ 'status' => 'ready']];
		$numRecv = count ( $this->clients ) - 1; // 総人数カウント

		// 対象ルームの人数をカウント / オンライン担当者ログイン状況判別　処理　
		foreach ($this->clients as $client) {
			$clientParam = $this->urlParamDivide($client->httpRequest->getRequestTarget());
			if($clientParam['user_type']=='bidder'){ // 会場モニター画面用、オンライン入札者の人数をカウント用
				if( isset($array[$clientParam['room']]) ){
					$array[$clientParam['room']] = $array[$clientParam['room']] + 1;
				}else{
					$array = $array + array($clientParam['room'] => 1);
				}
			}elseif ($clientParam['user_type']=='monitor') { // モニター担当が入室後、準備中画面へ変更処理用
				$status = ['type' => 'status', 'data' => [ 'status' => 'start']];
			}
		}
		$status = json_encode($status);
		// 対象ルームの会場モニターに対して出力処理
		foreach ($this->clients as $client) {
			$clientParam = $this->urlParamDivide($client->httpRequest->getRequestTarget());
			// オークション状態をセット
			$client->send($status);
			// 会場モニターのみに選定処理
			if($clientParam['user_type']=='venue'){
				foreach ($array as $k => $v) {
					// 対象のルームを選定して、Bidderの数を出力処理
					if($clientParam['room']==$k){
						$data = ['type' => 'venue', 'data' =>[ 'num' => $v ] ];
						$json = json_encode($data);
						$client->send($json);
					} // if
				} // foreach
			} // if
		} // foreach
	} // toOpen

	public function onMessage(ConnectionInterface $from, $json) {
		$error_message = '';
		$numRecv = count ( $this->clients ) - 1;
		$param = $from->httpRequest->getRequestTarget();
		$array = json_decode($json, true);

		// URLパスを分割処理（domain/$room/$user_type　構成
		$param = explode("/",$param);	// パラメータ分割処理

		if( count($param)>=3 || count($param)<=5){
			if(count($param)==3){
				$bid_id = 'undefined';
			}else{
				if($param[3]==''){
					$bid_id = 'undefined';
				}else{
					$bid_id = $param[3];
				}
			}
			$fromParam = [
				'room'		=> $param[1],	// ROOM ID
				'user_type'	=> $param[2],	// USER
				'bid_id'	=> $bid_id		// BID ID
			];
		}else{
			$error_message = 'URLパラメータが正しくありません。リロード後再度実行してください。';
		}

		// JSONデータを連想配列化
		$array = json_decode($json, true);


		// データ通信ログ出力
		// $this->putMessageLog($json);

		foreach ( $this->clients as $client ) {
			// var_dump($client['user_type']);
			$clientParam = $this->urlParamDivide($client->httpRequest->getRequestTarget());

			// echo sprintf('Room  "%s" : USER_TYPE "%s" / FROM ROOM "%s" USER_TYPE "%s" ' . "\n", $clientParam['room'], $clientParam['user_type'], $fromParam['room'], $fromParam['user_type']);
			if ($error_message!='') {
				# code...
			}else{
				// 同一のROOMに対して、リクエスト・レスポンス
				if ($fromParam['room'] == $clientParam['room']) {

					// 自身へのSocket通信は行わない
					if( $from != $client ){
						// Requestを送ったユーザー別処理
						switch ($fromParam['user_type']) {
							case 'monitor':
								if(isset($array['type'])){
									if($array['type'] == 'bid'){
										$send_data = $this->moniBidData($array);
										$client->send($send_data);
									}elseif ($array['type'] == 'lot') {
										$client->send($json);
									}elseif ($array['type'] == 'status') {
										$client->send($json);
									}
								}else{
									$error_message = 'データの型が異なります。';
								}
								break;

							case 'online':
								if(isset($array['type'])){
									if($array['type'] == 'status' && $array['data']['status']=='accept'){
										if($array['data']['bid_id'] == $clientParam['bid_id']){
											$send_data = $this->fromOnlineData($array);
											$client->send($send_data);
										}
										if($clientParam['user_type'] == 'venue'){
											$send_data = $this->fromOnlineData($array);
											$client->send($send_data);
										}
									}elseif ($array['type'] == 'bid') {
										# code...
									}
								}else{
									$error_message = 'データの型が異なります。';
								}
								break;

							case 'bidder':
								if(isset($array['type'])){
									if( ($array["type"] == 'bid') && ($clientParam['user_type']=='online') ){
										$send_data = $this->toOnlineData($array);
										$client->send($send_data);
									}
									break;
								}else{
									$error_message = 'データの型が異なります。';
								}
							default:
								$error_message = '入力対象のユーザータイプではありません。';
								break;
						}

					} // $from != $client
				} // $from['room'] != $client['room']
			} // error_message !== ''
		} // foreach

		// エラーメッセージがある場合
		if($error_message !== ''){
			$err = [
				'type'	=> 'error',
				'data'	=> $error_message
			];
			$err_json = json_encode($err);
			foreach ( $this->clients as $client ) {
				if( $from == $client ){
					$client->send($err_json);
				}
			}
		}
	}

	public function onClose(ConnectionInterface $conn) {
		// The connection is closed, remove it, as we can no longer send it messages
		$this->clients->detach ( $conn );
		echo "Connection {$conn->resourceId} has disconnected\n";
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		echo "An error has occurred: {$e->getMessage()}\n";
		$conn->close ();
	}
	/**
	* 文字列のエスケープ処理
	* @param string $str 	文字列
	* @return string $str 	文字列
	*/
	private function h($str){
		return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
	}
	/**
	* URL パラメータ（パス）から ROOM ID / USER TYPEを取得・整形）
	* @param int $para    送信元URLパス（ディレクトリ）
	* @return array $room, $user_type
	*/
	private function urlParamDivide($param){
		// デフォルト設定
		$arr = [
			'room' 		=> false,
			'user_type' => false,
			'bid_id'	=> false
		];
		// 分割生成処理
		if(strpos($param,'/') !== false){
			// URLパスを分割処理（domain/$room/$user_type　構成
			$paramArray = explode("/",$param);						// パラメータ分割処理
			if( count($paramArray)>=3 || count($paramArray)<=5){	// ?分割された配列の数が3つ以上であれば
				$room = $paramArray[1];								// ROOM ID
				$user_type = $paramArray[2];						// USER

				if(count($paramArray)==3){
					$bid_id = 'undefined';
				}else{
					if($paramArray[3]==''){
						$bid_id = 'undefined';
					}else{
						$bid_id = $paramArray[3];
					}
				}

				$arr = [
					'room' 		=> $room,
					'user_type' => $user_type,
					'bid_id'	=> $bid_id
				];
				return $arr;
			}
		}
		return false;
	}
	/**
	* メッセージログ出力
	* @param string $client    	送信元クライアント情報
	* @param string $msg 		送信内容
	* @return boolean
	*/
	private function putMessageLog($client, $msg){
		// 日付設定（TimeStamp）
		$date = date( "Ymd" );
		$outDate = date("Y/m/d h:i:s");
		echo sprintf("date : " . $date);
		// 内容・ディレクトリの成型
		$dirPath = "./" . $date;
		$content = "[" . $outDate . "] From:" . $client . " Msg:" . $msg . "\n";
		// ディレクトリの有無確認
		if(!file_exists($directory_path)){
			mkdir($dirPath, 0644, true);
		}
		// ログ出力
		return error_log($content, 3, "./debug.log");
	}

	/**
	* 会場モニター向けデータ整形（同一Roomのみ）
	* @param array $client 		送信先（WS接続先）クライアント情報
	* @param array $data 		送信データ
	* @return array $data_arr  	表示データ連想配列(JSONエンコード済)
	*/
	private function toVenueData($from, $clients, $data){
		$data_arr = [
			'type'	=> 'bid',
			'data'	=> [
				'lot_id'=> 0,
				'price'	=> 0,
				'num'	=> 0
			]
		];
		$data_arr['data']['lot_id']	= $this->h($data['data']['lot_id']);
		$data_arr['data']['price']	= $this->h($data['data']['price']);
		// JSON化
		$json = json_encode($data_arr);
		return $json;
	}

	/**
	* オークショナー向けデータ整形（同一Roomのみ）
	* @param array $room 		送信先オークションID
	* @param array $client 		送信先（WS接続先）クライアント情報
	* @param array $data 		送信データ
	* @return array $data_arr 	表示データ連想配列（JSONエンコード済）
	*/
	private function toAuctioneerData($room, $clients, $data){
		$data_arr = [
			'type'	=> 'bid',
			'data'	=> [
				'lot_id'=> 0,
				'price'	=> 0
			]
		];
		// データ整形
		$data_arr['data']['lot_id']	= $this->h($data['data']['lot_id']);
		$data_arr['data']['price']	= $this->h($data['data']['price']);
		// JSON化
		$json = json_encode($data_arr);
		return $json;
	}

	/**
	* オンライン担当向けデータ整形（同一Roomのみ）
	* @param array $room 		送信先オークションID
	* @param array $client 		送信先（WS接続先）クライアント情報
	* @param array $data 		送信データ
	* @return array $data_arr 	表示データ連想配列（JSONエンコード済）
	*/
	private function toOnlineData($data){
		$data_arr = [
			'type'	=> 'bidder',
			'data'	=> [
				'lot_id' => 0,
				'bid_id' => 0,
				'price'  => 0
			]
		];
		// データ整形
		$data_arr['data']['lot_id']	= $this->h($data['data']['lot_id']);
		$data_arr['data']['bid_id']	= $this->h($data['data']['bid_id']);
		$data_arr['data']['price']	= $this->h($data['data']['price']);
		// JSON化
		$json = json_encode($data_arr);

		return $json;
	}

	/**
	* オンライン担当入札者向けデータ整形（同一Roomのみ）
	* @param array $data 		送信データ
	* @return array $data_arr 	表示データ連想配列（JSONエンコード済）
	*/
	private function fromOnlineData($data){
		$data_arr = [
			'type'	=> 'status',
			'data'	=> [
				'status' => 'accept',
				'lot_id' => 0,
				'bid_id' => 0
			]
		];
		// データ整形
		$data_arr['data']['lot_id']	= $this->h($data['data']['lot_id']);
		$data_arr['data']['bid_id']	= $this->h($data['data']['bid_id']);
		// JSON化
		$json = json_encode($data_arr);
		return $json;
	}

	/**
	* オンライン入札者向けデータ整形（同一Roomのみ）
	* @param array $data 		送信データ
	* @return array $data_arr 	表示データ連想配列（JSONエンコード済）
	*/
	private function moniBidData($data){
		$data_arr = [
			'type'	=> 'bid',
			'data'	=> [
				'lot_id'	=> 0,
				'price'		=> 0,
				'bid_type'	=> 'Sale Room'
			]
		];
		// データ整形
		$data_arr['data']['lot_id']	= $this->h($data['data']['lot_id']);
		$data_arr['data']['price']	= $this->h($data['data']['price']);
		// JSON化
		$json = json_encode($data_arr);
		return $json;
	}

}


