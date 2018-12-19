<?php
$files = glob(__DIR__ . '/helpers/*.php');
foreach ($files as $file) {
    require_once $file;
}
unset($files);
