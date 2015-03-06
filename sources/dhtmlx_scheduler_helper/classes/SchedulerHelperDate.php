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

    static public function differenceBetweenDates($firstDateStamp, $secondDateStamp) {
        $firstDate = new DateTime(date(self::FORMAT_DEFAULT, $firstDateStamp));
        $secondDate = new DateTime(date(self::FORMAT_DEFAULT, $secondDateStamp));
        $dateUnits = self::$DATE_UNITS;
        $differenceArray = array();

        foreach($dateUnits as $dateUnit)
            $differenceArray[$dateUnit] = $firstDate->diff($secondDate, true)->format("%".$dateUnit);

        return $differenceArray;
    }

    static public function getDateTimestamp($date)
    {
        $parsedDate = date_parse($date);
        return mktime(
            $parsedDate["hour"],
            $parsedDate["minute"],
            $parsedDate["second"],
            $parsedDate["month"],
            $parsedDate["day"],
            $parsedDate["year"]
        );
    }

    static public function getDayOfWeek($timestamp) {
        $weekDay = getdate($timestamp)["wday"];
        return $weekDay;
    }

    static public function addDate($timestamp, $unit, $count) {
        $dateUnits = self::$DATE_UNITS;
        $units = array(
            $dateUnits["hour"],
            $dateUnits["minute"],
            $dateUnits["second"],
            $dateUnits["month"],
            $dateUnits["day"],
            $dateUnits["year"]
        );
        $args = array();

        for($i = 0; $i < count($units); $i++){
            $time_part = $units[$i];
            $param = date($time_part, $timestamp);
            if($unit == $time_part)
                $param += $count;

            array_push($args, $param);

        }

        return call_user_func_array("mktime", $args);
    }

    static public function addDays($timestamp, $count) {
        return self::addDate($timestamp, self::$DATE_UNITS["day"], $count);
    }

    static public function addWeeks($timestamp, $count) {
        return self::addDate($timestamp, self::$DATE_UNITS["day"], ($count * self::DAYS_IN_WEEK));
    }

    static public function addMonths($timestamp, $count) {
        return self::addDate($timestamp, self::$DATE_UNITS["month"], $count);
    }

    static public function addYears($timestamp, $count) {
        return self::addDate($timestamp, self::$DATE_UNITS["year"], $count);
    }

}