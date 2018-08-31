#!/usr/bin/env php
<?php

require_once(dirname(dirname(__DIR__)) . '/vendor/autoload.php');

use Phlib\Mail\Factory;


function parseMail(Factory $factory, $source)
{
    $factory->createFromString($source);
}

$attemptCount = 100;
$source = file_get_contents(dirname(__DIR__) . '/__files/attachments-source.eml');

$factory = new Factory();

parseMail($factory, $source);
gc_collect_cycles();

$startM = memory_get_usage();
while ($attemptCount-- > 0) {
    parseMail($factory, $source);
    gc_collect_cycles();
}
$endM = memory_get_usage();
$memDiff = $endM - $startM;
echo $memDiff;
