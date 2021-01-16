<?php

// https://stackoverflow.com/questions/58256677/using-php-with-google-bigquery

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
			'change' => Import::change($data),
			'chart' => Import::chart($data),
			'chart2' => Import::chart2($data),
			'year' => date('Y'), // Current year to get halfway point easier on chart
			'date' => date('j M Y'), // Current year to get halfway point easier on chart
			'scale' => Import::scale($data),
		]);

		//print_r($data);
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

		return floatval(number_format($total/$x, 1, '.', ''));
	}

	private static function change($data) {
		$data = array_reverse($data);

		$latest = explode('),', $data[0]);
		$twoyears = explode('),', $data[729]);

		$change = floatval($latest[1]) - floatval($twoyears[1]);


		if ($change < 0) {
			$char = '-';
		} elseif ($change > 0) {
			$char = '+';

		}
		$change = number_format($change, 1, '.', '');
		return $char.$change;

	}

	private static function chart($data) {
		$x = 0;
		$points = [];
		while ($x < count($data)) {
			$record = explode(',', $data[$x]);

			// have year and value for each point
			$year = intval($record[0]);

			if ($year == 0) {
				$year = 1010;
			}
			$points[] = [
				$year,
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


		$offset = 10; // Prevent negative results

		$first = $points[0];
		// print_r($first);
		$last = end($points);

		$width = count($points); // based on number of years
		$height = $last[1] - $first[1] + $offset; // based on number of values

		$x = 0;
		$polyline = [];
		foreach ($points as $key => $point) {
			$y = $height - ($point[1] - $first[1]) - $offset;
			$polyline[] = "${x},${y}";
			$x++;
		}
		// print_r($polyline);

		$y300 = $height - (300 - $first[1]) - $offset;
		$y400 = $height - (400 - $first[1]) - $offset;

		$y300 = "<line class=\"y300\" x1=\"0\" x2=\"${width}\" y1=\"${y300}\" y2=\"${y300}\" stroke=\"#d0d0d0\" stroke-width=\"1\" vector-effect=\"non-scaling-stroke\" id=\"y300\" stroke-dasharray=\"5,5\"></line>";

		$y400 = "<line class=\"y400\" x1=\"0\" x2=\"${width}\" y1=\"${y400}\" y2=\"${y400}\" stroke=\"#d0d0d0\" stroke-width=\"1\" vector-effect=\"non-scaling-stroke\"id=\"y400\" stroke-dasharray=\"5,5\"></line>";

		$xaxis = "<line x1=\"0\" x2=\"${width}\" y1=\"${height}\" y2=\"${height}\" stroke=\"none\" vector-effect=\"non-scaling-stroke\"id=\"y400\"></line>";

		ob_start(); ?><svg viewBox="0 0 <?= $width; ?> <?= $height; ?>" data-height="<?= $height; ?>" class="chart2000" preserveAspectRatio="none"><?= $y300; ?><?= $y400; ?><?= $xaxis; ?><polyline fill="none" stroke="#d97400" stroke-width="4" points="<?= implode(' ', $polyline); ?>" vector-effect="non-scaling-stroke" stroke-linecap="round"></polyline></svg><?php 
		return str_replace('+', ' ', urlencode(ob_get_clean()));
	}

	private static function chart2($data) {
		$x = 0;
		$points = [];
		while ($x < count($data)) {
			$record = explode(',', $data[$x]);

			// have year and value for each point
			$year = intval($record[0]);

			if ($year == 0) {
				$year = 1010;
			}
			$points[] = [
				$year,
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


		$offset = 10; // Prevent negative results

		$first = $points[0];
		// print_r($first);
		$last = end($points);

		$width = count($points); // based on number of years
		$height = $last[1] - $first[1] + $offset; // based on number of values

		$x = 0;
		$polyline = [];
		foreach ($points as $key => $point) {
			$y = $height - ($point[1] - $first[1]) - $offset;
			$polyline[] = "${x},${y}";
			$x++;
		}
		// print_r($polyline);

		$y300 = $height - (300 - $first[1]) - $offset;
		$y400 = $height - (400 - $first[1]) - $offset;

		$y300 = "<line class=\"y300\" x1=\"0\" x2=\"${width}\" y1=\"${y300}\" y2=\"${y300}\" stroke=\"#d0d0d0\" stroke-width=\"1\" vector-effect=\"non-scaling-stroke\" id=\"y300\" stroke-dasharray=\"5,5\"></line>";

		$y400 = "<line class=\"y400\" x1=\"0\" x2=\"${width}\" y1=\"${y400}\" y2=\"${y400}\" stroke=\"#d0d0d0\" stroke-width=\"1\" vector-effect=\"non-scaling-stroke\"id=\"y400\" stroke-dasharray=\"5,5\"></line>";

		$xaxis = "<line x1=\"0\" x2=\"${width}\" y1=\"${height}\" y2=\"${height}\" stroke=\"none\" vector-effect=\"non-scaling-stroke\"id=\"y400\"></line>";

		ob_start(); ?><svg viewBox="0 0 <?= $width; ?> <?= $height; ?>" data-height="<?= $height; ?>" class="chart20" preserveAspectRatio="none" fill="red"><?= $y300; ?><?= $y400; ?><?= $xaxis; ?><polyline fill="none" stroke="#d97400" stroke-width="4" points="<?= implode(' ', $polyline); ?>" vector-effect="non-scaling-stroke" stroke-linecap="round"></polyline></svg><?php 
		return str_replace('+', ' ', urlencode(ob_get_clean()));
	}


	private static function scale($data) {
		$change = floatval(substr(Import::change($data), 1));

		if ($change > "0") {
			$change = $change + 10;
		} else {
			$change = 10 - abs($change);
		}


		$rect = "<rect width=\"100%\" height=\"100%\" x=\"0%\" fill=\"none\" stroke-width=\"0.5\" stroke=\"#3d3d3d\"></rect>";
		
		$markers = "<g class=\"markers\">";

		$pc = 0;
		for ($x = 1; $x <= 9; $x++) {
			$pc = $pc + 10;
			if ($x < "4") {
				$fill = "green";
			} else if ($x < "7") {
				$fill = "yellow";
			} else {
				$fill = "red";
			}
			
			$pos = $pc . "%";
			$marker = "<rect fill=$fill x=\"$pos\" y=\"0\" width=\"0.5\" height=\"100%\"></rect>";
			$markers = $markers . $marker;		
		}
		
		$markers = $markers . "</g>"; 

		$tri_pos = ($change) / 20 * 100; 
		$tri_coords_top = ($tri_pos - 2) . ',0 ' . ($tri_pos + 2) . ',0 ' . $tri_pos . ',20';
		$tri_coords_btm = ($tri_pos - 2) . ',100 ' . ($tri_pos + 2) . ',100 ' . $tri_pos . ',80';
		$triangle = "<g><polygon fill=\"#3d3d3d\" points=\"$tri_coords_top\"/><polygon fill=\"#3d3d3d\" points=\"$tri_coords_btm\"/></g>";

		ob_start(); ?><svg width="100%" height="20%" viewBox="0 0 100 100" class="scale" preserveAspectRatio="none"><?= $markers; ?><?= $rect; ?><?= $triangle; ?></svg><?php 
		return str_replace('+', ' ', urlencode(ob_get_clean()));
	}

	private static function save($path, $return) {
		file_put_contents(__DIR__.'/../'.$path, $return);
	}
}
