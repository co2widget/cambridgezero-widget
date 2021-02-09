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
		// var_dump($data);
		$change = Import::change($data);
		// JSON encode for 
		$return = json_encode([
			'average' => Import::average($data),
			'change' => $change,
			'chart' => Import::chart($data),
			'chart20' => Import::chart20($data),
			'year' => date('Y'), // Current year to get halfway point easier on chart
			'date' => date('j M Y'), // Current year to get halfway point easier on chart
			'angle' => Import::angle($change),
		]);

		//print_r($data);
		//echo $return;

		Import::save($path, $return);

	}

	private static function average($data) {
                
		return floatval(number_format(Import::sevenDayMovingAverage(0, array_reverse($data)), 1, '.', ''));
        }
        private static function getCO2Float($str) {
            return  floatval(array_pop(explode('),',$str)));
        }

        private static function mean($floats){
            return array_sum($floats)/count($floats);
        }

        private static function sevenDayMovingAverage($startIndex, $data){
            return Import::mean(array_map('Import::getCO2Float', array_slice($data, $startIndex, 7)));
        }
        private static function thirtyDayMovingAverage($startIndex, $data){
            return Import::mean(array_map('Import::getCO2Float', array_slice($data, $startIndex, 30)));
        }

	private static function change($data) {
		$data = array_reverse($data);

		$latest = Import::thirtyDayMovingAverage(0, $data);
		$twoYears = Import::thirtyDayMovingAverage(729, $data);
		$change = $latest - $twoYears;

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

		// var_dump($width);
		$x = 0;
		$polyline = [];
		foreach ($points as $key => $point) {
			$y = $height - ($point[1] - $first[1]) - $offset;
			$polyline[] = "${x},${y}";
			$x++;
		}
		// var_dump($polyline);

		$y300 = $height - (300 - $first[1]) - $offset;
		$y400 = $height - (400 - $first[1]) - $offset;

		$y300 = "<line class=\"y300\" x1=\"0\" x2=\"${width}\" y1=\"${y300}\" y2=\"${y300}\" stroke=\"#d0d0d0\" stroke-width=\"1\" vector-effect=\"non-scaling-stroke\" id=\"y300\" stroke-dasharray=\"5,5\"></line>";

		$y400 = "<line class=\"y400\" x1=\"0\" x2=\"${width}\" y1=\"${y400}\" y2=\"${y400}\" stroke=\"#d0d0d0\" stroke-width=\"1\" vector-effect=\"non-scaling-stroke\"id=\"y400\" stroke-dasharray=\"5,5\"></line>";

		$xaxis = "<line x1=\"0\" x2=\"${width}\" y1=\"${height}\" y2=\"${height}\" stroke=\"none\" vector-effect=\"non-scaling-stroke\"id=\"y400\"></line>";

		ob_start(); ?><svg viewBox="0 0 <?= $width; ?> <?= $height; ?>" data-height="<?= $height; ?>" class="chart2000" preserveAspectRatio="none"><?= $y300; ?><?= $y400; ?><?= $xaxis; ?><polyline fill="none" stroke="#d97400" stroke-width="4" points="<?= implode(' ', $polyline); ?>" vector-effect="non-scaling-stroke" stroke-linecap="round"></polyline></svg><?php 
		return str_replace('+', ' ', urlencode(ob_get_clean()));
	}

	private static function chart20($data) {
		$lastyear = (int)date('Y') - 1;

		// generate array of last 20 years not including this year
		$years = range($lastyear, $lastyear - 19);
		$x = 0;
		$points = [];
		$count = count($data);

		// extract year and value from strings for each year
		while ($x < count($data)) {
			$record = explode(',', $data[$x]);

			$year = intval($record[0]);

			$temp = array('year' => $year, 'value' => floatval($record[3]));
			array_push($points, $temp);

			$x++;
			
		}

		// filter points to get only last 20 years
		$data20 = array_filter($points, function($v) use ($years) {
			return in_array($v['year'], $years);
		});

		// sum values for each of 20 years and divide by respective counts to get avg
                $co2byyear = array();
                foreach ($data20 as $val) {
                    $co2byyear[$val['year']][] = $val['value'];
                }
                $points = array();
                foreach($co2byyear as $year => $vals){
                    $points[] = array("year" => $year, "avg" => Import::mean($vals));
                }
		
		$offset = 1; // Prevent negative results

		$first = $points[0]['avg'];

		// $_SERVER
		// echo $first; die();
		
		$last = end($points);
                $last = $last['avg'];
                reset($points);

		$width = count($points); // based on number of years
		$wp = $width / 100;
		
		$height = $last - $first;
		$hp = $height / 100;
		// var_dump($points);
		
		$x = 1; // start at 1 to fit on graph - needs a look
		
		$polyline = [];
		foreach ($points as $point) {
			// $y = $height - ($point['avg'] - $first);
			$y = (($point['avg'] - $first) / ($last - $first));
			$y = $y * 100;
			$left = ((($x/20) * 5) * 20);
			$bottom = $y;
			$polyline[] = "<div class=\"chart20__dot\" style=\"left:${left}%;bottom:${bottom}%\" data-avg=\"${point['avg']}\" data-year=\"${point['year']}\"></div>";
			$x++;
		}



		$xaxis = "<line x1=\"0\" x2=\"${width}\" y1=\"${height}\" y2=\"${height}\" stroke=\"none\" vector-effect=\"non-scaling-stroke\"id=\"y400\"></line>";

		$y400 = ((400 - $first) / ($last - $first));
                $y380 = ((380 - $first) / ($last - $first));
		
		ob_start(); ?><div class="chart20">
			<div class="chart20__xaxis"></div>
			<div class="chart20__yaxis"></div>
			<div class="chart20__400" style="bottom:<?= $y400*100; ?>%"></div>
                        <div class="chart20__380" style="bottom:<?= $y380*100; ?>%"></div>
			<?= implode(' ', $polyline); ?>
		</div><?php 
		return str_replace('+', ' ', urlencode(ob_get_clean()));

		
		
	}


	// Function to calculate the angle for the triangle
	private static function angle($change = 0) {

		// Circle is maximum +/-225
		if ($change > 10) {
			$change = 10;
		}

		if ($change < -10) {
			$change = -10;
		}
		return (225/200) * $change * 10;
		
	}

	private static function save($path, $return) {
		file_put_contents(__DIR__.'/../'.$path, $return);
		exec('npm run build');
	}
}
