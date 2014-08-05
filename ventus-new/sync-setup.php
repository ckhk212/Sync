<?php
require_once("sync.php");

$sync = new Sync();
var_dump($sync);
$sync->setFiscalYearRange();
// $sync->removeTempTables();
// $sync->generateTempTables();
$sync->executeSync();