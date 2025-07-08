<?php

$helperDir = __DIR__;

foreach (glob($helperDir . '/*.php') as $file) {
    require_once $file;
}