<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__.'/../classes/database.php';
require_once __DIR__.'/../classes/auth.php';
require_once __DIR__.'/../classes/pageant.php';
require_once __DIR__.'/../classes/rounds.php';
require_once __DIR__.'/../classes/scores.php';
require_once __DIR__.'/../classes/awards.php';
if (!isset($GLOBALS['APP_DEV_INITIALIZED'])) { ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL); $GLOBALS['APP_DEV_INITIALIZED']=true; }
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
