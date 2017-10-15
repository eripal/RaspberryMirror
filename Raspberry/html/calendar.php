<?php
/* iCal Parser - Some PHP functions to parse an iCal calendar into a usable PHP object.
 *               Also convert the object into pretty HTML.
 *
 * Copyright (C) 2006 Adam Wolfe Gordon
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Blatantly stolen from http://ca3.php.net/manual/en/function.xml-parse-into-struct.php
// Convert xml into a nice object
class XmlElement {
  var $name;
  var $attributes;
  var $content;
  var $children;
};

function xml_to_object($xml) {
  $parser = xml_parser_create();
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
  xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
  xml_parse_into_struct($parser, $xml, $tags);
  xml_parser_free($parser);
  
  $elements = array();  // the currently filling [child] XmlElement array
  $stack = array();
  foreach ($tags as $tag) {
    $index = count($elements);
    if ($tag['type'] == "complete" || $tag['type'] == "open") {
      $elements[$index] = new XmlElement;
      $elements[$index]->name = $tag['tag'];
      $elements[$index]->attributes = $tag['attributes'];
      $elements[$index]->content = $tag['value'];
      if ($tag['type'] == "open") {  // push
	$elements[$index]->children = array();
	$stack[count($stack)] = &$elements;
	$elements = &$elements[$index]->children;
      }
    }
    if ($tag['type'] == "close") {  // pop
      $elements = &$stack[count($stack) - 1];
      unset($stack[count($stack) - 1]);
    }
  }
  return $elements[0];  // the single top-level element
}

// Not stolen
// Fetch text from an iCal URL and convert it into xml
function ical2xml($URL) {
  $myfile = file_get_contents($URL);
  
  $myfile = str_replace("\n ", "", $myfile);
  $myfile = str_replace("&", " and ", $myfile);
  $mylines = split("\n", $myfile);
  
  $xml = "";	
  foreach($mylines as $line) {
    if($line == "")
      continue;
    
    list($key,$val) = split(":", $line);
    $key = trim($key, "\r\n");
    $val = trim($val, "\r\n");
    $val = str_replace("\n", "", $val);
    $val = str_replace("\r", "", $val);
    
    $attribs = split(";", $key);
    $key = $attribs[0];
    $attribs = array_slice($attribs, 1);
    
    $myattribs = " ";
    foreach($attribs as $attrib) {
      list($att, $attval) = split("=", $attrib);
      $myattribs .= "$att=\"$attval\" ";
    }
    $myattribs = rtrim($myattribs);
    
    // BEGIN and END keywords
    if($key == 'BEGIN') {
      $xml .= "<$val$myattribs>\n";
    } else if($key == 'END') {
      $xml .= "</$val>\n";
    } else {
      $xml .= "<$key$myattribs>$val</$key>\n";
    }
  }
  
  return $xml;
}

// Event class
class event {
  var $starttime;
  var $endtime;
  var $allday;
  var $rrulefreq;
  var $rruledays;
  var $rruleuntil;
  var $description;
  var $location;
  var $summary;
};

// Calendar class
class calendar {
  var $name;
  var $timezone;
  var $description;
  var $events = array();
};

// Timezone class
class timezone {
  var $namestd;
  var $namedst;
  var $offsetstd;
  var $offsetdst;
};

// Convert a time in iCal's ugly format to a unix timestamp
function ical_time_to_timestamp($time) {
  $hour = substr($time, 9, 2);
  if($hour == "")
    $hour = 0;
  $min = substr($time, 11, 2);
  if($min == "")
    $min = 0;
  $sec =  substr($time, 13, 2);
  if($sec == "")
    $sec = 0;
  $mon = substr($time, 4, 2);
  $day = substr($time, 6, 2);
  $year = substr($time, 0, 4);
  return mktime($hour, $min, $sec, $mon, $day, $year);
}

// The day the world ends (for Unix users)
$endoftime = ical_time_to_timestamp("20380118T000000");

// Event comparison function for sorting
function cmp($a, $b) {
  if(!(is_object($a) && is_object($b)))
    return 0;
  if($a->starttime == $b->starttime)
    return 0;
  return ($a->starttime < $b->starttime) ? -1 : 1;
}

// Convert XML into a calendar object
function xml_to_calendar($xml) {
  $mycal = new calendar;
  $eventcount = 0;
  
  $thecal = xml_to_object($xml);
  
  foreach($thecal->children as $child) {
    switch($child->name) {
    case "X-WR-CALNAME":
      $mycal->name = $child->content;
      break;
    case "X-WR-CALDESC":
      $mycal->description = $child->content;
      break;
    case "VTIMEZONE":
      $mycal->timezone = new timezone;
      foreach($child->children as $data) {
	switch($data->name) {
	case "STANDARD":
	  foreach($data->children as $sec) {
	    switch($sec->name) {
	    case "TZNAME":
	      $mycal->timezone->namestd = $sec->content;
	      break;
	    case "TZOFFSETTO":
	      $mycal->timezone->offsetstd = $sec->content;
	      break;
	    default:
	      break;
	    }
	  }
	  break;
	case "DAYLIGHT":
	  foreach($data->children as $sec) {
	    switch($sec->name) {
	    case "TZNAME":
	      $mycal->timezone->namedst = $sec->content;
	      break;
	    case "TZOFFSETTO":
	      $mycal->timezone->offsetdst = $sec->content;
	      break;
	    default:
	      break;
	    }
	  }
	  break;
	default:
	  break;
	}
      }
      break;
    case "VEVENT":
      $i = $eventcount;
      $mycal->events[$i] = new event;
      $mycal->events[$i]->allday = FALSE;
      $eventcount++;
      foreach($child->children as $data) {
	switch($data->name) {
	case "DTSTART":
	  if(substr($data->content, 9, 6) == "") {
	    $mycal->events[$i]->allday = TRUE;
	    $mycal->events[$i]->starttime = ical_time_to_timestamp($data->content . "T000000");
	  } else
	    $mycal->events[$i]->starttime = ical_time_to_timestamp($data->content);
	  break;
	case "DTEND":
	  if(substr($data->content, 9, 6) == "") {
	    $mycal->events[$i]->allday = TRUE;
	    $mycal->events[$i]->endtime = ical_time_to_timestamp($data->content . "T000000");
	  } else
	    $mycal->events[$i]->endtime = ical_time_to_timestamp($data->content);
	  break;
	case "DURATION":
	  $mycal->events[$i]->endtime = $mycal->events[$i]->starttime + substr($data->content, 2, strlen($data->content) - 1);
	  break;
	case "RRULE":
	  $myrrule = split(";", $data->content);
	  // Unless otherwise specified, repeating events go until the end of time
	  $mycal->events[$i]->rruleuntil = $endoftime;
	  foreach($myrrule as $part) {
	    list($a, $b) = split("=", $part);
	    switch($a) {
	    case "FREQ":
	      $mycal->events[$i]->rrulefreq = $b;
	      break;
	    case "BYDAY":
	      $mycal->events[$i]->rruledays = $b;
	      break;
	    case "UNTIL":
	      $mycal->events[$i]->rruleuntil = ical_time_to_timestamp($b);
	      break;
	    default:
	      break;
	    }
	  }
	  break;
	case "DESCRIPTION":
	  $mycal->events[$i]->description = $data->content;
	  break;
	case "LOCATION":
	  $mycal->events[$i]->location = $data->content;
	  break;
	case "SUMMARY":
	  $mycal->events[$i]->summary = $data->content;
	  break;
	default:
	  break;
	}
      }
      break;
    default:
      break;
    }
  }

  // Sort events by start date/time
  usort($mycal->events, "cmp");
  
  return $mycal;
}

// Take in an iCal object and spit out pretty HTML.
function objToHTML($thecal) {
  // Days of the week
  $shortdays = array(	"MO"=>"Monday",
			"TU"=>"Tuesday",
			"WE"=>"Wednesday",
			"TH"=>"Thursday",
			"FR"=>"Friday",
			"SA"=>"Saturday",
			"SU"=>"Sunday"
			);

  // Global variables from the config file
  global $timeformat;
  global $dateformat;
  global $noyearformat;
  global $nodayformat;
  global $xthday;

  $nc = "<ul>";
  foreach($thecal->VEVENT as $event) {
    if($event->DTSTART > time() || ($event->rrulefreq && (time() < $event->rruleuntil || $event->rruleuntil == $endoftime))) {
      $nc .= "<li><b>" . $event->summary . "</b><br />\n";
      
      if($event->rrulefreq == "WEEKLY") {
	$days = str_replace(",", ", ", $event->rruledays);
	$repdays = split(",", $event->rruledays);
	foreach($repdays as $repday) {
	  $days = str_replace($repday, $shortdays[$repday], $days);
	}
	
	$prefix = "Every week on $days";
	
	if($event->starttime > time()) {
	  if($event->allday == FALSE) {
	    $fromto = " starting " . date($dateformat, $event->starttime) . " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	  } else {
	    $fromto = " all day starting " . date($dateformat, $event->starttime);
	  }
	} else {
	  if($event->allday == FALSE) {
	    $fromto = " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	  } else {
	    $fromto = ", all day";
	  }
	}
	
	if($event->rruleuntil != $endoftime) {
	  $until = " until " . date($dateformat, $event->rruleuntil);
	} else {
	  $until = "";
	}
      } else if($event->rrulefreq == "MONTHLY") {
	$prefix = "The " . date($xthday, $event->starttime) . " day of each month";
	
	if($event->starttime > time()) {
	  if($event->allday == FALSE) {
	    $fromto = " starting " . date($nodayformat, $event->starttime) . " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	  } else {
	    $fromto = " starting " . date($nodayformat, $event->starttime);
	  }
	} else {
	  if($event->allday == FALSE) {
	    $fromto = " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	  } else {
	    $fromto = ", all day";
	  }
	}
	
	if($event->rruleuntil != $endoftime) {
	  $until = " until " . date($nodayformat, $event->rruleuntil);
	} else {
	  $until = "";
	}
      } else if($event->rrulefreq == "DAILY") {
	$prefix = "Every day";
	
	if($event->starttime > time()) {
	  if($event->allday == FALSE) {
	    $fromto = " starting " . date($dateformat, $event->starttime) . " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	  } else {
	    $fromto = " all day starting " . date($dateformat, $event->starttime);
	  }
	} else {
	  if($event->allday == FALSE) {
	    $fromto = " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	  } else {
	    $fromto = " all day";
	  }
	}
	
	if($event->rruleuntil != $endoftime) {
	  $until = " until " . date($dateformat, $event->rruleuntil);
	} else {
	  $until = "";
	}
      } else if($event->rrulefreq == "YEARLY") {
	$prefix = "Annually on " . date($noyearformat, $event->starttime);
	
	if($event->allday == FALSE) {
	  $fromto = " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	} else {
	  $fromto = ", all day";
	}
	
	if($event->rruleuntil != $endoftime) {
	  $until = " until " . date("Y", $event->rruleuntil);
	} else {
	  $until = "";
	}
      } else {
	$prefix = date($dateformat, $event->starttime);
	
	if($event->allday == FALSE) {
	  $fromto = " from " . date($timeformat, $event->starttime) . " to " . date($timeformat, $event->endtime);
	} else {
	  $fromto = " all day";
	}
	
	$until = "";
      }
      
      $nc .= $prefix . $fromto . $until . "<br />\n";
      
      if($event->location)
	$nc .= $event->location . "<br />\n";
      $nc .= "<blockquote>" . $event->description . "</blockquote><br /></li>\n";
    }
  }
  $nc .= "</ul>";

  return $nc;
}
?>