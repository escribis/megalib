<?php
require_once 'main.config.local.php' ;

require_once '../YEd.php' ;
require_once '../HTML.php' ;

$file = 'data/input/g1.html' ;
$text = file_get_contents($file) ;
var_dump(YEdHTML::getImageAreaMap($text)) ;

echo '<h1>END OF TESTS</h1>' ;