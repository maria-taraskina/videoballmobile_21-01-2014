<?php

require_once "options.php";
require_once "mysql.php";

/*
    ����������� ��� ������ ����
    �� �������������� ������������ ����� ����� ���� �������� ��������� 

*/

$date = new DateTime();
$filename = "/var/www/html/videoballmobile/run/iptv-" . $GLOBALS['USER'] . ".json";
$dvb_channels = "/var/www/html/videoballmobile/run/dvb-channels-" . $GLOBALS['USER'];
$dvblast_sock = "/tmp/dvblast" . $GLOBALS['USER'] . ".sock";

$ATTR_TYPE = -1;


if ( isset($GLOBALS['db']) ) {

} else {

    $db = new mysql();
    $db->debug = false;
    $sql = 'SET NAMES UTF8';

    $db->query($sql);
}

// Надо переделать под массив
function dvbs_make_channels( $adr, $pid ) {
	$fp = fopen( $GLOBALS['dvb_channels'], 'w' );
	fwrite( $fp, $adr."/udp 1 " . $pid );
	fclose( $fp );
	
	return filesize( $GLOBALS['dvb_channels'] );
}
function dvbs_make_command( $vs ) {
	$r = DVBLAST . " ";
	
	// Логи в системный файл
	$r .= "-q ";
	
	$r .= "-r " . $GLOBALS['dvblast_sock'] . " ";
	
	// Выбор интерфейса
	$r .= "-a " . $GLOBALS['USER'] . " ";
	
	// Выбор файла с адресами трансляции
	$r .= "-c " . $GLOBALS['dvb_channels'] . " ";

	// Установка транспондера
	if ( ($vs['pol'] == 'R') or ($vs['pol'] == 'L') ) {
		if ($vs['pol'] == 'L') { 
			$r .= "-f " . trim( $vs['freq'] - 150000) . " ";
		}
		if ($vs['pol'] == 'R') {
			$r .= "-f " . trim( $vs['freq'] - 10750000 ) . " ";
		}
	} else {
		$r .= "-f " . trim( $vs['freq'] ) . " ";
	}
	
	// Установка символьной скорости
	$r .= "-s " . trim( $vs['sr'] ) . " ";
	
	// Установка поляризации
	if ( ($vs['pol'] == 'H') or ($vs['pol'] == 'L') ) {
		$r .= "-v 18 ";
	} else {
		$r .= "-v 13 ";
	}
	
	// Установка модуляции
	if ( isset($vs['mod']) ) { $r .= "-m " . trim( $vs['mod'] ) . " "; }
	
	$r .= "-u -U -W -Y ";

	// Установка DiSeQ
	if ( isset( $GLOBALS['sat'][$vs['sat']]) ) {
	 	$r .= "-S " . $GLOBALS['sat'][$vs['sat']] . " ";
	} 

	if ( $GLOBALS['DEBUG'] == 1 ) {
	 	print  'dvblast cmd: ' . $r;
	}

	return $r;
}

function dvbs_make_decrypt( $vs ) {
	$r = TSDECRYPT . " ";
	
	// Указываем входящий адрес 
	$r .= "-I " . $vs['addrin'] . " ";
	
	// Указываем адрес расшифрованного потока
	$r .= "-O " . $vs['addrout'] . " ";
	
	if ( $vs['sat']['encrypt'] == "BISS") {
		$r .= "-Q " . substr( $vs['sat']['code'], 0, 6 ) . substr( $vs['sat']['code'], 8, 6 );
	}
	if ( $vs['sat']['encrypt'] == "VIACCESS" | $vs['sat']['encrypt'] == "DRECRYPT"  ) {
	
		if ( isset($vs['sat']['oc_ecm']) & $vs['sat']['oc_ecm'] != "" ) {
			$r .= " -X " . $vs['sat']['oc_ecm'];
		}
	
		$r .= " -e -c " . $vs['sat']['encrypt'] . " -A NEWCAMD " . "-s " . $vs['sat']['oc_host'].":". $vs['sat']['oc_port'] . " -U " . $vs['sat']['oc_user'] . " -P " . $vs['sat']['oc_pwd'] . " -B " . $vs['sat']['oc_key'];
	}

	if ( $GLOBALS['DEBUG'] == 1 ) {
	 	print  'tsdecrypt cmd: ' . $r;
	}
	
	return $r;
}
function dvbs_make_ctrl( $cmd ) {
//	exec("sudo rm /tmp/dvblast" . $GLOBALS['USER'] . ".sock" );
	
	$r = DVBLASTCTL . " ";
	
	$r .= "-x xml ";
	
	$r .= "-r " . $GLOBALS['dvblast_sock'] . " ";
	
	$r .= $cmd;
	
	return $r;
}

function dvbs_status2() {
	$cmd = dvbs_make_ctrl("fe_status");
	$pid = (int)shell_exec($cmd . " > /tmp/dvblast_status & echo $!" );
}

function _dvbs_status( $c ) {
    $cmd = dvbs_make_ctrl( $c );

    ob_start();
    passthru( 'sudo ' . $cmd . '  ' , $result );
    $r = ob_get_contents();
    ob_end_clean();

    return $r;
}

// Функция определяет настроился транспондер или нет 
function dvbs_status_has_lock() {
    $xml = _dvbs_status( 'fe_status' );
    
    // Ищем в xml признак лоченого сигнала
    if ( _dvbs_xml( $xml, 'STATUS', 'status', HAS_LOCK ) ) {
	return RESULT_OK;
    }
    return RESULT_NOT_OK;
}

// Функция определяет есть ли указанный канал в потоке на настроенном транспондере 
function dvbs_status_has_channel( $channel ) {
    $xml = _dvbs_status('get_sdt');
    
    if ( _dvbs_xml( $xml, 'SERVICE', 'sid', $channel ) ) {
	return RESULT_OK;
    } 
    return RESULT_NOT_OK;
}

function _dvbs_xml( $x, $tag, $attr, $value ) {

    $xml = new DOMDocument( );
    $xml->loadXML( $x );
    
    $r = array();
    $params = $xml->getElementsByTagName( $tag );

    foreach ( $params as $param ) {
	if ( $param->getAttribute( $attr ) == $value ) {
	    return 1;
	}
    }
    return 0;
} 


function iptv_options_read() {
	if ( file_exists( $GLOBALS['filename'] ) ) {
	
		$fp = fopen($GLOBALS['filename'], 'r');
	        $s = json_decode( fread($fp, filesize($GLOBALS['filename'])), True );
		fclose($fp);

		return $s;

	} else {
		return;
	}
}

function iptv_options_save($s) {

	$fp = fopen( $GLOBALS['filename'], 'w' );
	fwrite( $fp, json_encode($s) );
	fclose( $fp );

	return filesize($GLOBALS['filename']);
}

function get_vars( &$arr, $res ) {
	$cnt = count( $res );
	
	for ( $i=0; $i < $cnt; $i++ ) {
		$arr[$res[$i][0]] = $res[$i][1];
	}
}

function dvbs_play( $channel_id ) {
	$streams = iptv_options_read();
	
	
	// Отпустили паузу играем с диска
	if ( isset($streams['IPtoFile']['pid']) ) {
	
		$toFile = $streams['IPtoFile'];
		$start_pause = $toFile['start']  * 27000000;

		$toIP['addrin'] = $toFile['addrin'];
		$toIP['channel_id'] = $toFile['channel_id'];
		$toIP['desc'] = $toFile['desc'];
 		$toIP['addrout'] = $toFile['addrout'];
 		$toIP['directory'] = $toFile['directory'];

		$toIP['pid'] = (int)shell_exec( MULTICAT . " -u -U -k " . $start_pause . " " . $toIP['directory']. " " .$toIP['addrout']. " > /dev/null & echo $!") ;
		$toIP['start'] = $GLOBALS['date']->getTimestamp();

                $streams['FileToIP'] = $toIP;
	
	
	} else {

		// Если работает другой канал его надо вырубить и включить 
		if ( (isset($streams['DVBtoIP']['pid'])) and ($streams['DVBtoIP']['channel_id'] <> $channel_id) ) {
			dvbs_stop();
		} 
		
		if (  $streams['DVBtoIP']['channel_id'] == $channel_id ) {
			// Ничего не делаем, включили тот же канал
		
		} else {

 			$sql = "SELECT `p`.`title`, `p`.`start`, `p`.`stop` FROM `video_source` as `s` JOIN `video_programma` as `p` ON (`s`.`id_channel` = `p`.`channel` AND `p`.`start` <= now() AND `p`.`stop` >= now()) WHERE `s`.`id_video` = " . (int)$channel_id;
 			$prog = $GLOBALS['db']->data($sql);
 			
 			
 			
 			
			$sql = "CALL `get_video_attr`(" . (int)$channel_id . ")";
 			$res = $GLOBALS['db']->data($sql, MYSQL_NUM);

// print $sql; 			
// print_r($res); 			
			$toIP = array();

			$toIP['cmd'] = "play";  // Поток открыт для просмотра 
			$toIP['start'] = 0;
			get_vars($toIP['sat'], $res);
			$toIP['channel'] = '';
			$toIP['channel_id'] = $channel_id;
			$toIP['desc'] = '';
			$toIP['addrin'] = $GLOBALS['ipaddrin'][$GLOBALS['USER']];
			$toIP['addrout'] = $GLOBALS['ipaddr'][$GLOBALS['USER']];
			$toIP['programma']['title'] = $prog[0]['title'];
			$toIP['programma']['start'] = $prog[0]['start'];
			$toIP['programma']['stop'] = $prog[0]['stop'];
			
			
			
			if ( !isset($toIP['sat']['encrypt']) ) {
				$toIP['addrin'] = $toIP['addrout'];
			}
		

			dvbs_make_channels( $toIP['addrin'], $toIP['sat']['pid'] );

			$cmd1 = 'sudo ' . dvbs_make_command( $toIP['sat'] );
//  print $cmd1;
			$toIP['pid'] = (int)shell_exec($cmd1 . " > /dev/null & echo $!" );
			$toIP['start'] = $GLOBALS['date']->getTimestamp();
			
			
			if ( isset($toIP['sat']['encrypt']) ) {

				$cmd2 = 'sudo '. dvbs_make_decrypt( $toIP );

				$toCrypt = array();
				$toCrypt['pid'] = (int)shell_exec( $cmd2 . " > /dev/null & echo $!" );
				$streams['DECRYPT'] = $toCrypt;
			
			}

            		$streams['DVBtoIP'] = $toIP;
		}
	
	
	}
	$size =  iptv_options_save( $streams );
	
	return json_encode( $streams );
}


function dvbs_pause() {

	$streams = iptv_options_read();

	if ( isset($streams['DVBtoIP']) ) {
	
		$toIP = $streams['DVBtoIP'];

		$toFile = array();
		$toFile['cmd'] = "pause";
		$toFile['pid'] = 0;
		$toFile['addrin'] = $toIP['addrin'];
		$toFile['addrout'] = $toIP['addrout'];
		$toFile['channel_id'] = $toIP['channel_id'];
		$toFile['desc'] = $toIP['desc'];
		$toFile['start'] = $GLOBALS['date']->getTimestamp();
		$toFile['directory'] = "/tmp/".$toIP['channel_id'];  // �� ������ �������� ������������� ������������
		
		if ( !is_dir($toFile['directory']) ) {
			mkdir ( $toFile['directory'] ); 
		}


		$toFile['pid'] = (int)shell_exec( MULTICAT . " -u -U @".$toFile['addrin']." ".$toFile['directory']." > /dev/null & echo $!") ;
		sleep(2);
		//exec("kill " . $streams['IPtoIP']['pid']);
		
		
		// Потока с IP на IP больше не будет
		unset($streams['IPtoIP']);
		$streams['IPtoFile'] = $toFile;
		
		
	}
	
	// Ставим на паузу поток с файла, т.е.просто  хлопаем процесс 
	// и сохраняем время постановки на паузу
	if ( isset($streams['FileToIP']) ) {

		$streams['IPtoFile']['start'] = $GLOBALS['date']->getTimestamp();
		
		exec("sudo kill " . $streams['FileToIP']['pid']);
		unset($streams['FileToIP']);
	}


	$size = iptv_options_save( $streams );
}



function add_video_record( $channel_id ) {

	$GLOBALS['ATTR_TYPE'] = 4; // Записанное видео

	$sql = "INSERT INTO `video_source` (`id_type`, `name`, `title`) VALUES (4, 'Записанное видео', '')";
	$GLOBALS['db']->query($sql);
	
	$res = $GLOBALS['db']->data("SELECT LAST_INSERT_ID() as `id`");
	$id_video = $res[0]['id'];
	
	// Ссылка на источник
	add_attr( $id_video, 8, $channel_id );
	
	// Начало записи
	add_attr( $id_video, 9, $GLOBALS['date']->getTimestamp() );	

}

function add_video_file( $vs ) {
	$GLOBALS['ATTR_TYPE'] = 3; // Видео файлы
	
	if ( !file_exists( $vs['path']) ) {
		return False;
	}
	
	$mov = new ffmpeg_movie( $vs['path'] );
	
	$sql = "INSERT INTO `video_source` (`id_type`, `name`, `title`) VALUES (" .$GLOBALS['ATTR_TYPE'] . ", '" . esc($vs['name']) . "', '" . esc($vs['title']). "')";
	$GLOBALS['db']->query($sql);
	
	$res = $GLOBALS['db']->data("SELECT LAST_INSERT_ID() as `id`");

	if ( $res[0]['id'] > 0 ) {
		$id_video = $res[0]['id'];
		
		// Путь к файлу
		add_attr( $id_video, 7, $vs['path'] );
		
		// Продолжительность видео файла
		add_attr( $id_video, 23, $mov->getDuration() );
		
	}	
	
	return True;
}

function add_dvbs_channel( $vs ) {
	$GLOBALS['ATTR_TYPE'] = 1; // Спутниковое телевидение
	
	if ( isset($vs['epg']) ) {
		$epg = $vs['epg'];
	} else {
	 	$epg = 0;
	}



	$sql = "INSERT INTO `video_source` (`id_type`, `id_channel`, `name`, `title`, `active`) VALUES (" . $GLOBALS['ATTR_TYPE'] . ", " . $epg . ", '" . esc($vs['name']) . "', '', 1)";
	$GLOBALS['db']->query($sql);
	
	$res = $GLOBALS['db']->data("SELECT LAST_INSERT_ID() as `id`");
	
	if ( $res[0]['id'] > 0 ) {
		$id_video = $res[0]['id'];
		
		// Градус 
		add_attr( $id_video, 22, $vs['sat'] ); 
	
		// Транспондер
		add_attr( $id_video, 1, $vs['freq'] );
	
		// Символьная скорость
		add_attr( $id_video, 2, $vs['sr'] );
	
		// Коррекция ошибок
		add_attr( $id_video, 3, $vs['fec'] );
	
		// Поляризация
		add_attr( $id_video, 4, $vs['pol'] );
	
		// Программа
		add_attr( $id_video, 5, $vs['prog'] );
		
		// Модуляция
		if ( isset($vs['mod']) ) {
		    add_attr( $id_video, 16, $vs['mod'] );
		}
		// Шифрование
		if ( isset($vs['encryption']) ) {
			add_attr( $id_video, 14, $vs['encryption'] );
		
			if ( $vs['encryption'] == "BISS" ) {
				add_attr( $id_video, 15, $vs['code'] );
			}
			
			if ( ($vs['encryption'] == 'VIACCESS') or ($vs['encryption'] == 'CONAX') or ($vs['encryption'] == 'IRDETO') or ($vs['encryption'] == 'DRECRYPT') ) {
			
				add_attr( $id_video, 17, $vs['oc_host'] );  // Хост для подключения к OSCAM
				add_attr( $id_video, 21, $vs['oc_port'] );  // Порт для подключения к OSCAM
				add_attr( $id_video, 18, $vs['oc_user'] );  // Имя пользователя 
				add_attr( $id_video, 19, $vs['oc_pwd'] );   // Пароль
				add_attr( $id_video, 20, $vs['oc_key'] );   // Ключ
				if ( isset($vs['oc_ecm']) ) add_attr( $id_video, 24, $vs['oc_ecm'] );   // CA PID
				
			}
			
		}
	}
}

function add_attr( $parent_id, $attr, $value ) {

	$sql = "INSERT INTO `video_attr_info` (`id_attr`, `value`) VALUES (" . (int)$attr . ", '" . esc($value) . "')";
	$GLOBALS['db']->query($sql);
	
	$res = $GLOBALS['db']->data("SELECT LAST_INSERT_ID() as `id`");
	$id_info = $res[0]['id'];
	
	$sql = "INSERT INTO `video_attr_data` (`id_source`, `id_type`, `id_info`) VALUES (" . (int)$parent_id . ", " . (int)$GLOBALS['ATTR_TYPE'] . ", " . $id_info . ")";
	$GLOBALS['db']->query($sql);

	$res = $GLOBALS['db']->data("SELECT LAST_INSERT_ID() as `id`");
	return $res[0]['id'];
}

function _stop_proc( $vn ) {

	if ( isset( $GLOBALS['streams'][$vn]['pid'] )  ) {
	
		exec( "sudo kill " . $GLOBALS['streams'][$vn]['pid'] );
		unset( $GLOBALS['streams'][$vn] );

	}
}

function _start_proc() {

}



function dvbs_stop() {
	global $streams;
	
	$streams = iptv_options_read();


	// Дешифрация потока                                                                                                                                                                                        
	_stop_proc( 'DECRYPT' );

	
	// Снимается поток со спутника
	_stop_proc( 'DVBtoIP' );
	exec("sudo rm " . $GLOBALS['dvblast_sock'] );
	
	// несрослось
	//unset( $GLOBALS['streams']['DVBtoIP']);
        //exec( "sudo " . dvbs_make_ctrl("shutdown")  );
	                                                                                                                                                                                                            
	// После паузы, поток идет с файла
	_stop_proc( 'FileToIP' );
	
	// Пауза, потоко идет в файл
        _stop_proc( 'IPtoFile' );

	$size = iptv_options_save( $streams );

	unset( $streams );
	
	return json_encode( array("ok"=>true) );
}


// Возвращает список IPTV каналов в фомате JSON
// для списка каналов в интерфейсе
function iptv_get_channels() {
	$sql = "SELECT * FROM `iptv_channels`";
	
	return json_encode( $GLOBALS['db']->data( $sql ) );
	
}

function dvbs_get_channels() {
	$sql = "SELECT * FROM `dvbs_channels`";
	
	return json_encode( $GLOBALS['db']->data($sql) );
}



function kill_all_dvbs() {
    exec( "sudo killall " . TSDECRYPT );
    sleep(1);
    exec( "sudo killall " . DVBLAST );
    sleep(1);
    unlink("iptv-0.json");
    unlink("iptv-1.json");
    unlink("iptv-2.json");
    unlink("iptv-3.json");

}


?>