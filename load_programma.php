<?

require_once "iptv.functions.php";

//  phpinfo();

define ( "WGET", 'wget -U "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5" -N ');
define ( "XMLTV_URL", "http://www.teleguide.info/download/new3/xmltv.xml.gz" );
define ( "XMLTV_FILE", "/tmp/xmltv.xml.gz" );




$cmd_wget = WGET . '"' . XMLTV_URL . '"' . " -O " . XMLTV_FILE;
$cmd_gz = "gzip -d -f " . XMLTV_FILE;
$r = shell_exec($cmd_wget . '&&' . $cmd_gz);


$sql = 'SELECT `id_channel` FROM `video_source` WHERE `active` > 0 AND `id_channel` > 0 AND `id_channel` < 999000';
$ch = $GLOBALS['db']->data($sql);


function is_channel( $channel, $ch ) {
	$cnt = count($ch);
	
	$r = False;
	for($i=0; $i < $cnt; $i++) {
		if ( (int)$ch[$i]['id_channel'] == $channel ) {
			$r = True;
			$i = $cnt;
		}
	}
	
	return $r;
}




$doc = new DOMDocument('1.0', 'utf-8');
$doc->load('/tmp/xmltv.xml');


$channels = $doc->getElementsByTagName('channel');
foreach ($channels as $channel) {
	
	$sql = 'INSERT INTO `video_channels` (`id`, `name`) VALUES (' . (int)$channel->getAttribute('id') . ', "' . trim($channel->nodeValue) . '")';
	
	$GLOBALS['db']->debug = False;
	$GLOBALS['db']->query($sql);
	
}


$programms = $doc->getElementsByTagName('programme');
foreach ($programms as $program) {
        $start = "";
        $stop = "";
        $channel = "";
        $title = "";
        $category = "";
        $desc = ""; 
     

	$start = substr($program->getAttribute('start'), 0, 14);
	$stop = substr($program->getAttribute('stop'), 0, 14);
	
	$channel = $program->getAttribute('channel');


	if ( is_channel( $channel, $ch ) ) {
	
		$childs = $program->childNodes;
		foreach ($childs as $child) {
			if ($child->nodeName == "title") {
				$title = trim($child->nodeValue);
			}
	
			if ($child->nodeName == "category") {
				$category = trim($child->nodeValue);
			}
			
			if ($child->nodeName == "desc") {
				$desc = trim($child->nodeValue);
			}
		}
	
		$sql = 'INSERT INTO `video_programma` (`channel`, `category`, `title`, `desc`, `start`, `stop`) VALUES (' . $channel;
		$sql .= ', "' . esc($category) . '", "' . esc($title) . '", "' . esc($desc) . '", "' . $start . '", "' . $stop . '")';
	
		$GLOBALS['db']->debug = False;
		$GLOBALS['db']->query($sql);
	
	}
	
}


?>