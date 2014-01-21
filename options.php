<?php

if ( !isset($GLOBALS['USER']) ) {
	$GLOBALS['USER'] = 3; // Активный пользователь будет меняться по авторизации
}
if ( !isset($GLOBALS['PLAYER']) ) {
	$GLOBALS['PLAYER'] = 0; // Активный плеер
}


$DEBUG = 1;

define( "TSDECRYPT", "/usr/src/video/tsdecrypt-9.0/tsdecrypt" );
define( "DVBLAST", "/usr/src/video/dvblast/dvblast" );
define( "DVBLASTCTL", "/usr/src/video/dvblast/dvblastctl" );
define( "MULTICAT", "/usr/src/video/multicat/trunk/multicat" );
define( "RECORD_DIR", "/tmp/" );  // Папка для хранения таймшифта и записей телепередач

// Настройка на транспондер
define( "HAS_LOCK", "HAS_LOCK" );


define( "RESULT_OK", json_encode( array("ok"=>true) ) );
define( "RESULT_NOT_OK", json_encode( array("ok"=>false) ) );


/*
Пользовательские адреса

@239.1.1.1:1001 - Первый пользователь
@239.1.1.1:1002 - и тд.
*/

// Список спутников и настройка diseq
$sat = array();
$sat['36'] = 1;
$sat['13'] = 2;


// Список адресов пользователя 
$ipaddr = array();
$ipaddr[0] = "239.1.1.1:5001";
$ipaddr[1] = "239.1.1.1:5002";
$ipaddr[2] = "239.1.1.1:5003";
$ipaddr[3] = "239.1.1.1:5004";

// Список адресов пользователя 
$ipaddrin = array();
$ipaddrin[0] = "229.1.1.1:5001";
$ipaddrin[1] = "229.1.1.1:5002";
$ipaddrin[2] = "229.1.1.1:5003";
$ipaddrin[3] = "229.1.1.1:5004";




// Список медиаплееров
$players = array();

$players[0]['name'] = "Джакузная";
$players[0]['ipaddr'] = "192.168.1.10";
$players[0]['udpxy'] = 0;


$players[1]['name'] = "Спальня Зеркальная";
$players[1]['ipaddr'] = "192.168.1.11";
$players[1]['udpxy'] = 0;


$players[2]['name'] = "Спальня Сафари";
$players[2]['ipaddr'] = "192.168.1.12";
$players[2]['udpxy'] = 0;

$players[3]['name'] = "Спальня Яхта";
$players[3]['ipaddr'] = "192.168.1.14";
$players[3]['udpxy'] = 0;

$players[4]['name'] = "Ванная";
$players[4]['ipaddr'] = "192.168.1.13";
$players[4]['udpxy'] = 0;


$players[5]['name']   = "Кинозал";
$players[5]['ipaddr'] = "192.168.1.19";
$players[5]['udpxy']  = 1;

$players[6]['name'] = "Кухня";
$players[6]['ipaddr'] = "192.168.1.16";
$players[6]['udpxy'] = 0;

$players[7]['name'] = "Столовая";
$players[7]['ipaddr'] = "192.168.1.17";
$players[7]['udpxy'] = 0;

$players[8]['name'] = "Подвал";
$players[8]['ipaddr'] = "192.168.1.18";
$players[8]['udpxy'] = 0;


?>