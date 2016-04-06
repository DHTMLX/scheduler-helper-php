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

    private function getHelper(){
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
        
        $schedHelper->config["debug"] = false;
        $schedHelper->config["occurrence_timestamp_in_utc"] = true;
        return $schedHelper;
    }
    
    public function __construct()
    {
        date_default_timezone_set('Europe/Minsk');
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

    public function __destruct()
    {
        $this->_baseHelper->dropTable();
    }

    public function testGetData()
    {
        $testName = "getData";

        $this->_logger->logStart($testName);
        $schedHelper = $this->getHelper();

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

    public function testSaveData_Insert(){
        $testName = "saveData";
        $this->_logger->logStart($testName);
        $schedHelper =  $this->getHelper();

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
                $sourceData = $dataHelp->prepateDataForHelper($source["data"], $schedHelper);
                $dataHelp->saveDataWithHelper($sourceData, $schedHelper);

                $dataFromBase = $this->_baseHelper->getDataFromBase();

                $dataHelp->writeObjectToFile($dataFromBase, "_dataFromBase.txt", $dataPacks[$i]);
                $this->assertTrue($dataHelp->compareDataBunches($target["data"], $dataFromBase, TestConfig::$fields),
                    "Helper and Scheduler data has difference");
            }
        }

        $this->_logger->logEnd($testName);
    }

    public function testSaveData_Update(){
        $testName = "saveDataUpdate";
        $this->_logger->logStart($testName);
        $schedHelper =  $this->getHelper();

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

                $this->_baseHelper->insertDataFromJSON($source["insert_data"]);
                $sourceData = $dataHelp->prepateDataForHelper($source["data"], $schedHelper);
                $dataHelp->saveDataWithHelper($sourceData, $schedHelper);

                $dataFromBase = $this->_baseHelper->getDataFromBase();

                $dataHelp->writeObjectToFile($dataFromBase, "_dataFromBase.txt", $dataPacks[$i]);
                $this->assertTrue($dataHelp->compareDataBunches($target["data"], $dataFromBase, TestConfig::$fields),
                    "Helper and Scheduler data has difference");
            }
        }

        $this->_logger->logEnd($testName);
    }

    public function testdeleteById(){
        $testName = "deleteById";
        $this->_logger->logStart($testName);
        $schedHelper = $this->getHelper();

        $dataHelp = new TestDataHelper($testName);
        $dataPacks = $dataHelp->getTestDataList();
        if($dataPacks) {
            for ($i = 0; $i < count($dataPacks); $i++) {
                $this->_logger->logStep($testName);
                $this->_logger->info("$dataPacks[$i] bunch processing....");
                $source = $dataHelp->getTestSourceData($dataPacks[$i]);


                if(!$source){
                    $this->_logger->warning("There is no data. Bunch is skipped");
                    continue;
                }

                $this->_baseHelper->resetTable();

                $this->_baseHelper->insertDataFromJSON($source["insert_data"]);

                foreach($source["data"] as $event){
                    $schedHelper->deleteById($event["id"]);
                    $dataFromBase = $this->_baseHelper->getDataFromBase($event["id"]);
                    $this->assertTrue(count($dataFromBase) === 0,
                        "Event wasn't removed");
                }
            }
        }

        $this->_logger->logEnd($testName);
    }
}