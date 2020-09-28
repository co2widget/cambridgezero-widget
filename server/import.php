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
		// Remove unecessary charactors
		$resp = str_replace("\n", "", $resp);
		$resp = str_replace("\r", "", $resp);
		$resp = str_replace("]", "", $resp);
		$resp = str_replace("Date.UTC(", "", $resp);
		$resp = substr($resp, 4);
		$resp = substr($resp, 0, -3);

		curl_close($curl);

		// Explde into array
		$data = explode(',[', $resp);

		// JSON encode for 
		$return = json_encode([
			'average' => Import::average($data),
			'increase' => Import::increase($data),
			'chart' => Import::chart($data),
		]);

		echo $return;

		Import::save($path, $return);

	}

	private static function average($data) {
		$data = array_reverse($data);

		$x = 0;
		$total = 0;
		while ($x < 7) {
			$i = explode('),', $data[$x]);
			$total += floatval($i[1]);
			$x++;
		}

		return floatval(number_format($total/$x, 2, '.', ''));
	}

	private static function increase($data) {
		$data = array_reverse($data);

		$latest = explode('),', $data[0]);
		$twoyears = explode('),', $data[729]);

		$change = floatval($latest[1]) - floatval($twoyears[1]);


		if ($change < 0) {
			$char = '&darr;&nbsp;';
		} elseif ($change > 0) {
			$char = '&uarr;&nbsp;';

		}
		$change = number_format($change, 2, '.', '');
		return $char.$change;

	}

	private static function chart($data) {
		$x = 0;
		$points = [];
		while ($x < count($data)) {
			$record = explode(',', $data[$x]);

			// have year and value for each point
			$points[] = [
				intval($record[0]),
				floatval($record[3])
			];


			// Logic to ensure each value is each 5 years
			if ($x < 164) {
				$x++;	
			} elseif ($x == 164) {
				$x = 168;
			} elseif ($x >= 164 && $x <= 283) {
				$x +=5; 
			} elseif ($x > 283 && $x < 364) {
				$x = 364;
			} elseif ($x >= 364 && $x <= 2964) {
				$x += 260;
			} else {
				$x += 365*5;
			}
		}

		$last = explode(',', end($data));

		$points[] = [
			intval($last[0]),
			floatval($last[3])
		];




		return str_replace('+', ' ', urlencode('<svg viewBox="0 0 500 100" class="chart" preserveAspectRatio="none"><polyline fill="none" stroke="#0074d9" stroke-width="2" points="00,120 20,60 40,80 60,20 80,80 100,80 120,60 140,100 160,90 180,80 200, 110 220, 10 240, 70 260, 100 280, 100 300, 40 320, 0 340, 100 360, 100 380, 120 400, 60 420, 70 440, 80" vector-effect="non-scaling-stroke"></polyline></svg>'));
	}

	private static function save($path, $return) {
		file_put_contents(__DIR__.'/../'.$path, $return);
	}
}
