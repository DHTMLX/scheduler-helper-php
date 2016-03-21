<?php

namespace DHTMLX_Scheduler;
require_once "SchedulerHelperDate.php";
require_once "SchedulerHelperConnector.php";
require_once "RecurringType.php";

use PDO, Exception;

abstract class DHelper extends SchedulerHelperConnector
{
	const FLD_ID = "id";
	const FLD_START_DATE = "start_date";
	const FLD_END_DATE = "end_date";
	const FLD_TEXT = "text";
	const FLD_RECURRING_TYPE = "recurring_type";
	const FLD_PARENT_ID = "parent_id";
	const FLD_LENGTH = "length";

	private $_fields_names = array(
		self::FLD_ID => "event_id",
		self::FLD_START_DATE => "start_date",
		self::FLD_END_DATE => "end_date",
		self::FLD_TEXT => "text",
		self::FLD_RECURRING_TYPE => "rec_type",
		self::FLD_PARENT_ID => "event_pid",
		self::FLD_LENGTH => "event_length"
	);

	private $_connect_configs;
	private $_fields_values = array();

	public $config = array(
		"debug" => true,
		"server_date" => false
	);

	protected $_mapped_fields = array();

	protected $_use_only_mapped_fields = false;

	protected function getIdFieldName()
	{
		return $this->getFieldsNames(self::FLD_ID);
	}

	protected function getStartDateFieldName()
	{
		return $this->getFieldsNames(self::FLD_START_DATE);
	}

	protected function getEndDateFieldName()
	{
		return $this->getFieldsNames(self::FLD_END_DATE);
	}

	protected function getTextFieldName()
	{
		return $this->getFieldsNames(self::FLD_TEXT);
	}

	protected function getRecurringTypeFieldName()
	{
		return $this->getFieldsNames(self::FLD_RECURRING_TYPE);
	}

	protected function getParentIdFieldName()
	{
		return $this->getFieldsNames(self::FLD_PARENT_ID);
	}

	protected function getLengthFieldName()
	{
		return $this->getFieldsNames(self::FLD_LENGTH);
	}

	protected function getConnectConfigs()
	{
		return $this->_connect_configs;
	}

	protected function setConnectConfigs($connectConfigs)
	{
		$this->_connect_configs = $connectConfigs;
		return $this;
	}

	protected function getFieldsNames($field = NULL)
	{
		if(is_null($field))
			return $this->_fields_names;

		if(!isset($this->_fields_names[$field]))
			throw new Exception("Field {$field} not found.");

		return $this->_fields_names[$field];
	}

	public function setFieldsNames($fieldsDataArray, $useOnlySetFields = false)
	{
		if(!is_array($fieldsDataArray))
			throw new Exception("Fields data must be array.");

		$this->_mapped_fields = $fieldsDataArray;
		$this->_use_only_mapped_fields = $useOnlySetFields;

		foreach($fieldsDataArray as $fieldKey => $fieldValue) {
			//If field name is numeric, then made same field key and field value.
			if(is_numeric($fieldKey))
				$fieldKey = $fieldValue;

			$this->_fields_names[$fieldKey] = $fieldValue;
		}

		return $this;
	}

	protected function setFieldsValues($dataArray)
	{
		foreach($dataArray as $fieldKey => $fieldValue)
			$this->_fields_values[$this->_fields_names[$fieldKey]] = $fieldValue;

		return $this;
	}

	protected function getFieldsValues($field = NULL)
	{
		if(is_null($field))
			return $this->_fields_values;

		if(isset($this->_fields_values[$this->_fields_names[$field]]))
			return $this->_fields_values[$this->_fields_names[$field]];

		return NULL;
	}
}

interface IHelper
{
	public function getData($startDate, $endDate);
	public function saveData($dataArray);
	public function deleteById($id);
}

class Helper extends DHelper implements IHelper
{
	public function __construct($connectConfigs = NULL)
	{
		$this->setConnectConfigs($connectConfigs);
		parent::__construct($connectConfigs);
	}

	public static function getInstance($connectConfigs = NULL)
	{
		return new self($connectConfigs);
	}

	/**
	 * Gets timestamp from date
	 * @param $date
	 * @return int
	 */
	private function getDateTimestamp($date)
	{
		return SchedulerHelperDate::getDateTimestamp($date, $this->config["server_date"]);
	}

	/**
	 * Get recurring events data exceptions. And prepare data to format: []
	 * @return array
	 */
	private function _getRecurringEventsExceptionsByInterval()
	{
		$getEventsSql = "
			SELECT
				*
			FROM
				".$this->getTableName()."
			WHERE
			    (
			        ".$this->getRecurringTypeFieldName()." = '".RecurringType::IS_RECURRING_EXCEPTION."'
				    OR ".$this->getRecurringTypeFieldName()." = '".RecurringType::IS_RECURRING_BREAK."'
				    OR ".$this->getRecurringTypeFieldName()." IS NULL
                )
				AND ".$this->getLengthFieldName()." > '0'
		";

		$query = $this->getConnection()->prepare($getEventsSql);
		$query->execute();
		$events = array();

		while($eventData = $query->fetch(PDO::FETCH_ASSOC))
		{
			$eventParentId = $eventData[$this->getParentIdFieldName()];
			if(!isset($events[$eventParentId]))
				$events[$eventParentId] = array();

			$eventLength = $eventData[$this->getLengthFieldName()];
			$events[$eventParentId][$eventLength] = $eventData;
		}

        $this->closeConnection();
		return $events;
	}

	/**
	 * Get simple events by interval.
	 * @param $startDate
	 * @param $endDate
	 * @return array
	 */
	private function _getSimpleEventsByInterval($startDate, $endDate) {
		$getEventsSql = "
			SELECT
				*
			FROM
				".$this->getTablename()."
			WHERE
			    (
                	".$this->getStartDateFieldName()." <= '{$endDate}'
                	AND ".$this->getEndDateFieldName()." >= '{$startDate}'
                )
				AND (
				    ".$this->getRecurringTypeFieldName()." = '".RecurringType::IS_RECURRING_EXCEPTION."'
				    OR ".$this->getRecurringTypeFieldName()." = '".RecurringType::IS_RECURRING_BREAK."'
				    OR ".$this->getRecurringTypeFieldName()." IS NULL
				)
				AND ".$this->getLengthFieldName()." = '0'
        ";

		$query = $this->getConnection()->prepare($getEventsSql);
		$query->execute();
        $data = $query->fetchAll();
        $this->closeConnection();
		return $data;
	}

	/**
	 * Prepare events data.
	 * @param $events
	 * @return array
	 */
	private function _prepareSimpleEvents($events)
	{
		$resultData = array();
		$evCount = count($events);
		for($i = 0; $i < $evCount; $i++)
		{
			array_push($resultData, $this->_filterEventDataToResponse($events[$i]));
		}

		return $resultData;
	}

	/**
	 * Get recurring events data by interval.
	 * @param $startDate
	 * @param $endDate
	 * @return array
	 */
	private function _getRecurringEventsByInterval($startDate, $endDate)
	{
		$getEventsSql = "
			SELECT
				*
			FROM
				".$this->getTablename()."
			WHERE
			    (
                    (
                        ".$this->getEndDateFieldName()." >= '{$startDate}'
                        AND ".$this->getStartDateFieldName()." <= '{$endDate}'
                    )
                )
                AND (
				    ".$this->getRecurringTypeFieldName()." != '".RecurringType::IS_RECURRING_EXCEPTION."'
				    AND ".$this->getRecurringTypeFieldName()." != '".RecurringType::IS_RECURRING_BREAK."'
				    AND ".$this->getRecurringTypeFieldName()." IS NOT NULL
				)
				AND ".$this->getLengthFieldName()." != '0'
        ";

		$query = $this->getConnection()->prepare($getEventsSql);
		$query->execute();
        $data = $query->fetchAll();
        $this->closeConnection();
		return $data;
	}

	/**
	 * Exclude event extra data.
	 * @param $eventDataArray
	 * @return array
	 */
	private function _filterEventDataToResponse($eventDataArray)
	{
		$filteredEventData = array();
		$fullEventData = array();
		foreach($eventDataArray as $dataKey => $dataValue) {
			$fullEventData[$dataKey] = $dataValue;
			if (!$this->_use_only_mapped_fields || array_key_exists($dataKey, array_flip($this->_mapped_fields)))
				$filteredEventData[$dataKey] = $dataValue;
		}
		return array("filtered_event_data" => $filteredEventData, "full_event_data" => $fullEventData);
	}

	/**
	 * Exclude recurring exceptions from dates and prepare events data.
	 * @param $recurringDatesStamps
	 * @param $recurringEventData
	 * @param array $recurringEventExceptionsData
	 * @return array
	 */
	private function _prepareRecurringDataWithoutExceptions($recurringDatesStamps, $recurringEventData, $recurringEventExceptionsData = array())
	{
		$recurringData = array();

		$parentRecurringExceptions = array();
		if(isset($recurringEventExceptionsData[$recurringEventData[$this->getIdFieldName()]]))
			$parentRecurringExceptions = $recurringEventExceptionsData[$recurringEventData[$this->getIdFieldName()]];

		$startField = $this->getStartDateFieldName();
		$lengthField = $this->getLengthFieldName();
		$endField = $this->getEndDateFieldName();
		$stampsCount = count($recurringDatesStamps);
		for($i = 0; $i < $stampsCount; $i++) {
			$preparedEventData = $recurringEventData;
			$eventStartDateStamp = $recurringDatesStamps[$i];
			$preparedEventData[$startField] = date(SchedulerHelperDate::FORMAT_DEFAULT, $eventStartDateStamp);

			$eventEndDateStamp = $eventStartDateStamp + $recurringEventData[$lengthField];
			$preparedEventData[$endField] = date(SchedulerHelperDate::FORMAT_DEFAULT, $eventEndDateStamp);

			if(isset($parentRecurringExceptions[$eventStartDateStamp])) {
				$eventExceptionData = $parentRecurringExceptions[$eventStartDateStamp];
				if($eventExceptionData[$this->getRecurringTypeFieldName()] != RecurringType::IS_RECURRING_BREAK)
					$preparedEventData = $eventExceptionData;
				else
					continue;
			}

			$preparedEventData = $this->_filterEventDataToResponse($preparedEventData);
			array_push($recurringData, $preparedEventData);
		}

		return $recurringData;
	}

	/**
	 * Get recurring events data by interval.
	 * @param $startDate
	 * @param $endDate
	 * @return array
	 */
	public function getData($startDate, $endDate)
	{
		$eventsData = array();
		$recurringEventsExceptions = $this->_getRecurringEventsExceptionsByInterval();
		$recurringEvents = $this->_getRecurringEventsByInterval($startDate, $endDate);

		$intervalStartDateStamp = $this->getDateTimestamp($startDate);
		$intervalEndDateStamp = $this->getDateTimestamp($endDate);

		$recField = $this->getRecurringTypeFieldName();
		$startField = $this->getStartDateFieldName();
		$endField = $this->getEndDateFieldName();

		$recCount = count($recurringEvents);
		for($i = 0; $i < $recCount; $i++) {
			$eventData = $recurringEvents[$i];

			//Parse recurring data format.
			$recurringTypeData = $eventData[$recField];
			$recurringStartDateStamp = $this->getDateTimestamp($eventData[$startField]);
			$recurringEndDateStamp = $this->getDateTimestamp($eventData[$endField]);
			$recurringTypeObj = new RecurringType($recurringTypeData, $recurringStartDateStamp, $recurringEndDateStamp);

			//Get recurring dates by parsed format.
			$recurringDatesStamps = $recurringTypeObj->getRecurringDates($intervalStartDateStamp, $intervalEndDateStamp);

			//Exclude recurring exceptions by dates and prepare events data.
			$recurringEventData = $this->_prepareRecurringDataWithoutExceptions($recurringDatesStamps, $eventData, $recurringEventsExceptions);
			$eventsData = array_merge($eventsData, $recurringEventData);
		}

		//Add simple events.
		$simpleEvents = $this->_getSimpleEventsByInterval($startDate, $endDate);
		$simpleEvents = $this->_prepareSimpleEvents($simpleEvents);
		$eventsData = array_merge($eventsData, $simpleEvents);

		//Leave events that belongs to interval.
		$resultData = array();
		$evCount = count($eventsData);
		for($i = 0; $i < $evCount; $i++) {
			$eventData = $eventsData[$i];
			$fullEventData = $eventData["full_event_data"];
			$recurringStartDateStamp = $this->getDateTimestamp($fullEventData[$startField]);
			$recurringEndDateStamp = $this->getDateTimestamp($fullEventData[$endField]);
			if($recurringStartDateStamp < $intervalEndDateStamp && $recurringEndDateStamp > $intervalStartDateStamp) {
				array_push($resultData, $eventData["filtered_event_data"]);
			}
		}

		return $resultData;
	}

	/**
	 * Save recurring events data.
	 * @param $dataArray
	 * @throws Exception
	 */
	public function saveData($dataArray)
	{
		//If exist recurring type field and this array, then parse this to string.
		if((isset($dataArray[self::FLD_RECURRING_TYPE])) && is_array($dataArray[self::FLD_RECURRING_TYPE]))
			$dataArray[self::FLD_RECURRING_TYPE] = RecurringType::parseRecurringDataArrayToString($dataArray[self::FLD_RECURRING_TYPE]);

		$connection = $this->getConnection();
        $connection->beginTransaction();
		try {
			self::getInstance($this->getConnectConfigs())
				->setFieldsNames($this->getFieldsNames())
				->setFieldsValues($dataArray)
				->save();

            $connection->commit();
		}
		catch(Exception $error) {
            $connection->rollBack();
			throw new Exception("Data not saved.");
		}

        $this->closeConnection();
	}

	/**
	 * Delete data event by id.
	 * @param $id
	 * @throws Exception
	 */
	public function deleteById($id)
	{
        $connection = $this->getConnection();
        $connection->beginTransaction();
		try {
			self::getInstance($this->getConnectConfigs())
				->setFieldsNames($this->getFieldsNames())
				->setFieldsValues(array(self::FLD_ID => $id))
				->delete();

            $connection->commit();
		}
		catch(Exception $error) {
            $connection->rollBack();
			throw new Exception("Data not deleted.");
		}

        $this->closeConnection();
	}

	/**
	 * Get max recurring end date for recurring type.
	 * @param $recurringType
	 * @param $startDateStr
	 * @param $eventLength
	 * @return int
	 */
	public function getRecurringEndDateStr($recurringType, $startDateStr, $eventLength) {
		$endDateStamp = RecurringType::getRecurringEndDate($recurringType, $this->getDateTimestamp($startDateStr), $eventLength);
		return date(SchedulerHelperDate::FORMAT_DEFAULT, $endDateStamp);
	}
}