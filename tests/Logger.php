<?php

class Logger
{
    private $_stepNumber = 0;

    public function logStart($name = NULL)
    {
        $this->_stepNumber = 0;
        print "Test started. $name\n";
    }

    public function logStep(){
        print "Step ".(++$this->_stepNumber)."\n";
    }

    public function logEnd($name = NULL){
        $this->_stepNumber = 0;
        print "Test finished. $name\n";
    }

    public function info($msg){
        print "INFO: $msg\n";
    }

    public function warning($msg){
        print "WARNING: $msg\n";
    }

    public function error($msg){
        print "ERROR: $msg\n";
    }
}