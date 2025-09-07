<?php
define("TESTMODUS",false); //Konstante; gibt an, ob wir uns in einem Development-System (TESTMODUS ist true) oder in einem Produktivsystem (TESTMODUS ist false) befinden

if(TESTMODUS) {
	error_reporting(E_ALL);
	ini_set("display_errors",1);
}
else {
	error_reporting(E_ALL & ~E_DEPRECATED);
	ini_set("display_errors",0);
}
?>