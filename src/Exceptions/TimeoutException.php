<?php namespace Konduto\Exceptions;

class TimeoutException extends KondutoException {

    public $message = "Communication timeout for Konduto API";
    public function __construct() {}
}