<?php

	$db = mysql_connect("localhost","datalogger","datalogger") or die("DB Connect error");
	mysql_select_db("datalogger");

	$q = "SELECT humidity FROM datalogger where sensor = 8 ORDER BY date_time DESC LIMIT 1";
	$ds = mysql_query($q);
	$humiditySensor=(int)mysql_fetch_object($ds)->humidity;


	/* used to execute a python script
	 * $command = escapeshellcmd(' /usr/custom/test.py');
	* $output = shell_exec($command);
	* echo $output
	*/

	// grap weather information from openweathermap.org
	$city = "Chepo";
	$country = "PA"; // two digit country code

	$url = "http://api.openweathermap.org/data/2.5/weather?q=".$city.",".$country."&units=metric&cnt=7&lang=en";
	$json = file_get_contents($url);
	$data = json_decode($json,true);
	//get humidity
	echo $data['main']['humidity']."<br>";


	//change threshold depening on time of day
	$humidityThreshold;
	$humidityNight = 80.0;
	$humidityDay = 95.0;
	$windTime;

	$t = time();
	$curentTime = date('H:i');
	$morningTime = ('10:00');
	$eveningTime = ('22:00');

	//set day or nighttime humidity
	if (($curentTime < $morningTime) or ($curentTime > $eveningTime))
		{
			$humidityThreshold = $humidityNight;
		}
		else
		{
			$humidityThreshold = $humidityDay;
		}


	//change power state of fan depending on current humidity
	if ($humiditySensor > $humidityThreshold)
		{
			//wind depending on how much humidity is over our limit
			$windTime = (60 / (100-$humidityThreshold)*($humiditySensor-$humidityThreshold));
			//let it wind
			exec('/usr/local/bin/gpio mode 5 out');
			exec('/usr/local/bin/gpio write 5 1');

			//time till wind stops
			sleep ($windTime);
			exec('/usr/local/bin/gpio mode 5 out');
			exec('/usr/local/bin/gpio write 5 0');
		}

	mysql_query($q);
	mysql_close($db);
?>
