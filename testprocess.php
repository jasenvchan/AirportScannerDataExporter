<?php

$testarray = array();

print_r($_POST);
$testarray["date"] = $_POST["datefield"];
echo "<br>";
print_r(strtotime($testarray["date"]));
echo "<br>";
print_r(strtotime("2018-09-07"));

$var = NULL;
print_r($var ? "yos" : "nos");

$arraytest = array(1, 2, 3, 4, 2);
$lastElement = end($arraytest);

echo sprintf('%02d',"1");
echo sprintf('%02d',"2");
echo "<br>" . sprintf('%02d',(string)((int)sprintf('%02d',"1") + (int)sprintf('%02d',"2")));


?>