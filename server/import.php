<?php

class Import {
	private static function err($s) {
		throw new Exception($s);
	}

	private static function ago($s) {
		$x = new DateTime();
		$x->sub(new DateInterval($s));
		return DateTimeImmutable::createFromMutable($x);
	}

	private static function get($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$resp = curl_exec($curl);
		curl_close($curl);
		return $resp;
	}

	private static function fetcHistorical() {
		$url = 'https://www.climatelevels.org/graphs/co2-daily_data.php?callback=1';
		$reg = "/UTC\((\d+),(\d+),(\d+)\),(\d+\.\d+)/";
		$resp = Import::get($url);
		$data = array();
		preg_match_all($reg, $resp, $data, PREG_SET_ORDER);
		$more_data = array_map(function ($a) {
			return array('date' => new DateTimeImmutable($a[1] . '-' . ($a[2] + 1) . '-' . $a[3]), 'value' => floatval($a[4]));
		}, $data);
		return $more_data;
	}

	private static function fetchLatest() {
		$url = "https://gml.noaa.gov/webdata/ccgg/trends/co2_mlo_weekly.csv";
		$reg = "/(\d+-\d+-\d+),(\d+\.\d+)/";
		$resp = Import::get($url);
		$data = array();
		preg_match_all($reg, $resp, $data, PREG_SET_ORDER);
		$more_data = array_map(function ($a) {
			return array('date' => new DateTimeImmutable($a[1]), 'value' => floatval($a[2]));
		}, $data);
		return $more_data;
	}

	public static function runWithRetries($path = false, $remainingRetries = 3) {
		try {
			Import::run($path);
            echo "Completed successfully";
		} catch (Exception $e) {
			if ($remainingRetries > 0) {
				echo "Error: " . $e->getMessage() . ". Retrying...\n";
				Import::runWithRetries($path, $remainingRetries - 1);
			} else die($e->getMessage());
		}
	}

	public static function run($path = false) {
		if (!$path) {
			return;
		}

		$data = array_merge(Import::fetcHistorical(), Import::fetchLatest());
		usort($data, function ($l, $r) {
			if ($l['date'] == $r['date']) return 0;
			return $l['date'] < $r['date'] ? -1 : 1;
		});
		$change = Import::change($data);
		// JSON encode for
		$weekAgo = Import::ago('P1W');
		$return = json_encode([
			'average' => number_format(Import::rangeAvg($data, $weekAgo, new DateTimeImmutable()), 1, '.', ''),
			'change' => $change,
			'chart' => Import::chart($data),
			'chart20' => Import::chart20($data),
			'year' => date('Y'), // Current year to get halfway point easier on chart
			'date' => date('j M Y'), // Current year to get halfway point easier on chart
			'angle' => Import::angle($change),
			'buildTime' => date(DATE_RSS),
		]);

		Import::save($path, $return);

	}

	private static function mean($floats) {
		return array_sum($floats) / count($floats);
	}

	private static function range($data, $startDate, $endDate) {
		return array_filter($data, function ($k) use ($startDate, $endDate) {
			return $startDate <= $k['date'] && $k['date'] <= $endDate;
		});
	}

	private static function rangeAvg($data, $startDate, $endDate) {
		$range = Import::range($data, $startDate, $endDate);
		if (empty($range)) {
			// Have to fail here if $range is empty, since the average will be NaN
			$startDateStr = $startDate->format(DateTimeInterface::ISO8601);
			$endDateStr = $endDate->format(DateTimeInterface::ISO8601);
			Import::err("Unable to find any data for dates between $startDateStr and $endDateStr");
		}
		$floats = array_map(function ($x) {
			return $x['value'];
		}, $range);
		return Import::mean($floats);
	}


	private static function change($data): string {
		$today = new DateTimeImmutable();
		$twoWeeksAgo = Import::ago('P14D');
		$twoYearAgo = Import::ago('P2Y');
		$twoYearTwoWeeksAgo = Import::ago('P2Y14D');

		$latest = Import::rangeAvg($data, $twoWeeksAgo, $today);
		$twoYears = Import::rangeAvg($data, $twoYearTwoWeeksAgo, $twoYearAgo);
		$change = $latest - $twoYears;

		if ($change < 0) {
			$char = '-';
		} elseif ($change > 0) {
			$char = '+';

		}
		$change = number_format($change, 1, '.', '');
		return $char . $change;
	}

	private static function extrapolateToCurrentYear($data, $currentYear) {
		$twoYearsAgo = Import::yearAvg($data, $currentYear - 2);
		$oneYearAgo = Import::yearAvg($data, $currentYear - 1);
		return $oneYearAgo + ($oneYearAgo - $twoYearsAgo);
	}
	private static function yearAvg($data, $year) {
		$currentYear = date("Y");
		$isCurrentYear = $year == $currentYear;
        if ($isCurrentYear) echo "calculating avg for current year\n";
        $startDate = new DateTimeImmutable($year . "-01-01");
		$endDate = new DateTimeImmutable($year . "-12-31");
		$points = Import::range($data, $startDate, $endDate);
		$thisYearAvg = null;
        // If no data points for the target year, use linear interpolation between the two closest data points
        if (empty($points)) {
			$previousYears = array_filter($data, function ($k) use ($year, $startDate) {
				return $k['date'] <= $startDate;
			});
			// Have to fail here if no years exist in data before target year
			if (empty($previousYears)) Import::err("Unable to find any data for years before $year");
			$previousYear = end($previousYears);
			$nextYears = array_filter($data, function ($k) use ($year, $endDate) {
				return $k['date'] >= $endDate;
			});
			// Have to fail here if no years exist in data after target year -- unless year is current year
			if (empty($nextYears)) {
				if ($isCurrentYear) {
                    echo "No data for year-to-date - extrapolating value from last two years\n";
                    $thisYearAvg = Import::extrapolateToCurrentYear($data, $currentYear);
                }
				else Import::err("Unable to find any data for years after $year");
			} else {
				$nextYear = array_values($nextYears)[0];
				$thisYearAvg = (
						($nextYear['value'] * ($year - Import::getYear($previousYear['date']))) +
						($previousYear['value'] * (Import::getYear($nextYear['date']) - $year))
					) / (Import::getYear($nextYear['date']) - Import::getYear($previousYear['date']));
			}
		} else {
			// otherwise just return the average
			$floats = array_map(function ($x) {
				return $x['value'];
			}, $points);
			$thisYearAvg = Import::mean($floats);
		}
		if ($isCurrentYear) {
            if (date_format($endDate, "m") < 5) {
                echo "Projecting current year from last 2\n";
                return Import::extrapolateToCurrentYear($data, $currentYear);
            } else {
                echo "Using year-to-date average\n";
                return $thisYearAvg;
            }
		} else return $thisYearAvg;
	}

	public static function getYear($date): int {
		return intval($date->format("Y"));
	}

	private static function chart($data) {
		$currentYear = Import::getYear(new DateTimeImmutable());
		echo "Current year is " . $currentYear . "\n";
		$lastThousandYears = array_map(function ($y) use ($currentYear, $data) {
			$year = $currentYear + $y - 1005;
			return [
				$year,
				Import::yearAvg($data, $year)
			];
		}, range(0, 1005, 5)); // 201 steps

		$offset = 10; // Prevent negative results

		$first = $lastThousandYears[0];
		// print_r($first);
		$last = end($lastThousandYears);

		$width = count($lastThousandYears); // based on number of years
		$height = $last[1] - $first[1] + $offset; // based on number of values

		// var_dump($width);
		$x = 0;
		$polyline = [];
		foreach ($lastThousandYears as $key => $point) {
			$y = $height - ($point[1] - $first[1]) - $offset;
			$polyline[] = "${x},${y}";
			$x++;
		}
		// var_dump($polyline);

		$y300 = $height - (300 - $first[1]) - $offset;
		$y400 = $height - (400 - $first[1]) - $offset;

		$y300 = "<line class=\"y300\" x1=\"0\" x2=\"${width}\" y1=\"${y300}\" y2=\"${y300}\" stroke=\"#d0d0d0\" "
			. "stroke-width=\"1\" vector-effect=\"non-scaling-stroke\" id=\"y300\" stroke-dasharray=\"5,5\"></line>";

		$y400 = "<line class=\"y400\" x1=\"0\" x2=\"${width}\" y1=\"${y400}\" y2=\"${y400}\" stroke=\"#d0d0d0\" "
			. "stroke-width=\"1\" vector-effect=\"non-scaling-stroke\"id=\"y400\" stroke-dasharray=\"5,5\"></line>";

		$xaxis = "<line x1=\"0\" x2=\"${width}\" y1=\"${height}\" y2=\"${height}\" stroke=\"none\" vector-effect=\"non-scaling-stroke\"id=\"y400\"></line>";

		ob_start(); ?>
        <svg viewBox="0 0 <?= $width; ?> <?= $height; ?>" data-height="<?= $height; ?>" class="chart2000"
             preserveAspectRatio="none"><?= $y300; ?><?= $y400; ?><?= $xaxis; ?>
        <polyline fill="none" stroke="#d97400" stroke-width="4" points="<?= implode(' ', $polyline); ?>"
                  vector-effect="non-scaling-stroke" stroke-linecap="round"></polyline></svg><?php
		return str_replace('+', ' ', urlencode(ob_get_clean()));
	}

	private static function chart20($data) {
		$currentYear = Import::getYear(new DateTimeImmutable());

		$points = array_map(function ($y) use ($currentYear, $data) {
			$year = $currentYear + $y - 19;
			return [
				'year' => $year,
				'avg' => Import::yearAvg($data, $year)
			];
		}, range(0, 19, 1)); // 20 steps

		$first = $points[0]['avg'];
		$last = end($points);
		$last = $last['avg'];
		reset($points);

		$x = 1; // start at 1 to fit on graph - needs a look
		$h = 10 + $last - $first;

		$polyline = [];
		foreach ($points as $point) {
			// $y = $height - ($point['avg'] - $first);
			$y = (($point['avg'] - $first) / $h);
			$y = $y * 100;
			$left = ((($x / 20) * 5) * 20);
			$bottom = $y;
			$tooltip = $point['year'] == $currentYear ? " title='incomplete data for current year'" : '';
			$polyline[] = "<div class=\"chart20__dot\" style=\"left:${left}%;bottom:${bottom}%\" data-avg=\"${point['avg']}\" data-year=\"${point['year']}\"${tooltip}></div>";
			$x++;
		}

		$y420 = ((420 - $first) / $h);
		$y400 = ((400 - $first) / $h);
		$y380 = ((380 - $first) / $h);

		ob_start(); ?>
        <div class="chart20">
        <div class="chart20__xaxis"></div>
        <div class="chart20__yaxis"></div>
        <div class="chart20__420" style="bottom:<?= $y420 * 100; ?>%" y="<?=$y420?>"></div>
        <div class="chart20__400" style="bottom:<?= $y400 * 100; ?>%" y="<?=$y400?>"></div>
        <div class="chart20__380" style="bottom:<?= $y380 * 100; ?>%" y="<?=$y380?>"></div>
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
		return (225 / 200) * $change * 10;

	}

	private static function save($path, $return) {
		file_put_contents(__DIR__ . '/../' . $path, $return);
		exec('npm run build');
	}
}
