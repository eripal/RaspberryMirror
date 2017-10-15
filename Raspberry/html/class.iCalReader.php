<?php
error_reporting(E_ALL);

class ICal
{
    public  /** @type {int} */ $todo_count = 0;
    public  /** @type {int} */ $event_count = 0; 
    public /** @type {Array} */ $cal;
    private /** @type {string} */ $_lastKeyWord;
	const UNIX_MIN_YEAR = 1970;
    const DATE_FORMAT = 'Ymd';
    const TIME_FORMAT = 'His';
	const DATE_TIME_FORMAT = 'Ymd\THis';
	protected $alteredRecurrenceInstances = array();
	public $defaultWeekStart = 'SU';
	protected $dayOrdinals = array(
        1 => 'first',
        2 => 'second',
        3 => 'third',
        4 => 'fourth',
        5 => 'fifth',
        6 => 'last',
    );
    protected $weekdays = array(
        'SU' => 'sunday',
        'MO' => 'monday',
        'TU' => 'tuesday',
        'WE' => 'wednesday',
        'TH' => 'thursday',
        'FR' => 'friday',
        'SA' => 'saturday',
    );
    protected $monthNames = array(
         1 => 'January',
         2 => 'February',
         3 => 'March',
         4 => 'April',
         5 => 'May',
         6 => 'June',
         7 => 'July',
         8 => 'August',
         9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    );
    protected $frequencyConversion = array(
        'DAILY'   => 'day',
        'WEEKLY'  => 'week',
        'MONTHLY' => 'month',
        'YEARLY'  => 'year',
    );
    public function __construct($filename) 
    {
        if (!$filename) {
			echo "Not a file";
            //return false;
        }        
//        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$lines = $filename;
        if (stristr($lines, 'BEGIN:VCALENDAR') === false) {
			echo "false</br>";
            return false;
        } else {
            // TODO: Fix multiline-description problem (see http://tools.ietf.org/html/rfc2445#section-4.8.1.5)
            foreach ($lines as $line) {
                $line = trim($line);
                $add  = $this->keyValueFromString($line);
                if ($add === false) {
                    $this->addCalendarComponentWithKeyAndValue($type, false, $line);
                    continue;
                } 
                list($keyword, $value) = $add;
                switch ($line) {
                // http://www.kanzaki.com/docs/ical/vtodo.html
                case "BEGIN:VTODO": 
                    $this->todo_count++;
                    $type = "VTODO"; 
                    break; 
                // http://www.kanzaki.com/docs/ical/vevent.html
                case "BEGIN:VEVENT": 
                    //echo "vevent gematcht";
                    $this->event_count++;
                    $type = "VEVENT"; 
                    break; 
                //all other special strings
                case "BEGIN:VCALENDAR": 
                case "BEGIN:DAYLIGHT": 
                    // http://www.kanzaki.com/docs/ical/vtimezone.html
                case "BEGIN:VTIMEZONE": 
                case "BEGIN:STANDARD": 
                    $type = $value;
                    break; 
                case "END:VTODO": // end special text - goto VCALENDAR key 
                case "END:VEVENT": 
                case "END:VCALENDAR": 
                case "END:DAYLIGHT": 
                case "END:VTIMEZONE": 
                case "END:STANDARD": 
                    $type = "VCALENDAR"; 
                    break; 
                default:
                    $this->addCalendarComponentWithKeyAndValue($type, 
                                                               $keyword, 
                                                               $value);
                    break; 
                } 
            }
            return $this->cal; 
        }
    }
    public function addCalendarComponentWithKeyAndValue($component, 
                                                        $keyword, 
                                                        $value) 
    {
        if ($keyword == false) { 
            $keyword = $this->last_keyword; 
            switch ($component) {
            case 'VEVENT': 
                $value = $this->cal[$component][$this->event_count - 1]
                                               [$keyword].$value;
                break;
            case 'VTODO' : 
                $value = $this->cal[$component][$this->todo_count - 1]
                                               [$keyword].$value;
                break;
            }
        }
        
        if (stristr($keyword, "DTSTART") or stristr($keyword, "DTEND")) {
            $keyword = explode(";", $keyword);
            $keyword = $keyword[0];
        }
        switch ($component) { 
        case "VTODO": 
            $this->cal[$component][$this->todo_count - 1][$keyword] = $value;
            //$this->cal[$component][$this->todo_count]['Unix'] = $unixtime;
            break; 
        case "VEVENT": 
            $this->cal[$component][$this->event_count - 1][$keyword] = $value; 
            break; 
        default: 
            $this->cal[$component][$keyword] = $value; 
            break; 
        } 
        $this->last_keyword = $keyword; 
    }
    public function keyValueFromString($text) 
    {
        preg_match("/([^:]+)[:]([\w\W]*)/", $text, $matches);
        if (count($matches) == 0) {
            return false;
        }
        $matches = array_splice($matches, 1, 2);
        return $matches;
    }
    public function iCalDateToUnixTimestamp($icalDate) 
    { 
        $icalDate = str_replace('T', '', $icalDate); 
        $icalDate = str_replace('Z', '', $icalDate); 
        $pattern  = '/([0-9]{4})';   // 1: YYYY
        $pattern .= '([0-9]{2})';    // 2: MM
        $pattern .= '([0-9]{2})';    // 3: DD
        $pattern .= '([0-9]{0,2})';  // 4: HH
        $pattern .= '([0-9]{0,2})';  // 5: MM
        $pattern .= '([0-9]{0,2})/'; // 6: SS
        preg_match($pattern, $icalDate, $date); 
        // Unix timestamp can't represent dates before 1970
        if ($date[1] <= 1970) {
            return false;
        } 
        // Unix timestamps after 03:14:07 UTC 2038-01-19 might cause an overflow
        // if 32 bit integers are used.
        $timestamp = mktime((int)$date[4], 
                            (int)$date[5], 
                            (int)$date[6], 
                            (int)$date[2],
                            (int)$date[3], 
                            (int)$date[1]);
        return  $timestamp;
    } 
    /**
     * Returns an array of arrays with all events. Every event is an associative
     * array and each property is an element it.
     *
     * @return {array}
     */
    public function events() 
    {
        $array = $this->cal;
        return $array['VEVENT'];
    }
    /**
     * Returns a boolean value whether thr current calendar has events or not
     *
     * @return {boolean}
     */
    public function hasEvents() 
    {
        return ( count($this->events()) > 0 ? true : false );
    }
    public function eventsFromRange($rangeStart = false, $rangeEnd = false) 
    {
        $events = $this->sortEventsWithOrder($this->events(), SORT_ASC);
        if (!$events) {
            return false;
        }
        $extendedEvents = array();
        
        if ($rangeStart !== false) {
            $rangeStart = new DateTime();
        }
        if ($rangeEnd !== false or $rangeEnd <= 0) {
            $rangeEnd = new DateTime('2038/01/18');
        } else {
            $rangeEnd = new DateTime($rangeEnd);
        }
        $rangeStart = $rangeStart->format('U');
        $rangeEnd   = $rangeEnd->format('U');
        
        // loop through all events by adding two new elements
        foreach ($events as $anEvent) {
            $timestamp = $this->iCalDateToUnixTimestamp($anEvent['DTSTART']);
			//echo $rangeStart . " >= " . $timestamp . " <= " . $rangeEnd;
            if ($timestamp >= $rangeStart && $timestamp <= $rangeEnd) {
				//echo "+";
                $extendedEvents[] = $anEvent;
            }
			//echo nl2br("\n");
        }
        return $extendedEvents;
    }
    public function sortEventsWithOrder($events, $sortOrder = SORT_ASC)
    {
        $extendedEvents = array();
        
        // loop through all events by adding two new elements
        foreach ($events as $anEvent) {
            if (!array_key_exists('UNIX_TIMESTAMP', $anEvent)) {
                $anEvent['UNIX_TIMESTAMP'] = 
                            $this->iCalDateToUnixTimestamp($anEvent['DTSTART']);
            }
            if (!array_key_exists('REAL_DATETIME', $anEvent)) {
                $anEvent['REAL_DATETIME'] = 
                            date("d.m.Y", $anEvent['UNIX_TIMESTAMP']);
            }
            
            $extendedEvents[] = $anEvent;
        }
        
        foreach ($extendedEvents as $key => $value) {
            $timestamp[$key] = $value['UNIX_TIMESTAMP'];
        }
        array_multisort($timestamp, $sortOrder, $extendedEvents);
        return $extendedEvents;
    }
	
	public function processRecurrences()
    {
        $events = $this->cal['VEVENT'];
        if (empty($events)) {
            return false;
        }
        foreach ($events as $anEvent) {
            if (isset($anEvent['RRULE']) && $anEvent['RRULE'] != '') {
                // Recurring event, parse RRULE and add appropriate duplicate events
                $rrules = array();
                $rruleStrings = explode(';', $anEvent['RRULE']);
                foreach ($rruleStrings as $s) {
                    list($k, $v) = explode('=', $s);
                    $rrules[$k] = $v;					
                }				
                // Get frequency
                $frequency = $rrules['FREQ'];
                // Get Start timestamp
                $startTimestamp = $anEvent['DTSTART_array'][2];
                if (isset($anEvent['DTEND'])) {
                    $endTimestamp = $anEvent['DTEND_array'][2];
                } else if (isset($anEvent['DURATION'])) {
                    $duration = end($anEvent['DURATION_array']);
                    $endTimestamp = date_create($anEvent['DTSTART']);
                    $endTimestamp->modify($duration->y . ' year');
                    $endTimestamp->modify($duration->m . ' month');
                    $endTimestamp->modify($duration->d . ' day');
                    $endTimestamp->modify($duration->h . ' hour');
                    $endTimestamp->modify($duration->i . ' minute');
                    $endTimestamp->modify($duration->s . ' second');
                    $endTimestamp = date_format($endTimestamp, 'U');
                } else {
                    $endTimestamp = $anEvent['DTSTART_array'][2];
                }
                $eventTimestampOffset = $endTimestamp - $startTimestamp;
                // Get Interval
                $interval = (isset($rrules['INTERVAL']) && $rrules['INTERVAL'] != '')
                    ? $rrules['INTERVAL']
                    : 1;
                $dayNumber = null;
                $weekDay = null;
                if (in_array($frequency, array('MONTHLY', 'YEARLY'))
                    && isset($rrules['BYDAY']) && $rrules['BYDAY'] != ''
                ) {
                    // Deal with BYDAY
                    $dayNumber = intval($rrules['BYDAY']);
                    if (empty($dayNumber)) { // Returns 0 when no number defined in BYDAY
                        if (!isset($rrules['BYSETPOS'])) {
                            $dayNumber = 1; // Set first as default
                        } else if (is_numeric($rrules['BYSETPOS'])) {
                            $dayNumber = $rrules['BYSETPOS'];
                        }
                    }
                    $dayNumber = ($dayNumber == -1) ? 6 : $dayNumber; // Override for our custom key (6 => 'last')
                    $weekDay = substr($rrules['BYDAY'], -2);
                }
                $untilDefault = date_create('now');
                $untilDefault->modify($this->defaultSpan . ' year');
                $untilDefault->setTime(23, 59, 59); // End of the day
                if (isset($rrules['UNTIL'])) {
                    // Get Until
                    $until = strtotime($rrules['UNTIL']);
                } else if (isset($rrules['COUNT'])) {
                    $countOrig = (is_numeric($rrules['COUNT']) && $rrules['COUNT'] > 1) ? $rrules['COUNT'] : 0;
                    $count = ($countOrig - 1); // Remove one to exclude the occurrence that initialises the rule
                    $count += ($count > 0) ? $count * ($interval - 1) : 0;
                    $countNb = 1;
                    $offset = "+$count " . $this->frequencyConversion[$frequency];
                    $until = strtotime($offset, $startTimestamp);
                    if (in_array($frequency, array('MONTHLY', 'YEARLY'))
                        && isset($rrules['BYDAY']) && $rrules['BYDAY'] != ''
                    ) {
                        $dtstart = date_create($anEvent['DTSTART']);
                        for ($i = 1; $i <= $count; $i++) {
                            $dtstartClone = clone $dtstart;
                            $dtstartClone->modify('next ' . $this->frequencyConversion[$frequency]);
                            $offset = "{$this->dayOrdinals[$dayNumber]} {$this->weekdays[$weekDay]} of " . $dtstartClone->format('F Y H:i:01');
                            $dtstart->modify($offset);
                        }
                        /**
                         * Jumping X months forwards doesn't mean
                         * the end date will fall on the same day defined in BYDAY
                         * Use the largest of these to ensure we are going far enough
                         * in the future to capture our final end day
                         */
                        $until = max($until, $dtstart->format('U'));
                    }
                    unset($offset);
                } else {
                    $until = $untilDefault->getTimestamp();
                }
                if(!isset($anEvent['EXDATE_array'])){
                    $anEvent['EXDATE_array'] = array();
                }				
                // Decide how often to add events and do so
                switch ($frequency) {
                    case 'DAILY':
                        // Simply add a new event each interval of days until UNTIL is reached
                        $offset = "+$interval day";
                        $recurringTimestamp = strtotime($offset, $startTimestamp);
                        while ($recurringTimestamp <= $until) {
                            // Add event
                            $anEvent['DTSTART'] = gmdate(self::DATE_TIME_FORMAT, $recurringTimestamp) . 'Z';
                            $anEvent['DTSTART_array'] = array(array(), $anEvent['DTSTART'], $recurringTimestamp);
                            $anEvent['DTEND_array'] = $anEvent['DTSTART_array'];
                            $anEvent['DTEND_array'][2] += $eventTimestampOffset;
                            $anEvent['DTEND'] = gmdate(
                                self::DATE_TIME_FORMAT,
                                $anEvent['DTEND_array'][2]
                            ) . 'Z';
                            $anEvent['DTEND_array'][1] = $anEvent['DTEND'];
                            $searchDate = $anEvent['DTSTART'];
                            $isExcluded = array_filter($anEvent['EXDATE_array'], function($val) use ($searchDate) {
                                return is_string($val) && strpos($searchDate, $val) === 0;
                            });
                            if (isset($this->alteredRecurrenceInstances[$anEvent['UID']]) && in_array($dayRecurringTimestamp, $this->alteredRecurrenceInstances[$anEvent['UID']])) {
                                $isExcluded = true;
                            }
                            if (!$isExcluded) {
                                $events[] = $anEvent;
                                $this->eventCount++;
                                // If RRULE[COUNT] is reached then break
                                if (isset($rrules['COUNT'])) {
                                    $countNb++;
                                    if ($countNb >= $countOrig) {
                                        break 2;
                                    }
                                }
                            }
                            // Move forwards
                            $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                        }
                        break;
                    case 'WEEKLY':							
                        // Create offset
                        $offset = "+$interval week";
                        // Use RRULE['WKST'] setting or a default week start (UK = SU, Europe = MO)
                        $weeks = array(
                            'SA' => array('SA', 'SU', 'MO', 'TU', 'WE', 'TH', 'FR'),
                            'SU' => array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'),
                            'MO' => array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'),
                        );
                        $wkst = (isset($rrules['WKST']) && in_array($rrules['WKST'], array('SA', 'SU', 'MO'))) ? $rrules['WKST'] : $this->defaultWeekStart;
                        $aWeek = $weeks[$wkst];
                        $days = array('SA' => 'Saturday', 'SU' => 'Sunday', 'MO' => 'Monday');
                        // Build list of days of week to add events
                        $weekdays = $aWeek;
                        if (isset($rrules['BYDAY']) && $rrules['BYDAY'] != '') {
                            $bydays = explode(',', $rrules['BYDAY']);
                        } else {
                            $findDay = $weekdays[gmdate('w', $startTimestamp)];
                            $bydays = array($findDay);
                        }
                        // Get timestamp of first day of start week
                        $weekRecurringTimestamp = (gmdate('w', $startTimestamp) == 0)
                            ? $startTimestamp
                            : strtotime("last {$days[$wkst]} " . gmdate('H:i:s\z', $startTimestamp), $startTimestamp);
                        // Step through weeks
                        while ($weekRecurringTimestamp <= $until) {
                            // Add events for bydays
                            $dayRecurringTimestamp = $weekRecurringTimestamp;
							$dayRecurringTimestamp = strtotime('+1 day', $dayRecurringTimestamp);
                            foreach ($weekdays as $day) {
                                // Check if day should be added
                                if (in_array($day, $bydays) && $dayRecurringTimestamp > $startTimestamp
                                    && $dayRecurringTimestamp <= $until
                                ) {		
                                    // Add event
                                    $anEvent['DTSTART'] = gmdate(self::DATE_TIME_FORMAT, $dayRecurringTimestamp) . 'Z';
                                    $anEvent['DTSTART_array'] = array(array(), $anEvent['DTSTART'], $dayRecurringTimestamp);
                                    $anEvent['DTEND_array'] = $anEvent['DTSTART_array'];
                                    $anEvent['DTEND_array'][2] += $eventTimestampOffset;
                                    $anEvent['DTEND'] = gmdate(
                                        self::DATE_TIME_FORMAT,
                                        $anEvent['DTEND_array'][2]
                                    ) . 'Z';
                                    $anEvent['DTEND_array'][1] = $anEvent['DTEND'];
                                    $searchDate = $anEvent['DTSTART'];
                                    $isExcluded = array_filter($anEvent['EXDATE_array'], function($val) use ($searchDate) {
                                        return is_string($val) && strpos($searchDate, $val) === 0;
                                    });									
                                    if (isset($this->alteredRecurrenceInstances[$anEvent['UID']]) && in_array($dayRecurringTimestamp, $this->alteredRecurrenceInstances[$anEvent['UID']])) {
                                        $isExcluded = true;
                                    }
                                    if (!$isExcluded) {
                                        $events[] = $anEvent;
                                        $this->eventCount++;
                                        // If RRULE[COUNT] is reached then break
                                        if (isset($rrules['COUNT'])) {
                                            $countNb++;
                                            if ($countNb >= $countOrig) {
                                                break 2;
                                            }
                                        }
                                    }									
                                }							
                                // Move forwards a day
                                $dayRecurringTimestamp = strtotime('+1 day', $dayRecurringTimestamp);
                            }							
                            // Move forwards $interval weeks
                            $weekRecurringTimestamp = strtotime($offset, $weekRecurringTimestamp);
                        }
                        break;
                    case 'MONTHLY':
                        // Create offset
                        $offset = "+$interval month";
                        $recurringTimestamp = strtotime($offset, $startTimestamp);
                        if (isset($rrules['BYMONTHDAY']) && $rrules['BYMONTHDAY'] != '') {
                            // Deal with BYMONTHDAY
                            $monthdays = explode(',', $rrules['BYMONTHDAY']);
                            while ($recurringTimestamp <= $until) {
                                foreach ($monthdays as $monthday) {
                                    // Add event
                                    $anEvent['DTSTART'] = gmdate(
                                        'Ym' . sprintf('%02d', $monthday) . '\T' . self::TIME_FORMAT,
                                        $recurringTimestamp
                                    ) . 'Z';
                                    $anEvent['DTSTART_array'] = array(array(), $anEvent['DTSTART'], $recurringTimestamp);
                                    $anEvent['DTEND_array'] = $anEvent['DTSTART_array'];
                                    $anEvent['DTEND_array'][2] += $eventTimestampOffset;
                                    $anEvent['DTEND'] = gmdate(
                                        self::DATE_TIME_FORMAT,
                                        $anEvent['DTEND_array'][2]
                                    ) . 'Z';
                                    $anEvent['DTEND_array'][1] = $anEvent['DTEND'];
                                    $searchDate = $anEvent['DTSTART'];
                                    $isExcluded = array_filter($anEvent['EXDATE_array'], function($val) use ($searchDate) {
                                        return is_string($val) && strpos($searchDate, $val) === 0;
                                    });
                                    if (isset($this->alteredRecurrenceInstances[$anEvent['UID']]) && in_array($dayRecurringTimestamp, $this->alteredRecurrenceInstances[$anEvent['UID']])) {
                                        $isExcluded = true;
                                    }
                                    if (!$isExcluded) {
                                        $events[] = $anEvent;
                                        $this->eventCount++;
                                        // If RRULE[COUNT] is reached then break
                                        if (isset($rrules['COUNT'])) {
                                            $countNb++;
                                            if ($countNb >= $countOrig) {
                                                break 2;
                                            }
                                        }
                                    }
                                }
                                // Move forwards
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        } else if (isset($rrules['BYDAY']) && $rrules['BYDAY'] != '') {
                            $startTime = gmdate(self::TIME_FORMAT, $startTimestamp);
                            while ($recurringTimestamp <= $until) {
                                $eventStartDesc = "{$this->dayOrdinals[$dayNumber]} {$this->weekdays[$weekDay]} of " . gmdate('F Y H:i:s', $recurringTimestamp);
                                $eventStartTimestamp = strtotime($eventStartDesc);
                                // Prevent 5th day of a month from showing up on the next month
                                // If BYDAY and the event falls outside the current month, skip the event
                                $compareCurrentMonth = date('F', $recurringTimestamp);
                                $compareEventMonth   = date('F', $eventStartTimestamp);
                                if ($compareCurrentMonth != $compareEventMonth) {
                                    $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                                    continue;
                                }
                                if ($eventStartTimestamp > $startTimestamp && $eventStartTimestamp < $until) {
                                    $anEvent['DTSTART'] = gmdate(self::DATE_FORMAT, $eventStartTimestamp) . 'T' . $startTime . 'Z';
                                    $anEvent['DTSTART_array'] = array(array(), $anEvent['DTSTART'], $eventStartTimestamp);
                                    $anEvent['DTEND_array'] = $anEvent['DTSTART_array'];
                                    $anEvent['DTEND_array'][2] += $eventTimestampOffset;
                                    $anEvent['DTEND'] = gmdate(
                                        self::DATE_TIME_FORMAT,
                                        $anEvent['DTEND_array'][2]
                                    ) . 'Z';
                                    $anEvent['DTEND_array'][1] = $anEvent['DTEND'];
                                    $searchDate = $anEvent['DTSTART'];
                                    $isExcluded = array_filter($anEvent['EXDATE_array'], function($val) use ($searchDate) {
                                        return is_string($val) && strpos($searchDate, $val) === 0;
                                    });
                                    if (isset($this->alteredRecurrenceInstances[$anEvent['UID']]) && in_array($dayRecurringTimestamp, $this->alteredRecurrenceInstances[$anEvent['UID']])) {
                                        $isExcluded = true;
                                    }
                                    if (!$isExcluded) {
                                        $events[] = $anEvent;
                                        $this->eventCount++;
                                        // If RRULE[COUNT] is reached then break
                                        if (isset($rrules['COUNT'])) {
                                            $countNb++;
                                            if ($countNb >= $countOrig) {
                                                break 2;
                                            }
                                        }
                                    }
                                }
                                // Move forwards
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        }
                        break;
                    case 'YEARLY':
                        // Create offset
                        $offset = "+$interval year";
                        $recurringTimestamp = strtotime($offset, $startTimestamp);
                        // Check if BYDAY rule exists
                        if (isset($rrules['BYDAY']) && $rrules['BYDAY'] != '') {
                            $startTime = gmdate(self::TIME_FORMAT, $startTimestamp);
                            while ($recurringTimestamp <= $until) {
                                $eventStartDesc = "{$this->dayOrdinals[$dayNumber]} {$this->weekdays[$weekDay]}"
                                    . " of {$this->monthNames[$rrules['BYMONTH']]} "
                                    . gmdate('Y H:i:s', $recurringTimestamp);
                                $eventStartTimestamp = strtotime($eventStartDesc);
                                if ($eventStartTimestamp > $startTimestamp && $eventStartTimestamp < $until) {
                                    $anEvent['DTSTART'] = gmdate(self::DATE_FORMAT, $eventStartTimestamp) . 'T' . $startTime . 'Z';
                                    $anEvent['DTSTART_array'] = array(array(), $anEvent['DTSTART'], $eventStartTimestamp);
                                    $anEvent['DTEND_array'] = $anEvent['DTSTART_array'];
                                    $anEvent['DTEND_array'][2] += $eventTimestampOffset;
                                    $anEvent['DTEND'] = gmdate(
                                        self::DATE_TIME_FORMAT,
                                        $anEvent['DTEND_array'][2]
                                    ) . 'Z';
                                    $anEvent['DTEND_array'][1] = $anEvent['DTEND'];
                                    $searchDate = $anEvent['DTSTART'];
                                    $isExcluded = array_filter($anEvent['EXDATE_array'], function($val) use ($searchDate) {
                                        return is_string($val) && strpos($searchDate, $val) === 0;
                                    });
                                    if (isset($this->alteredRecurrenceInstances[$anEvent['UID']]) && in_array($dayRecurringTimestamp, $this->alteredRecurrenceInstances[$anEvent['UID']])) {
                                        $isExcluded = true;
                                    }
                                    if (!$isExcluded) {
                                        $events[] = $anEvent;
                                        $this->eventCount++;
                                        // If RRULE[COUNT] is reached then break
                                        if (isset($rrules['COUNT'])) {
                                            $countNb++;
                                            if ($countNb >= $countOrig) {
                                                break 2;
                                            }
                                        }
                                    }
                                }
                                // Move forwards
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        } else {
                            $day = gmdate('d', $startTimestamp);
                            $startTime = gmdate(self::TIME_FORMAT, $startTimestamp);
                            // Step through years
                            while ($recurringTimestamp <= $until) {
                                // Add specific month dates
                                if (isset($rrules['BYMONTH']) && $rrules['BYMONTH'] != '') {
                                    $eventStartDesc = "$day {$this->monthNames[$rrules['BYMONTH']]} " . gmdate('Y H:i:s', $recurringTimestamp);
                                } else {
                                    $eventStartDesc = $day . gmdate('F Y H:i:s', $recurringTimestamp);
                                }
                                $eventStartTimestamp = strtotime($eventStartDesc);
                                if ($eventStartTimestamp > $startTimestamp && $eventStartTimestamp < $until) {
                                    $anEvent['DTSTART'] = gmdate(self::DATE_FORMAT, $eventStartTimestamp) . 'T' . $startTime . 'Z';
                                    $anEvent['DTSTART_array'] = array(array(), $anEvent['DTSTART'], $eventStartTimestamp);
                                    $anEvent['DTEND_array'] = $anEvent['DTSTART_array'];
                                    $anEvent['DTEND_array'][2] += $eventTimestampOffset;
                                    $anEvent['DTEND'] = gmdate(
                                        self::DATE_TIME_FORMAT,
                                        $anEvent['DTEND_array'][2]
                                    ) . 'Z';
                                    $anEvent['DTEND_array'][1] = $anEvent['DTEND'];
                                    $searchDate = $anEvent['DTSTART'];
                                    $isExcluded = array_filter($anEvent['EXDATE_array'], function($val) use ($searchDate) {
                                        return is_string($val) && strpos($searchDate, $val) === 0;
                                    });
                                    if (isset($this->alteredRecurrenceInstances[$anEvent['UID']]) && in_array($dayRecurringTimestamp, $this->alteredRecurrenceInstances[$anEvent['UID']])) {
                                        $isExcluded = true;
                                    }
                                    if (!$isExcluded) {
                                        $events[] = $anEvent;
                                        $this->eventCount++;
                                        // If RRULE[COUNT] is reached then break
                                        if (isset($rrules['COUNT'])) {
                                            $countNb++;
                                            if ($countNb >= $countOrig) {
                                                break 2;
                                            }
                                        }
                                    }
                                }
                                // Move forwards
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        }
                        break;
                    $events = (isset($countOrig) && sizeof($events) > $countOrig) ? array_slice($events, 0, $countOrig) : $events; // Ensure we abide by COUNT if defined
                }
            }
        }
        $this->cal['VEVENT'] = $events;
    }	
} 
?>