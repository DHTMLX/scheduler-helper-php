<?php
namespace DHTMLX_Scheduler;
use Exception;

class RecurringType {

    //RECURRING DATA FIELDS
    const FLD_REC_TYPE = "type";
    const FLD_REC_TYPE_STEP = "count";
    const FLD_WEEK_DAY = "day";
    const FLD_WEEK_NUMBER = "count2";
    const FLD_WEEK_DAYS_LIST = "days";
    const FLD_REPEAT = "repeat";

    //RECURRING TYPES
    const REC_TYPE_DAY = "day";
    const REC_TYPE_WEEK = "week";
    const REC_TYPE_MONTH = "month";
    const REC_TYPE_YEAR = "year";

    //RECURRING VALUES
    const IS_RECURRING_EXCEPTION = "";
    const IS_RECURRING_BREAK = "none";

    private $_transpose_size = array(
        self::REC_TYPE_DAY => 1,
        self::REC_TYPE_WEEK => 7,
        self::REC_TYPE_MONTH => 1,
        self::REC_TYPE_YEAR => 12
    );

    private $_fields_values = array();
    private $_recurring_start_date_stamp;
    private $_recurring_end_date_stamp;

    public function __construct($recurringType, $recurringStartDateStamp, $recurringEndDateStamp)
    {
        if(is_array($recurringType))
            $recurringType = self::parseRecurringDataArrayToString($recurringType);

        $this->_fields_values = self::_parseRecurringDataString($recurringType);
        $this->_recurring_start_date_stamp = $recurringStartDateStamp;
        $this->_recurring_end_date_stamp = $recurringEndDateStamp;
    }

    public static function getInstance($recurringTypeString, $recurringStartDateStamp, $recurringEndDateStamp) {
        return new self($recurringTypeString, $recurringStartDateStamp, $recurringEndDateStamp);
    }

    /**
     * Get field value.
     * @param $fieldName
     * @return mixed
     * @throws Exception
     */
    private function _getFieldValue($fieldName)
    {
        if(!isset($this->_fields_values[$fieldName]))
            throw new Exception("Field '{$fieldName}' not found.");

        return $this->_fields_values[$fieldName];
    }

    /**
     * Parse recurring data from array to string.
     * @param $dataArray
     * @return string
     * @throws Exception
     */
    static function parseRecurringDataArrayToString($dataArray)
    {
        $dataFields = array(
            self::FLD_REC_TYPE => "each",
            self::FLD_REC_TYPE_STEP => "step",
            self::FLD_WEEK_NUMBER => "week_number",
            self::FLD_WEEK_DAYS_LIST => "days_of_week",
            self::FLD_REPEAT => "repeat"
        );

        $recurringTypes = array(self::REC_TYPE_DAY, self::REC_TYPE_WEEK, self::REC_TYPE_MONTH, self::REC_TYPE_YEAR);
        $daysOfWeek = array("sunday" => 0, "monday" => 1, "tuesday" => 2, "wednesday" => 3, "thursday" => 4, "friday" => 5, "saturday" => 6);
        $dataFieldsValues = array();

        foreach($dataArray as $field => $value) {
            switch($field) {
                case $dataFields[self::FLD_REC_TYPE_STEP]:
                case $dataFields[self::FLD_REPEAT]:
                    $value = str_replace(" ", "", $value);
                    $dataFieldsValues[$field] = $value;
                    break;

                case $dataFields[self::FLD_REC_TYPE]:
                    $value = str_replace(" ", "", $value);
                    if(!in_array($value, $recurringTypes))
                        throw new Exception("Field '{$field}' will contains value of ".join(", ", $recurringTypes));

                    $dataFieldsValues[$field] = $value;
                    break;


                case $dataFields[self::FLD_WEEK_NUMBER]:
                    $value = str_replace(" ", "", $value);
                    if(count(explode(",", $dataArray[$dataFields[self::FLD_WEEK_DAYS_LIST]])) > 1)
                        throw new Exception("If field {$field} not null, then field ".$dataFields[self::FLD_WEEK_DAYS_LIST]." will contains only one value of ".join(", ", array_keys($daysOfWeek)));

                    if(!in_array($value, $daysOfWeek))
                        throw new Exception("Field {$field} will contains value of ".join(",", array_keys($daysOfWeek)));

                    $dataFieldsValues[$field] = $value;
                    break;

                case $dataFields[self::FLD_WEEK_DAYS_LIST]:
                    $weekDaysToRecurring = explode(",", $value);
                    $days = array();
                    foreach($weekDaysToRecurring as $day) {
                        $day = str_replace(" ", "", $day);
                        if(!in_array($day, $daysOfWeek))
                            throw new Exception("Field {$field} will contains data like 'monday,tuesday,wednesday'.");

                        array_push($days, $daysOfWeek[$day]);
                    }

                    $dataFieldsValues[$field] = join("," ,$days);
                    break;

                default:
                    $dataFieldsValues[$field] = "";
                    break;
            }
        }

        //Check required data and fill gaps of data.
        $requiredFields = array(
            self::FLD_REC_TYPE,
            self::FLD_REC_TYPE_STEP
        );

        foreach($dataFields as $fieldKey => $fieldName) {
            if(isset($dataFieldsValues[$fieldName]))
                continue;

            if(in_array($fieldKey, $requiredFields))
                throw new Exception("Field '{$fieldName}' is required");

            $dataFieldsValues[$fieldName] = "";
        }

        $recurringFormat = "%s_%s_%s_%s_%s#%s";
        return sprintf(
            $recurringFormat,
            $dataFieldsValues[$dataFields[self::FLD_REC_TYPE]],
            $dataFieldsValues[$dataFields[self::FLD_REC_TYPE_STEP]],
            (!empty($dataFieldsValues[$dataFields[self::FLD_WEEK_NUMBER]])) ? $dataFieldsValues[$dataFields[self::FLD_WEEK_DAYS_LIST]] : "",
            $dataFieldsValues[$dataFields[self::FLD_WEEK_NUMBER]],
            (empty($dataFieldsValues[$dataFields[self::FLD_WEEK_NUMBER]])) ? $dataFieldsValues[$dataFields[self::FLD_WEEK_DAYS_LIST]] : "",
            $dataFieldsValues[$dataFields[self::FLD_REPEAT]]
        );
    }

    /**
     * Parse recurring data from string.
     * @param $dataStr
     * @return array
     */
    static private function _parseRecurringDataString($dataStr)
    {
        $formatPartsReg = "/(_|#)/";
        $formatDaysListDelimiter = ",";
        $parsedData = array();

        $parts = preg_split($formatPartsReg, $dataStr);
        list(
            $parsedData[self::FLD_REC_TYPE],
            $parsedData[self::FLD_REC_TYPE_STEP],
            $parsedData[self::FLD_WEEK_DAY],
            $parsedData[self::FLD_WEEK_NUMBER],
            $parsedData[self::FLD_WEEK_DAYS_LIST]
            ) = $parts;

        if(isset($parts[5]))
        {
            $parsedData[self::FLD_REPEAT] = $parts[5];
        }

        $days = $parsedData[self::FLD_WEEK_DAYS_LIST];

        // $days is a comma separated week days string ("0,1,2"). Need an extra check for `every Sunday` series - "0" - which string is considered as falsy/empty in php
        if(!empty($days) || $days === "0"){
            $parsedData[self::FLD_WEEK_DAYS_LIST] = explode($formatDaysListDelimiter, $days);
        }else{
            $parsedData[self::FLD_WEEK_DAYS_LIST] = array();
        }

        return $parsedData;
    }

    public function getRecurringTypeValue()
    {
        return $this->_getFieldValue(self::FLD_REC_TYPE);
    }

    public function getRecurringTypeStepValue()
    {
        return $this->_getFieldValue(self::FLD_REC_TYPE_STEP);
    }

    public function getWeekNumberValue()
    {
        return $this->_getFieldValue(self::FLD_WEEK_NUMBER);
    }

    public function getWeekDayValue()
    {
        return $this->_getFieldValue(self::FLD_WEEK_DAY);
    }

    public function getWeekDaysListValue()
    {
        return $this->_getFieldValue(self::FLD_WEEK_DAYS_LIST);
    }

    public function getRepeatValue()
    {
        return $this->_getFieldValue(self::FLD_REPEAT);
    }

    /**
     * Correcting interval by recurring start($this->_recurring_start_date_stamp)
     * and end($this->_recurring_end_date_stamp) dates.
     * @param $intervalStartDateStamp
     * @param $intervalEndDateStamp
     * @return array
     */
    private function _getCorrectedRecurringInterval($intervalStartDateStamp, $intervalEndDateStamp)
    {
        $recurringStartDateStamp = $this->_recurring_start_date_stamp;
        $recurringEndDateStamp = $this->_recurring_end_date_stamp;

        $recurringInterval = array(
            "start_date_stamp" => $intervalStartDateStamp,
            "end_date_stamp" => $intervalEndDateStamp
        );

        //Return recurring interval without correcting if it not belongs to assigned interval.
        if (($intervalStartDateStamp >= $recurringEndDateStamp) || ($intervalEndDateStamp <= $recurringStartDateStamp))
            return $recurringInterval;

        //Correct start date interval if it smaller then recurring start date.
        if ($intervalStartDateStamp < $recurringStartDateStamp)
            $intervalStartDateStamp = $recurringStartDateStamp;

        //Correct end date interval if it smaller then recurring end date.
        if ($intervalEndDateStamp > $recurringEndDateStamp)
            $intervalEndDateStamp = $recurringEndDateStamp;

        $type = $this->getRecurringTypeValue();
        //If recurring type is "year" then exit, else add months.
        if ($type == self::REC_TYPE_DAY || $type == self::REC_TYPE_WEEK) {
            $step = $this->_transpose_size[$type] * $this->getRecurringTypeStepValue();
            $day = 24 * 60 * 60;
            $delta = floor(($intervalStartDateStamp - $recurringStartDateStamp) / ($day * $step));
            if ($delta > 0)
                $recurringInterval["start_date_stamp"] = $recurringStartDateStamp + $delta * $step * $day;
        }
        else {
            $differenceStartDates = SchedulerHelperDate::differenceBetweenDates($intervalStartDateStamp, $recurringStartDateStamp);
            $differenceEndDates = SchedulerHelperDate::differenceBetweenDates($intervalEndDateStamp, $recurringEndDateStamp);
            $dateUnits = SchedulerHelperDate::$DATE_UNITS;

            //Add years.
            $recurringInterval["start_date_stamp"] = SchedulerHelperDate::addYears($recurringStartDateStamp, $differenceStartDates[$dateUnits["year"]]);
            $recurringInterval["end_date_stamp"] = SchedulerHelperDate::addYears($recurringEndDateStamp, -$differenceEndDates[$dateUnits["year"]]);


            if ($type == self::REC_TYPE_YEAR)
                return $recurringInterval;

            //Add months.
            $recurringInterval["start_date_stamp"] = SchedulerHelperDate::addMonths($recurringInterval["start_date_stamp"], $differenceStartDates[$dateUnits["month"]]);
            $recurringInterval["end_date_stamp"] = SchedulerHelperDate::addMonths($recurringInterval["end_date_stamp"], -$differenceEndDates[$dateUnits["month"]]);
            if ($type == self::REC_TYPE_MONTH)
                return $recurringInterval;
        }

        return $recurringInterval;
    }

    /**
     * Get step to recurring day from current day of week in date.
     * @param $dateStamp
     * @param $recurringWeekDay
     * @return int
     */
    private function _getRecurringDayStep($dateStamp, $recurringWeekDay)
    {
        $weekDay = SchedulerHelperDate::getDayOfWeek($dateStamp);
        $dayStep = $recurringWeekDay - $weekDay;
        $dayStep = ($dayStep < 0) ? (SchedulerHelperDate::DAYS_IN_WEEK - (-$dayStep)) : $dayStep;
        return $dayStep;
    }

    /**
     * Get recurring days for date.
     * @param $dateStamp
     * @return array
     */
    private function _getRecurringDays($dateStamp)
    {
        $recurringDays = array();

        //If recurring type has list of days, then get those days.
        $recurringWeekDays = $this->getWeekDaysListValue();
        if($recurringWeekDays) {
            $daysCount = count($recurringWeekDays);
            for($i = 0; $i < $daysCount; $i++) {
                $dayStep = $this->_getRecurringDayStep($dateStamp, $recurringWeekDays[$i]);
                array_push($recurringDays, SchedulerHelperDate::addDays($dateStamp, $dayStep));
            }
        }
        //Else if recurring type has day of week and step for it, then get this day.
        elseif($this->getWeekDayValue() && $this->getWeekNumberValue()) {
            $dayStep = $this->_getRecurringDayStep($dateStamp, $this->getWeekDayValue());
            $dayStep += (SchedulerHelperDate::DAYS_IN_WEEK * ($this->getWeekNumberValue() - 1));
            array_push($recurringDays, SchedulerHelperDate::addDays($dateStamp, $dayStep));
        }
        //Else return recurring date without change.
        else
            array_push($recurringDays, $dateStamp);

        return $recurringDays;
    }

    /**
     * Get recurring dates by interval or $intervalStartDateStamp and $countDates.
     * @param $intervalStartDateStamp
     * @param $intervalEndDateStamp
     * @param null $countDates
     * @return array|bool
     */
    public function getRecurringDates($intervalStartDateStamp, $intervalEndDateStamp, $countDates = NULL)
    {
        $recurringTypeStep = $this->getRecurringTypeStepValue();
        $recType = $this->getRecurringTypeValue();

        if(!($recType && $recType))
            return false;

        //Correct interval by recurring interval.
        $correctedInterval = $this->_getCorrectedRecurringInterval($intervalStartDateStamp, $intervalEndDateStamp);
        $intervalStartDateStamp = $correctedInterval["start_date_stamp"];
        $intervalEndDateStamp = $correctedInterval["end_date_stamp"];
        $currentRecurringStartDateStamp = $intervalStartDateStamp;
        $recurringDates = array();

        //Generate dates wile next recurring date belongs to interval.
        $countRecurringCycles = 0;
        while(
            (!is_null($countDates) && ($countRecurringCycles <= $countDates))
            || (
                ($intervalStartDateStamp <= $currentRecurringStartDateStamp)
                && ($currentRecurringStartDateStamp < $intervalEndDateStamp)
            )
        ) {
            $countRecurringCycles++;
            $recurringDays = $this->_getRecurringDays($currentRecurringStartDateStamp);
            $recurringDates = array_merge($recurringDates, $recurringDays);

            switch($recType) {
                case self::REC_TYPE_DAY:
                    $currentRecurringStartDateStamp = SchedulerHelperDate::addDays($currentRecurringStartDateStamp, $recurringTypeStep);
                    break;

                case self::REC_TYPE_WEEK:
                    $currentRecurringStartDateStamp = SchedulerHelperDate::addWeeks($currentRecurringStartDateStamp, $recurringTypeStep);
                    break;

                case self::REC_TYPE_MONTH:
                    $currentRecurringStartDateStamp = SchedulerHelperDate::addMonths($currentRecurringStartDateStamp, $recurringTypeStep);
                    break;

                case self::REC_TYPE_YEAR:
                    $currentRecurringStartDateStamp = SchedulerHelperDate::addYears($currentRecurringStartDateStamp, $recurringTypeStep);
                    break;
            }
        }

        return (!is_null($countDates))
            ? array_splice($recurringDates, (count($recurringDates) - $countDates))
            : $recurringDates;
    }

    /**
     * @param $recurringType
     * @param $startDateStamp
     * @param $eventLength
     * @return int|NULL
     */
    public static function getRecurringEndDate($recurringType, $startDateStamp, $eventLength)
    {
        $recurringTypeObj = self::getInstance($recurringType, $startDateStamp, NULL);

        $repeatValue = $recurringTypeObj->getRepeatValue();
        if(empty($repeatValue))
            return ($startDateStamp + $eventLength);

        $recurringStartDatesStamps = $recurringTypeObj->getRecurringDates($startDateStamp, NULL, $repeatValue);

        $maxEndDateStamp = NULL;
        foreach($recurringStartDatesStamps as $startDateStamp) {
            $endDateStamp = $startDateStamp + $eventLength;
            $maxEndDateStamp = ($endDateStamp > $maxEndDateStamp) ? $endDateStamp : $maxEndDateStamp;
        }

        return $maxEndDateStamp;
    }

}