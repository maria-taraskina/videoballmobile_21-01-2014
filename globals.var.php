<?php

if ( isset($_GET['u']) & (int)$_GET['u'] >= 0) {
       $GLOBALS['USER'] = 3; //  (int)$_GET['u'];
}

if ( isset($_GET['tv']) & (int)$_GET['tv'] >= 0) {
       $GLOBALS['PLAYER'] = 3; //(int)$_GET['tv'];
}

?>