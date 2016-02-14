<!doctype html>
<html lang="sv">
<head>
	<meta charset="utf-8">
	<title>Information</title>
	<meta name="description" content="Mitt lilla projekt">
	<meta http-equiv="refresh" content="60" /> <!-- Updates the whole page every 30 minutes (each 1800 second) -->
	<link rel="stylesheet" href="style.css">
   	<link rel="stylesheet" href="css/style.css">
	<link href='http://fonts.googleapis.com/css?family=Roboto:300' rel='stylesheet' type='text/css'>
    <link href='css/webfont/climacons-font.css' rel='stylesheet' type='text/css'>
	<script language="JavaScript"> <!-- Getting the current date and time and updates them every second -->
			setInterval(function() { 
				var currentTime = new Date ( );
				var currentHours = currentTime.getHours ( );   
				var currentMinutes = currentTime.getMinutes ( );
				var currentMinutesleadingzero = currentMinutes > 9 ? currentMinutes : '0' + currentMinutes; // If the number is 9 or below we add a 0 before the number.
				var currentDate = currentTime.getDate ( );
	
					var weekday = new Array(7);
					weekday[0] = "Söndag";
					weekday[1] = "Måndag";
					weekday[2] = "Tisdag";
					weekday[3] = "Onsdag";
					weekday[4] = "Torsdag";
					weekday[5] = "Fredag";
					weekday[6] = "Lördag";
				var currentDay = weekday[currentTime.getDay()]; 
	
					var actualmonth = new Array(12);
					actualmonth[0] = "Januari";
					actualmonth[1] = "Februari";
					actualmonth[2] = "Mars";
					actualmonth[3] = "April";
					actualmonth[4] = "Maj";
					actualmonth[5] = "Juni";
					actualmonth[6] = "Juli";
					actualmonth[7] = "Augusti";
					actualmonth[8] = "September";
					actualmonth[9] = "Oktober";
					actualmonth[10] = "November";
					actualmonth[11] = "December";
				var currentMonth = actualmonth[currentTime.getMonth ()];

    var currentTimeString = "<h1>" + currentHours + ":" + currentMinutesleadingzero + "</h1><h2>" + currentDay + " " + currentDate + " " + currentMonth + "</h2>";
    document.getElementById("clock").innerHTML = currentTimeString;
}, 1000);
	</script>
</head>
<body>
	<div id="wrapper">
		<div id="upper-left">
			<div id="clock"></div> <!-- Including the date/time-script -->
        	<div id="whatweather"></div>
		</div>
	<div id="upper-right">
		<?php 
			$xml_data ='<REQUEST>' .
                '<LOGIN authenticationkey="fe898a06841443beb05ea563a1ff2b8c" />' .
                '<QUERY objecttype="TrainAnnouncement" orderby="AdvertisedTimeAtLocation">' .
                '<FILTER>' .
                '<AND>' .
                '<EQ name="ActivityType" value="Avgang" />' .
                '<EQ name="LocationSignature" value="Nkv" />' .
                '<EQ name="ToLocation" value="Cst" />' .
                '<OR>' .
                '<AND>' .
                '<GT name="AdvertisedTimeAtLocation" value="$dateadd(-00:15:00)" />' .
                '<LT name="AdvertisedTimeAtLocation" value="$dateadd(15:00:00)" />' .
                '</AND>' .
                '<AND>' .
                    '<LT name="AdvertisedTimeAtLocation" value="$dateadd(00:30:00)" />' .
                    '<GT name="EstimatedTimeAtLocation" value="$dateadd(-00:15:00)" />' .
                '</AND>' .
                '</OR>' .
                '</AND>' .
                '</FILTER>' .
                '<INCLUDE>AdvertisedTrainIdent</INCLUDE>' .
                '<INCLUDE>AdvertisedTimeAtLocation</INCLUDE>' .
                '<INCLUDE>EstimatedTimeAtLocation</INCLUDE>' .
                '<INCLUDE>EstimatedTimeIsPreliminary</INCLUDE>' .
                '<INCLUDE>ToLocation</INCLUDE>' .
                '<INCLUDE>Canceled</INCLUDE>' .
                '</QUERY>' .
                '</REQUEST>';


			$URL = "http://api.trafikinfo.trafikverket.se/v1/data.xml";
 
			$ch = curl_init($URL);
			curl_setopt($ch, CURLOPT_MUTE, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml_data");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);
		
//			print htmlspecialchars($output);
			$oXML = new SimpleXMLElement($output);?>
			<table>
			<?php foreach($oXML->RESULT->TrainAnnouncement as $res):
				$info = "";
				$estTimeParts = "";
				$time = explode("T", $res->AdvertisedTimeAtLocation);
				$timeParts = explode(":", $time[1]);
				if (strlen($res->EstimatedTimeAtLocation) > 0) {
	                $info = "Delayed";
                    $estTime = $res->EstimatedTimeAtLocation;
					$estTime = explode("T", $res->EstimatedTimeAtLocation);
					$estTimeParts = explode(":", $estTime[1]);
					$est = $estTimeParts[0] . ":" . $estTimeParts[1];
                }
				if ($res->EstimatedTimeIsPreliminary == "true") {
					$info = "Preliminary";
				}
				if ($res->Canceled == "true") {
					$info = "Canceled";
				}
				?>           
        			<tr>                    
                    	<td ><?php echo $info;?></td>
                        <td ><?php echo $est;?></td>
                        <td ><?php echo $res->AdvertisedTrainIdent;?></td>
		            	<td><?php echo $timeParts[0] . ":" . $timeParts[1]; ?></td>
        			</tr>
		<?php endforeach;?>
        	</table>
	</div>
</div>
</body>
</html>

<script type="text/javascript" src="js/jquery-2.2.0.min.js"></script>
<script type="text/javascript" src="js/mustache.js"></script>
<script type="text/javascript" src="js/whatweather-1.2.js"></script>
<script type="text/javascript">
    $("div#whatweather").whatWeather({city:"Nykvarn,Sweden", days:"5"});
</script>
