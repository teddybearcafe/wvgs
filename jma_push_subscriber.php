<?php
	main();
	
	function main(){
		LogPut("main() start");
		$method = $_SERVER['REQUEST_METHOD'];
		if ($method == 'GET') {
			doGet();
		} else if ($method == 'POST') {
			doPost();
		}
		LogPut("main() end");
	}
	
	function doGet(){
		LogPut("doGet() start");
		$verify_token = "kurumi";
		
		// subscribe (or unsubscribe) a feed from the HUB
		$hubmode = $_REQUEST['hub_mode'];
		$hubchallenge = $_REQUEST['hub_challenge'];
		if ($hubmode == 'subscribe' || $hubmode == 'unsubscribe') {
			if ($_REQUEST['hub_verify_token'] != $verify_token) {//verify_tokenのチェック
				LogPut("doGet() hub_verify_token unmatch");
				header('HTTP/1.1 404 "Unknown Request"', null, 404);
				exit("Unknown Request");
			}
			// response a challenge code to the HUB
			header('HTTP/1.1 200 "OK"', null, 200);
			header('Content-Type: text/plain');
			echo $hubchallenge;
		} else {
			header('HTTP/1.1 404 "Not Found"', null, 404);
			LogPut("doGet() hubmode unmatch");
			exit("Unknown Request");
		}
		LogPut("doGet() end");
	}
	
	function doPost(){
		LogPut("doPost() start");
		// receive a feed from the HUB
		
		// feed Receive
		$string = file_get_contents("php://input");
		
		//ファイル保存とか
		$fp = fopen("./data/atom/".date('YmdHis') . "_atom" . ".xml", "w");
		fwrite($fp, $string);
		fclose($fp);
		
		//後は適当にParseしよう
		if (FALSE === ($feed = simplexml_load_string($string))) {
			LogPut("doPost() feed Parse ERROR");
			exit("feed Parse ERROR");
		}
		LogPut(var_export($feed,true));
		
		foreach ($feed->entry as $entry) {
			$url = $entry->link['href'];
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$fp = fopen("./data/".basename($url), "w");
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		}
		LogPut("doPost() end");
	}
	
	//標準出力及びログファイルへ出力
	function LogPut($buf){
		$date = date('Y/m/d(D) H:i', time());
		$buf = $date." ". $buf;
		
		//標準出力
		//echo mb_convert_encoding($buf . "<br>\n", "SJIS", "UTF-8");
		
		//ログファイルへ出力
		$fp = fopen("log.txt", "a+");
		fputs($fp, $buf. "\n");
		fclose($fp);
	}
?>
