<?php

file_exists('config.php') or die("Create a config.php file first.\n");

require_once 'config.php';
require_once 'curl/curl.php';
require_once 'lib'.DIRECTORY_SEPARATOR.'txerpa.php';
require_once 'lib'.DIRECTORY_SEPARATOR.'txerpa_exception.php';
