<?php

class TestBaseHelper
{
    private $_dbsm, $_host, $_db_name, $_user, $_password, $_table_name;
    private $_PDO = null;

    private $_fields;

    public function __construct($configs = array())
    {
        $this->_dbsm = isset($configs["dbsm"]) ? $configs["dbsm"] : "mysql";
        $this->_host = isset($configs["host"]) ? $configs["host"] : "localhost";
        $this->_db_name = $configs["db_name"];
        $this->_user = $configs["user"];
        $this->_password = $configs["password"];
        $this->_table_name = $configs["table_name"];
        $this->_fields = $configs["fields"];
    }

    private function getConnection()
    {
        $PDO_options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );

        $dsn = $this->_dbsm . ":host=" . $this->_host . ";dbname=" . $this->_db_name;
        $this->_PDO = ($this->_PDO) ? $this->_PDO : new PDO($dsn, $this->_user, $this->_password,$PDO_options);
        return $this->_PDO;
    }

    private function closeConnection()
    {
        unset($this->_PDO);
        $this->_PDO = null;
    }

    public function dropTable()
    {
        $conn = $this->getConnection();
        $conn->prepare("DROP TABLE IF EXISTS `$this->_table_name`;")->execute();
        $this->closeConnection();
    }

    public function resetTable()
    {
        $this->dropTable();
        $conn = $this->getConnection();
        $sql = "CREATE TABLE `$this->_table_name`(";
        $f = $this->_fields;
        $fieldsCount = count($f);
        $elNumb = 1;

        foreach($f as $name=>$type) {
            $sql .= "`$name` $type";
            if ($elNumb++ != $fieldsCount)
                $sql .= ",";
        }
        $sql .= ");";

        $conn->prepare($sql)->execute();
        $this->closeConnection();
    }

    public function insertDataFromJSON($data){
        $f = $this->_fields;
        $fieldsCount = count($f);

        $sql = "INSERT INTO `$this->_table_name`(";

        $elNumb = 1;
        foreach($f as $name=>$type) {
            $sql .= "`$name`";
            if ($elNumb++ != $fieldsCount)
                $sql .= ",";
        }

        $sql.= ") VALUES ";

        $evsCount = count($data);
        for($i = 0; $i < $evsCount; $i++) {
            $sql .= "(";
            $event = $data[$i];
            $elNumb = 1;
            foreach($f as $name=>$type) {
                $sql .= isset($event[$name]) && !is_null($event[$name]) ? "'".$event[$name]."'":"''";

                if ($elNumb++ != $fieldsCount)
                    $sql .= ",";
            }
            $sql.=")";
            if($i + 1 != $evsCount)
                $sql.=",";
        }
        $sql .= ";";

        $conn = $this->getConnection();
        $conn->prepare($sql)->execute();
        $this->closeConnection();
    }

    public function getDataFromBase(){
        $sql = "SELECT * FROM $this->_table_name";
        $conn = $this->getConnection()->prepare($sql);
        $conn->execute();
        $data = $conn->fetchAll();
        $this->closeConnection();
        return $data;
    }
}