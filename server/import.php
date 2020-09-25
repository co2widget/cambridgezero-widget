<?php

class Import {

	public static $url = 'https://www.climatelevels.org/graphs/co2-daily_data.php?callback=1';


	public static function run($path = false) {
		if (!$path){
			return;
		}

		// Grab data using cURL
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, Import::$url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$resp = curl_exec($curl);
		$resp = str_replace("\n", "", $resp);
		$resp = str_replace("\r", "", $resp);
		$resp = substr($resp, 4);
		$resp = substr($resp, 0, -3);

		curl_close($curl);

		// Explde into array
		$data = explode('],[', $resp);

		$return = [];

	}

	private static function average($data) {

	}

	private static function latest($data) {

	}

	private static function chart($data) {

	}
}
