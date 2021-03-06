﻿<!doctype html>
<html lang="sv">
<head>
	<meta charset="utf-8">
	<title>Information</title>
	<meta name="description" content="Mitt lilla projekt">
	<meta http-equiv="refresh" content="60" />
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
<script language="JavaScript"> <!-- Getting the current date and time and updates them every second -->
			function getSnapshotTime()
			{ 
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

			    var lastUpdateTime = "<h3>" + currentHours + ":" + currentMinutesleadingzero + currentDay + " " + currentDate + " " + currentMonth + "</h3>";
			    document.getElementById("lastUpdateClock").innerHTML = lastUpdateTime;
			};
</script>
</head>
<body>
	<div id="wrapper">
		<div id="upper-left">
			<div id="clock"></div> <!-- Including the date/time-script -->        	
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
                    $est = "";
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
        <div id="middle">
        <?php 
			$URL = 'https://calendar.google.com/calendar/ical/s9u986ltvf97tqpkbbllmsvad0%40group.calendar.google.com/private-c3fe30e6535e940bc29f10d237683a74/basic.ics';
			require 'class.iCalReader.php';
			$myfile = file_get_contents($URL);
			$myfile = str_replace("\n ", "", $myfile);
			$myfile = str_replace("&", " and ", $myfile);
			$mylines = split("\n", $myfile);
			$ical = new ICal($mylines);
			$events = $ical->events();	
			$events= $ical->processRecurrences();
			$Date = date('Y-m-d');
			$events = $ical->eventsFromRange(strtotime($Date. ' -2 days'), strtotime($Date. ' + 1 week'));	
			$weekday = Array("Söndag", "Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag");
			?>

		    <table>
            	<tr>
                	<?php $first=explode("T", $events[0]['DTSTART']);?>
                	<?php $sec=explode("T", $events[1]['DTSTART']);?>
                	<?php $third=explode("T", $events[2]['DTSTART']);?>
                    <?php $forth=explode("T", $events[3]['DTSTART']);?>
                    <?php $fifth=explode("T", $events[4]['DTSTART']);?>
                    <?php $six=explode("T", $events[5]['DTSTART']);?>

	                <th><?php echo date('l', strtotime($first[0])); ?></th>
                    <th><?php echo date('l', strtotime($sec[0])); ?></th>
                    <th><?php echo date('l', strtotime($third[0])); ?></th>
                    <th><?php echo date('l', strtotime($forth[0])); ?></th>
                    <th><?php echo date('l', strtotime($fifth[0])); ?></th>
                    <th><?php echo date('l', strtotime($six[0])); ?></th>
                </tr>
				<tr>
                   	<td><?php echo date('M d', strtotime($first[0]));?></td>
                    <td><?php echo date('M d', strtotime($sec[0]));?></td>
                    <td><?php echo date('M d', strtotime($third[0]));?></td>
                    <td><?php echo date('M d', strtotime($forth[0]));?></td>
                    <td><?php echo date('M d', strtotime($fifth[0]));?></td>
                    <td><?php echo date('M d', strtotime($six[0]));?></td>
                </tr>
				<tr>
                   	<td><?php echo $events[0]['SUMMARY']?></td>
                    <td><?php echo $events[1]['SUMMARY']?></td>
                    <td><?php echo $events[2]['SUMMARY']?></td>
                    <td><?php echo $events[3]['SUMMARY']?></td>
                    <td><?php echo $events[4]['SUMMARY']?></td>
                    <td><?php echo $events[5]['SUMMARY']?></td>                                    
                </tr>
				<tr>
                   	<td><?php echo $events[0]['LOCATION']?></td>
                    <td><?php echo $events[1]['LOCATION']?></td>
                    <td><?php echo $events[2]['LOCATION']?></td>
                    <td><?php echo $events[3]['LOCATION']?></td>
                    <td><?php echo $events[4]['LOCATION']?></td>
                    <td><?php echo $events[5]['LOCATION']?></td>                                     
                </tr>
            </table>
        </div>
        <div id="bottom-left"> 
			<?php // Code for getting the RSS-news-feed
			$rss = new DOMDocument();
			$rss->load('http://feeds.idg.se/idg/vzzs'); // Specify the address to the feed
			$feed = array();
				foreach ($rss->getElementsByTagName('item') as $node) {
					$item = array (
					'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
					'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
					'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
					);
				array_push($feed, $item);
				}
   
			$limit = 1; // Number of posts to be displayed
			for($x=0;$x<$limit;$x++) {
				$title = str_replace(' & ', ' &amp; ', $feed[$x]['title']);
				$description = $feed[$x]['desc'];
				$date = date('j F', strtotime($feed[$x]['date']));
				echo '<h2 class="smaller">'.$title.'</h2>';
				echo '<p class="date">'.$date.'</p>';
				echo '<p>'.strip_tags($description, '<p><b>');
			}
			?>
        </div>
   		<div id="bottom-right">
			<div id="lastUpdateClock"></div>
		</div>
    </div>
</body>
</html>
<script type="text/javascript" src="js/jquery-2.2.0.min.js"></script>
<script type="text/javascript" src="js/mustache.js"></script>