<?php
require_once "Logger.php";
require_once "TestBaseHelper.php";
require_once "TestDataHelper.php";
require_once "TestConfig.php";
use DHTMLX_Scheduler\Helper;


class SchedulerHelperTest extends \PHPUnit_Framework_TestCase
{
    private $_baseHelper;
    private $_logger;

    public function __construct()
    {
        $this->_logger = new Logger();

        $this->_baseHelper = new TestBaseHelper(array(
            "dbsm" => TestConfig::DBSM,
            "host" => TestConfig::HOST,
            "db_name" => TestConfig::DB_NAME,
            "user" => TestConfig::USER,
            "password" => TestConfig::PASSWORD,
            "table_name" => TestConfig::TEMP_TABLE_NAME,
            "fields" => TestConfig::$fields
        ));
    }

    public function testGetData()
    {
        $testName = "getData";

        $this->_logger->logStart($testName);
        $schedHelper = new Helper(
            array(
                "dbsm" => TestConfig::DBSM,
                "host" => TestConfig::HOST,
                "db_name" => TestConfig::DB_NAME,
                "user" => TestConfig::USER,
                "password" => TestConfig::PASSWORD,
                "table_name" => TestConfig::TEMP_TABLE_NAME
            )
        );
        $schedHelper->setFieldsNames(array(
            $schedHelper::FLD_ID => "id",
        ));


        $dataHelp = new TestDataHelper($testName);
        $dataPacks = $dataHelp->getTestDataList();

        if($dataPacks) {
            for ($i = 0; $i < count($dataPacks); $i++) {
                $this->_logger->logStep($testName);
                $this->_logger->info("$dataPacks[$i] bunch processing....");
                $source = $dataHelp->getTestSourceData($dataPacks[$i]);
                $target = $dataHelp->getTestTargetData($dataPacks[$i]);

                if(!$source || !$target){
                    $this->_logger->warning("There is no data. Bunch is skipped");
                    continue;
                }

                $this->_baseHelper->resetTable();
                $this->_baseHelper->insertDataFromJSON($source['data']);

                $helperData = $schedHelper->getData($source["settings"]["start_date"], $source["settings"]["end_date"]);
                $dataHelp->writeObjectToFile($helperData, "_helperData.txt", $dataPacks[$i]);
                $this->assertTrue($dataHelp->compareDataBunches($helperData, $target["data"], TestConfig::$fields),
                    "Helper and Scheduler data has difference");
            }
        }

        $this->_logger->logEnd($testName);
    }
}