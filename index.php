<?php
require_once('common/google.php');


// Start session
$lifetime = 60 * 60 * 24 * 30; // 30 days in seconds
ini_set('session.gc_maxlifetime', $lifetime); // Set lifetime of session on server
session_set_cookie_params($lifetime); // Set lifetime of session on server
session_start();
session_regenerate_id(true); // Regenerated to enhance security


// Set dates
$dayOne = date('Y-m-d');
$dayTwo = date('Y-m-d', strtotime('+1 day'));
$dayThree = date('Y-m-d', strtotime('+2 days'));
$dayFour = date('Y-m-d', strtotime('+3 days'));
$dayFive = date('Y-m-d', strtotime('+4 days'));
$daySix = date('Y-m-d', strtotime('+5 days'));
$daySeven = date('Y-m-d', strtotime('+6 days'));


// Events
$calendars = Google::GetCalendars();
$events = array();
foreach($calendars as $cid => $c) {
	$eventList = Google::GetEvents(
		$c, 
		date('c', strtotime($dayOne . 'T00:00:00')), 
		date('c', strtotime($daySeven . 'T23:59:59'))
	);
	if (count($eventList) > 0) {
		$events = array_merge($events, $eventList);
	}
}


// Weather
$apikey = file('/var/login/openweather.txt');
$apikey = rtrim(trim($apikey[0]));

$coordinates = file('/var/login/coordinates.txt');
$lat = rtrim(trim($coordinates[0]));
$lon = rtrim(trim($coordinates[1]));
$units = 'imperial';

// Current Conditions
$onecallURL = "https://api.openweathermap.org/data/3.0/onecall?lat=$lat&lon=$lon&appid=$apikey&units=$units";
$forecast = json_decode(file_get_contents($onecallURL), true);

$sunrise = $forecast['current']['sunrise'];
$sunset = $forecast['current']['sunset'];

// 5 Day Forecast
$dayKeys = array_column($forecast['daily'], 'dt');
foreach($dayKeys as $key => $dk) {
	$dayKeys[$key] = date('Y-m-d', $dk);
}

$labels = array();
$data = array();
$hours = array();
$i = 0;

foreach($forecast['hourly'] as $l) {
	$i++;
	if ($i <= 16) {
		$sunrise = $forecast['daily'][array_search(date('Y-m-d', $l['dt']), $dayKeys)]['sunrise'];
		$sunset = $forecast['daily'][array_search(date('Y-m-d', $l['dt']), $dayKeys)]['sunset'];

		$labels[] = $l['dt'];
		$data[] = round($l['temp']);
		$hours[] = array(
			'dt' => $l['dt'],
			'hour' => date('ga', $l['dt']),
			'sunrise' => $sunrise,
			'sunset' => $sunset,
			'icon' => $l['weather'][0]['main'],
			'temp' => round($l['temp'])
		);
	}
}
$chartMin = min($data) - 1;
$chartMax = max($data) + 1;
$labels = "'" . implode("','", $labels) . "'";
$data = "'" . implode("','", $data) . "'";

$dayOneHigh = round($forecast['daily'][0]['temp']['max']);
$dayOneLow = round($forecast['daily'][0]['temp']['min']);
$dayOneForecast = forecastIcon($forecast['daily'][0]['weather'][0]['main']);

$dayTwoHigh = round($forecast['daily'][1]['temp']['max']);
$dayTwoLow = round($forecast['daily'][1]['temp']['min']);
$dayTwoForecast = forecastIcon($forecast['daily'][1]['weather'][0]['main']);

$dayThreeHigh = round($forecast['daily'][2]['temp']['max']);
$dayThreeLow = round($forecast['daily'][2]['temp']['min']);
$dayThreeForecast = forecastIcon($forecast['daily'][2]['weather'][0]['main']);

$dayFourHigh = round($forecast['daily'][3]['temp']['max']);
$dayFourLow = round($forecast['daily'][3]['temp']['min']);
$dayFourForecast = forecastIcon($forecast['daily'][3]['weather'][0]['main']);

$dayFiveHigh = round($forecast['daily'][4]['temp']['max']);
$dayFiveLow = round($forecast['daily'][4]['temp']['min']);
$dayFiveForecast = forecastIcon($forecast['daily'][4]['weather'][0]['main']);

$daySixHigh = round($forecast['daily'][5]['temp']['max']);
$daySixLow = round($forecast['daily'][5]['temp']['min']);
$daySixForecast = forecastIcon($forecast['daily'][5]['weather'][0]['main']);

$daySevenHigh = round($forecast['daily'][6]['temp']['max']);
$daySevenLow = round($forecast['daily'][6]['temp']['min']);
$daySevenForecast = forecastIcon($forecast['daily'][6]['weather'][0]['main']);

function forecastIcon($i, $time = NULL, $sunrise = NULL, $sunset = NULL) {
	$i = strtoupper($i);
	$icon = '';

	$night = false;
	if (isset($time) && isset($sunrise) && isset($sunset)
	&& ($time < $sunrise || $time > $sunset)) {
		$night = true;
	}

	// Sets icon url
	if ($i == "CLEAR" && !$night) {
		$icon = "weather_icons/clear.png";
	} elseif ($i == "CLEAR" && $night) {
		$icon = "weather_icons/nt_clear.png";
	} elseif ($i == "RAIN") {
		$icon = "weather_icons/rain.png";
	} elseif ($i == "SNOW") {
		$icon = "weather_icons/snow.png";
	} elseif ($i == "SLEET") {
		$icon = "weather_icons/rain.png";
	} elseif ($i == "WIND") {
		$icon = "weather_icons/clear.png";
	} elseif ($i == "FOG") {
		$icon = "weather_icons/fog.png";
	} elseif ($i == "CLOUDS") {
		$icon = "weather_icons/mostlycloudy.png";
	} elseif ($i == "PARTLY-CLOUDY" && !$night) {
		$icon = "weather_icons/partlycloudy.png";
	} elseif ($i == "PARTLY-CLOUDY" && $night) {
		$icon = "weather_icons/cloudy.png";
	}
	
	return $icon;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
	<!-- Fonts -->
    <link rel="stylesheet" href="https://use.typekit.net/mbu5daj.css">
	<!-- Stylesheets -->
	<link href="css/Mirror.css" rel="stylesheet">
</head>
<body>
	<div id="timeDate">
		<h2 id="time"></h2>
		<h4 id="date"></h4>
	</div>
	<h2 id="greeting"></h2>
	<div id="weeklyForecast">
		<div class="dailyForecast">
			<h3 id="weekdayOne" class="weekdayName"></h3>
			<div class="dayForecast">
				<img class="weeklyIcon" src="<?=$dayOneForecast;?>" />
				<h4 class="tempHigh"><?=$dayOneHigh;?>&deg;</h4>
				<h4 class="tempLow"><?=$dayOneLow;?>&deg;</h4>
			</div>
			<div class="calendar">
			<?
			if (count(array_keys(array_column($events, 'dayOf'), $dayOne)) > 0) {
				foreach($events as $e) {
					if (date('Y-m-d', strtotime($e['startDate'])) == $dayOne) {
						$time = date('g:i A', strtotime($e['startDate'])) . ' - ' . date('g:i A', strtotime($e['endDate']));
						if (date('H:i', strtotime($e['startDate'])) == '00:00'
						&& date('H:i', strtotime($e['endDate']))) {
							$time = '';
						}
						?>
						<div class="calendarItem" style="background-color: <?=$e['backgroundColor'];?>">
							<h4><?=$e['summary'];?></h4>
							<h5><?=$time;?></h5>
						</div>
						<?
					}
				}
			} else {
				?>
				<div class="calendar">
					<h5 class="noEvents">No Events Today</h5>
				</div>
				<?
			}
			?>
			</div>
		</div>
		<div class="dailyForecast">
			<h3 id="weekdayTwo" class="weekdayName"></h3>
			<div class="dayForecast">
				<img class="weeklyIcon" src="<?=$dayTwoForecast;?>" />
				<h4 class="tempHigh"><?=$dayTwoHigh;?>&deg;</h4>
				<h4 class="tempLow"><?=$dayTwoLow;?>&deg;</h4>
			</div>
			<div class="calendar">
			<?
			if (count(array_keys(array_column($events, 'dayOf'), $dayTwo)) > 0) {
				foreach($events as $e) {
					if (date('Y-m-d', strtotime($e['startDate'])) == $dayTwo) {
						$time = date('g:i A', strtotime($e['startDate'])) . ' - ' . date('g:i A', strtotime($e['endDate']));
						if (date('H:i', strtotime($e['startDate'])) == '00:00'
						&& date('H:i', strtotime($e['endDate']))) {
							$time = '';
						}
						?>
						<div class="calendarItem" style="background-color: <?=$e['backgroundColor'];?>">
							<h4><?=$e['summary'];?></h4>
							<h5><?=$time;?></h5>
						</div>
						<?
					}
				}
			} else {
				?>
				<div class="calendar">
					<h5 class="noEvents">No Events Today</h5>
				</div>
				<?
			}
			?>
			</div>
		</div>
		<div class="dailyForecast">
			<h3 id="weekdayThree" class="weekdayName"></h3>
			<div class="dayForecast">
				<img class="weeklyIcon" src="<?=$dayThreeForecast;?>" />
				<h4 class="tempHigh"><?=$dayThreeHigh;?>&deg;</h4>
				<h4 class="tempLow"><?=$dayThreeLow;?>&deg;</h4>
			</div>
			<div class="calendar">
			<?
			if (count(array_keys(array_column($events, 'dayOf'), $dayThree)) > 0) {
				foreach($events as $e) {
					if (date('Y-m-d', strtotime($e['startDate'])) == $dayThree) {
						$time = date('g:i A', strtotime($e['startDate'])) . ' - ' . date('g:i A', strtotime($e['endDate']));
						if (date('H:i', strtotime($e['startDate'])) == '00:00'
						&& date('H:i', strtotime($e['endDate']))) {
							$time = '';
						}
						?>
						<div class="calendarItem" style="background-color: <?=$e['backgroundColor'];?>">
							<h4><?=$e['summary'];?></h4>
							<h5><?=$time;?></h5>
						</div>
						<?
					}
				}
			} else {
				?>
				<div class="calendar">
					<h5 class="noEvents">No Events Today</h5>
				</div>
				<?
			}
			?>
			</div>
		</div>
		<div class="dailyForecast">
			<h3 id="weekdayFour" class="weekdayName"></h3>
			<div class="dayForecast">
				<img class="weeklyIcon" src="<?=$dayFourForecast;?>" />
				<h4 class="tempHigh"><?=$dayFourHigh;?>&deg;</h4>
				<h4 class="tempLow"><?=$dayFourLow;?>&deg;</h4>
			</div>
			<div class="calendar">
			<?
			if (count(array_keys(array_column($events, 'dayOf'), $dayFour)) > 0) {
				foreach($events as $e) {
					if (date('Y-m-d', strtotime($e['startDate'])) == $dayFour) {
						$time = date('g:i A', strtotime($e['startDate'])) . ' - ' . date('g:i A', strtotime($e['endDate']));
						if (date('H:i', strtotime($e['startDate'])) == '00:00'
						&& date('H:i', strtotime($e['endDate']))) {
							$time = '';
						}
						?>
						<div class="calendarItem" style="background-color: <?=$e['backgroundColor'];?>">
							<h4><?=$e['summary'];?></h4>
							<h5><?=$time;?></h5>
						</div>
						<?
					}
				}
			} else {
				?>
				<div class="calendar">
					<h5 class="noEvents">No Events Today</h5>
				</div>
				<?
			}
			?>
			</div>
		</div>
		<div class="dailyForecast">
			<h3 id="weekdayFive" class="weekdayName"></h3>
			<div class="dayForecast">
				<img class="weeklyIcon" src="<?=$dayFiveForecast;?>" />
				<h4 class="tempHigh"><?=$dayFiveHigh;?>&deg;</h4>
				<h4 class="tempLow"><?=$dayFiveLow;?>&deg;</h4>
			</div>
			<div class="calendar">
			<?
			if (count(array_keys(array_column($events, 'dayOf'), $dayFive)) > 0) {
				foreach($events as $e) {
					if (date('Y-m-d', strtotime($e['startDate'])) == $dayFive) {
						$time = date('g:i A', strtotime($e['startDate'])) . ' - ' . date('g:i A', strtotime($e['endDate']));
						if (date('H:i', strtotime($e['startDate'])) == '00:00'
						&& date('H:i', strtotime($e['endDate']))) {
							$time = '';
						}
						?>
						<div class="calendarItem" style="background-color: <?=$e['backgroundColor'];?>">
							<h4><?=$e['summary'];?></h4>
							<h5><?=$time;?></h5>
						</div>
						<?
					}
				}
			} else {
				?>
				<div class="calendar">
					<h5 class="noEvents">No Events Today</h5>
				</div>
				<?
			}
			?>
			</div>
		</div>
		<div class="dailyForecast">
			<h3 id="weekdaySix" class="weekdayName"></h3>
			<div class="dayForecast">
				<img class="weeklyIcon" src="<?=$daySixForecast;?>" />
				<h4 class="tempHigh"><?=$daySixHigh;?>&deg;</h4>
				<h4 class="tempLow"><?=$daySixLow;?>&deg;</h4>
			</div>
			<div class="calendar">
			<?
			if (count(array_keys(array_column($events, 'dayOf'), $daySix)) > 0) {
				foreach($events as $e) {
					if (date('Y-m-d', strtotime($e['startDate'])) == $daySix) {
						$time = date('g:i A', strtotime($e['startDate'])) . ' - ' . date('g:i A', strtotime($e['endDate']));
						if (date('H:i', strtotime($e['startDate'])) == '00:00'
						&& date('H:i', strtotime($e['endDate']))) {
							$time = '';
						}
						?>
						<div class="calendarItem" style="background-color: <?=$e['backgroundColor'];?>">
							<h4><?=$e['summary'];?></h4>
							<h5><?=$time;?></h5>
						</div>
						<?
					}
				}
			} else {
				?>
				<div class="calendar">
					<h5 class="noEvents">No Events Today</h5>
				</div>
				<?
			}
			?>
			</div>
		</div>
		<div class="dailyForecast">
			<h3 id="weekdaySeven" class="weekdayName"></h3>
			<div class="dayForecast">
				<img class="weeklyIcon" src="<?=$daySevenForecast;?>" />
				<h4 class="tempHigh"><?=$daySevenHigh;?>&deg;</h4>
				<h4 class="tempLow"><?=$daySevenLow;?>&deg;</h4>
			</div>
			<div class="calendar">
			<?
			if (count(array_keys(array_column($events, 'dayOf'), $daySeven)) > 0) {
				foreach($events as $e) {
					if (date('Y-m-d', strtotime($e['startDate'])) == $daySeven) {
						$time = date('g:i A', strtotime($e['startDate'])) . ' - ' . date('g:i A', strtotime($e['endDate']));
						if (date('H:i', strtotime($e['startDate'])) == '00:00'
						&& date('H:i', strtotime($e['endDate']))) {
							$time = '';
						}
						?>
						<div class="calendarItem" style="background-color: <?=$e['backgroundColor'];?>">
							<h4><?=$e['summary'];?></h4>
							<h5><?=$time;?></h5>
						</div>
						<?
					}
				}
			} else {
				?>
				<div class="calendar">
					<h5 class="noEvents">No Events Today</h5>
				</div>
				<?
			}
			?>
			</div>
		</div>
	</div>
	<div id="hourlyForecast">
		<div id="hours">
		<?
		foreach($hours as $h) {
			?>
			<div class="hour">
				<h4><?=$h['hour'];?></h4>
				<img class="hourlyIcon" src="<?=forecastIcon($h['icon'], $h['dt'], $h['sunrise'], $h['sunset']);?>" />
				<h4><?=$h['temp'];?>&deg;</h4>
			</div>
			<?
		}
		?>
		</div>
		<div id="hourlyChart"><canvas style="width: 100%; height: 13vh;"></canvas></div>
	</div>
	<!-- Javascript -->
	<script src="js/jQuery_v3.4.1.js"></script>
	<script src="/js/chart.js"></script>
	<script src="js/mirror.js"></script>
	<script>
		function setTextColorBasedOnBackground(obj) {
			const bgColor = $(obj).css('background-color');
            const rgb = bgColor.match(/\d+/g);
            const brightness = Math.round(((parseInt(rgb[0]) * 299) +
                                          (parseInt(rgb[1]) * 587) +
                                          (parseInt(rgb[2]) * 114)) / 1000);

            $(obj).css('color', (brightness > 125) ? 'black' : 'white');
        }

		$(document).ready(function() {
			var ctx = $('#hourlyChart canvas');
			var chart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: [<?=$labels;?>],
					datasets: [{
						label: ['Tempature'],
						data: [<?=$data;?>],
						backgroundColor: ['rgba(0,100,150,1)'],
						borderWidth: 1,
						borderColor: ['rgba(255,255,255,0)'],
						borderJoinStyle: 'round',
						fill: true,
						cubicInterpolationMode: 'monotone'
					}]
				},
				options: {
					responsive: true,
					showXLabels: 15,
					layout: {
						padding: 0
					},
					elements: {
						point: {
							radius: 0,
							hoverRadius: 0
						}
					},
					plugins: {
						legend: {
							display: false
						}
					},
					scales: {
						y: {
							ticks: {
								display: false,
								backdropPadding: 0
							},
							grid: {
								display: false
							},
							min: <?=$chartMin;?>,
							max: <?=$chartMax;?>    
						},
						x: {
							ticks: {
								display: false,
								backdropPadding: 0
							},
							grid: {
								display: false
							}
						}
					}
				}
			});

			$('.calendarItem').each(function(i, obj) {
				setTextColorBasedOnBackground(obj);
			});
		});
	</script>
</body>
</html>
