<?php

namespace Scheduler;
use PDO, Exception;

class Connector
{
    private $_dbsm, $_host, $_db_name, $_user, $_password, $_table_name;
    private $_PDO;

    protected function __construct($configs = array())
    {
        $this->_dbsm = isset($configs["dbsm"]) ? $configs["dbsm"] : "mysql";
        $this->_host = isset($configs["host"]) ? $configs["host"] : "localhost";
        $this->_db_name = $configs["db_name"];
        $this->_user = $configs["user"];
        $this->_password = $configs["password"];
        $this->_table_name = $configs["table_name"];
    }

    protected function getTableName()
    {
        return $this->_table_name;
    }

    private function getConfigStringPDO()
    {
        return "{$this->_dbsm}:host={$this->_host};dbname={$this->_db_name}";
    }

    public function getPDO()
    {
        $PDO_options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );

        $this->_PDO = ($this->_PDO) ? $this->_PDO : new PDO($this->getConfigStringPDO(), $this->_user, $this->_password, $PDO_options);
        return $this->_PDO;
    }

    protected function save()
    {
        if(!is_null($this->getFieldsValues(Helper::FLD_ID)))
            $this->update();
        else
            $this->insert();
    }

    protected function update()
    {
        if(is_null($this->getFieldsValues(Helper::FLD_ID)))
            throw new Exception("For updating data needs value of field Helper::FLD_ID");

        $fieldsValues = $this->getFieldsValues();
        $sqlSetPart = [];
        foreach($fieldsValues as $field => $value)
            array_push($sqlSetPart, "{$field}='{$value}'");

        $sql = "
            UPDATE
              {$this->_table_name}
            SET
              ".join("," ,$sqlSetPart)."
            WHERE
                ".$this->getIdFieldName()." = ".$this->getFieldsValues(Helper::FLD_ID)."
        ";

        if($this->config["debug"])
            echo("Update operation sql: ".$sql."<BR>");

        $this->getPDO()->prepare($sql)->execute();
    }

    protected function insert()
    {
        $fieldsValues = $this->getFieldsValues();
        $sqlFields = join(",", array_keys($fieldsValues));
        $sqlValues = "'".join("','", array_values($fieldsValues))."'";
        $sql = "INSERT INTO {$this->_table_name} ({$sqlFields}) values ({$sqlValues})";

        if($this->config["debug"])
            echo("Insert operation sql: ".$sql."<BR>");

        $this->getPDO()->prepare($sql)->execute();
    }

    protected function delete()
    {
        $dataId = $this->getFieldsValues(Helper::FLD_ID);
        if(is_null($dataId))
            throw new Exception("For deleting needs value of FLD_ID");

        $sql = "
            DELETE FROM
                ".$this->_table_name."
            WHERE
                ".$this->getIdFieldName()." = '{$dataId}'";

        if($this->config["debug"])
            echo("Delete operation sql: ".$sql."<BR>");

        $this->getPDO()->prepare($sql)->execute();
    }
}