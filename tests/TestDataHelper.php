<?php
require_once "TestConfig.php";

class TestDataHelper
{
    const DATA_FOLDER_PREFIX = "Data_";
    const SOURCE_NAME = "source.json";
    const TARGET_NAME = "target.json";

    private $_dataFolder;

    private function getJSONDataFromFile($name, $type)
    {
        $file = dirname(__FILE__) . "/" . $this->_dataFolder . "/" . $name . "/" . $type;
        if (!file_exists($file)) return null;

        return json_decode(file_get_contents($file),true);
    }

    private function correctNoEndRecType($type){
        $noPos = strpos($type, "#no");
        if($noPos){
            $type = substr_replace($type, "", $noPos + 1);
        }
        return $type;
    }

    public function __construct($testName)
    {
        $this->_dataFolder = self::DATA_FOLDER_PREFIX . $testName;
    }

    public function getTestDataList()
    {
        $dir = dirname(__FILE__) . "/" . $this->_dataFolder;
        if (!file_exists($dir)) return null;
        $folderItems = scandir($dir);
        $folders = array();
        foreach ($folderItems as $item) {
            if ($item === '.' || $item === '..') continue;
            if (is_dir($dir . "/" . $item))
                array_push($folders, $item);
        }
        return $folders;
    }

    public function getTestSourceData($name)
    {
        return $this->getJSONDataFromFile($name, self::SOURCE_NAME);
    }

    public function getTestTargetData($name)
    {
        return $this->getJSONDataFromFile($name, self::TARGET_NAME);
    }

    public function compareDataObjects($helperObj, $schedObj, $fields)
    {
        foreach($fields as $key=>$value){
            $hVal = isset($helperObj[$key]) ? $helperObj[$key]: "";
            $sVal = isset($schedObj[$key])? $schedObj[$key] : "";
            if($key == "rec_type"){
                $sVal = $this->correctNoEndRecType($sVal);
                $hVal = $this->correctNoEndRecType($hVal);
            }
            if($hVal != $sVal) {
                return false;
            }
        }
        return true;
    }

    public function compareDataBunches($helperData, $schedData, $fields)
    {
        $helpLength = count($helperData);
        $schedLength = count($schedData);

        if($helpLength != $schedLength) return false;

        for($i = 0; $i < $helpLength; $i++){
            $objHasSame = false;
            for($j = 0; $j < $schedLength; $j++){
                if($this->compareDataObjects($helperData[$i], $schedData[$j], $fields)) {
                    array_splice($schedData, $j, 1);
                    $schedLength = count($schedData);
                    $objHasSame = true;
                    break;
                }
            }
            if(!$objHasSame){
                return false;
            }
        }

        return true;
    }

    public function writeObjectToFile($obj, $name, $bunch="")
    {
        ob_start();
        var_dump($obj);
        $output = ob_get_clean();
        if ($bunch)
            $bunch = "/$bunch";
        $file = dirname(__FILE__) . "/" . $this->_dataFolder . $bunch . "/" . $name;
        file_put_contents($file, $output);
    }

    public function prepateDataForHelper($data, $helper)
    {
        foreach ($data as &$event) {
            if (!isset($event["end_date"]) && isset($event["recurring_type"]) && isset($event["length"])) {
                $event["end_date"] = $helper->getRecurringEndDateStr($event["recurring_type"], $event["start_date"], $event["length"]);
            }
        }

        return $data;
    }

    public function saveDataWithHelper($data, $helper)
    {
        foreach($data as $event){
            $helper->saveData($event);
        }
    }
}