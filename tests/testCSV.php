<?php
require_once 'main.config.local.php' ;

require_once '../CSV.php' ;
define('CSVFILE','data/input/FileSystemPattern.csv') ;
$csv=new CSVFile() ;
$csv->load(CSVFILE) ;
var_dump($csv->getHeader()) ;

echo implode ('   ',$csv->getHeader())."<br/>" ;
echo $csv->getRowNumber() ;
echo $csv->getJSON() ;
echo "<h1>END OF TESTS </h1>" ;


