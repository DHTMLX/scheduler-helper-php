<?php

namespace DHTMLX_Scheduler;
use DateTime;

class SchedulerHelperDate
{
    const SECONDS_IN_DAY = 86400;
    const DAYS_IN_WEEK = 7;
    const MONTHS_IN_YEAR = 12;

    const FORMAT_DEFAULT = "Y-m-d H:i:s";

    static $DATE_UNITS = array(
        "hour" => "H",
        "minute" => "i",
        "second" => "s",
        "month" => "m",
        "day" => "d",
        "year" => "Y"
    );

    static $INTERVAL_UNITS = array(
        "hour" => array("type" => "T", "val" => "H"),
        "minute" => array("type" => "T", "val" => "M"),
        "second" => array("type" => "T", "val" => "S"),
        "month" => array("type" => "", "val" => "M"),
        "day" => array("type" => "", "val" => "D"),
        "year" => array("type" => "", "val" => "Y")
    );

    static public function differenceBetweenDates($firstDateStamp, $secondDateStamp) {
        $firstDate = new DateTime(date(self::FORMAT_DEFAULT, $firstDateStamp));
        $secondDate = new DateTime(date(self::FORMAT_DEFAULT, $secondDateStamp));
        $dateUnits = self::$DATE_UNITS;
        $differenceArray = array();

        foreach($dateUnits as $dateUnit) {
            //diff function can't be used here because it gets difference in UTC
            $differenceArray[$dateUnit] = abs($firstDate->format($dateUnit) - $secondDate->format($dateUnit));
        }

        return $differenceArray;
    }

    static public function getDateTimestamp($date, $serverDate)
    {
        $parsedDate = date_parse($date);

        $timestamp = false;
        if ($serverDate) {
            $timestamp = gmmktime(
                $parsedDate["hour"],
                $parsedDate["minute"],
                $parsedDate["second"],
                $parsedDate["month"],
                $parsedDate["day"],
                $parsedDate["year"]
            );
        } else {
            $timestamp = mktime(
                $parsedDate["hour"],
                $parsedDate["minute"],
                $parsedDate["second"],
                $parsedDate["month"],
                $parsedDate["day"],
                $parsedDate["year"]
            );
        }

        // mktime/gmmktime returns FALSE for years after 2038 in 32bit PHP, so timestamps for end_dates of endless series will be false as well (usually defined as 9999/1/1).
        // If $date is a valid date string and timestamp equals FALSE - assume that's it and give timestamp maximum possible value, so dates could be compared correctly later in code
        if($timestamp === false && $parsedDate && !($parsedDate["error_count"])){
            $timestamp = PHP_INT_MAX;
        }

        return $timestamp;
    }

    static public function getTimestampFromUTCTimestamp($stamp, $serverDate){
        $date = new DateTime();
        $date->setTimezone(new \DateTimeZone("UTC"));
        $date->setTimestamp($stamp);
        $date = self::getDateTimestamp($date->format(self::FORMAT_DEFAULT), $serverDate);
        return $date;
    }

    static public function getDayOfWeek($timestamp) {
        $weekDay = getdate($timestamp)["wday"];
        return $weekDay;
    }

    static public function addDate($timestamp, $unit, $count) {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $absCount = $count >= 0 ? $count : abs($count);
        $interval = new \DateInterval("P" . $unit["type"] . $absCount . $unit["val"]);
        if ($count >= 0)
            $date->add($interval);
        else
            $date->sub($interval);
        return $date->getTimestamp();
    }

    static public function addDays($timestamp, $count) {
        return self::addDate($timestamp, self::$INTERVAL_UNITS["day"], $count);
    }

    static public function addWeeks($timestamp, $count) {
        return self::addDate($timestamp, self::$INTERVAL_UNITS["day"], ($count * self::DAYS_IN_WEEK));
    }

    static public function addMonths($timestamp, $count) {
        return self::addDate($timestamp, self::$INTERVAL_UNITS["month"], $count);
    }

    static public function addYears($timestamp, $count) {
        return self::addDate($timestamp, self::$INTERVAL_UNITS["year"], $count);
    }

    static public function weekStart($timestamp, $startOnMonday = true){
        $shift = self::getDayOfWeek($timestamp);
        if($startOnMonday){
            if($shift === 0){
                $shift = 6;
            }
            else{
                $shift--;
            }
        }
        return self::addDays($timestamp, -1*$shift);
    }

    static public function monthStart($timestamp){
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $m = $date->format('m');
        $y = $date->format('Y');
        $date->setDate($y, $m, 1);
        return $date->getTimestamp();
    }

    static public function yearStart($timestamp){
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $y = $date->format('Y');
        $date->setDate($y, 1, 1);
        return $date->getTimestamp();
    }

}