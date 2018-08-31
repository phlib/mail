#!/usr/bin/env php
<?php

require_once(dirname(dirname(__DIR__)) . '/vendor/autoload.php');

use Phlib\Mail\Factory;


function parseMail(Factory $factory, $source)
{
    $factory->createFromString($source);
}

$source = file_get_contents(dirname(__DIR__) . '/__files/attachments-source.eml');

$factory = new Factory();

$runSeconds = 5;
$parses = 0;
$start =  microtime(true);
while ((microtime(true) - $start) < 5) {
    parseMail($factory, $source);
    $parses++;
}
$parsesPerSecond = $parses / $runSeconds;
echo $parsesPerSecond;
