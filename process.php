<?php
/*
Data Exporter Query Creating

Created July 31st, 2018

@author: Jasen

TODO
- proper date search
	- PROBLEM: dates are saved to the exact second; it makes more sense to search before, after, or within a certain range
	- SOLUTION: search for a range

- proper item search; convert ORs into ANDs, look up substring with 
- 

*/
ini_set('memory_limit','-1');

//for or statements, use and not; i.e. if you want to select rows where the airport is either 1, 5, or 9, search for rows where airports are not 2 and not 3 and not 4, etc. instead of searching for 1 or 5 or 9; it'll make to write the back end but probably should not be the final solution 

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=outdata.csv');

function formatData(){
	$columns = array();
	$keys = array_keys($_POST);
	$columnValues = array();
	$previousColumnName = "";

	for($i=0;$i<count($keys);$i++){

		$value = $_POST[$keys[$i]];
		if ($value == "") continue;
		$fieldName = $keys[$i];
		$columnName = explode("_", $fieldName)[0];

		try{
			$additionalInfo = explode("_",$fieldName)[1];
		}
		catch (exception $e){
			$additionalInfo = NULL;
		}

		if(array_key_exists($columnName,$columns)){
			if($additionalInfo == "hybridradio") {
				array_unshift($columns[$columnName],$value);
			}
			else {
				array_push($columns[$columnName],$value);
			}
		}

		else {
			$columns[$columnName] = array();
			array_push($columns[$columnName],$value);

			if (!($additionalInfo == "hybrid")){
				array_unshift($columns[$columnName],"=");
			}
		}

	}
	
	//print_r($columns);
	return $columns;

}

function createSQLCommand($columns,$targetTable = "daybag"){
	$sqlcommand = "SELECT * FROM " . $targetTable . " WHERE (";

	$lastColumnName = array_pop(array_keys($columns));
	foreach ($columns as $columnName => $columnValues){
		$comparator = $columnValues[0];
		$sqlcommand = $sqlcommand . "(";
		$lastValueKey = array_pop(array_keys($columnValues))-1;

		if($columnName == "Date"){
			$datestring = $columnValues[1];
			$splitDateString = explode("-",$datestring);
			$nextDayDateString = $splitDateString[0] . "-" . $splitDateString[1] . "-" . sprintf('%02d',(string)(((int)$splitDateString[2])+1));
			$sqlcommand = $sqlcommand . $columnName . ">=" . strtotime($datestring) . " AND " . $columnName . "<" . strtotime($nextDayDateString);
		}

		if($columnName == "LegalItemsPresent" || $columnName == "IllegalItemsPresent"){
			foreach(array_slice($columnValues,1) as $key=>$value){
				$sqlcommand = $key == $lastValueKey ? $sqlcommand . "SUBSTRING(TotalItemCombination," . (string)((int)$value + 1 + ($columnName == "IllegalItemsPresent" ? 406 : 0)) . ",1) = 1" : $sqlcommand . "SUBSTRING(TotalItemCombination," . (string)((int)$value + 1 + ($columnName == "IllegalItemsPresent" ? 406 : 0)) . ",1) = 1" . " AND ";
			}
		}

		else {
			foreach(array_slice($columnValues,1) as $key => $value){		
				$sqlcommand = $key == $lastValueKey ? $sqlcommand . $columnName . $comparator . $value : $sqlcommand . $columnName . $comparator . $value . " OR ";
			}
		}

		//print_r("Last Key Value: " . $lastColumnName . " Key " . $columnName . "<br>");
		$sqlcommand = $columnName == $lastColumnName ? $sqlcommand . ")" : $sqlcommand . ") AND ";
	}

	$sqlcommand = $sqlcommand . ");";
	//print_r($sqlcommand);
	return $sqlcommand;
}

$hostname = ''; //removed
$username = 'root';
$password = ''; //removed
$dbname = 'data';

//$myPDO = new PDO('mysql:host=127.0.0.1;dbname=data',$username,$password);
/*
$sqlcommand = "SELECT * FROM daybag WHERE (";

$keys = array_keys($_POST);

$previousColumnName = "";
$first = True;
$last = False;

for($i=0;$i<count($keys);$i++){
	$last = $i == count($keys)-1 ? True : False;
	//print_r($sqlcommand);
	$columnName = explode("_", $keys[$i])[0];

	try {
		$additionalInfo = explode("_",$keys[$i])[1];
	}
	catch(exception $e) {
		$additionalInfo = "";
	}

	$value = $_POST[$keys[$i]];
	if($value=="") continue;

	if($columnName == $previousColumnName){
		//only ever going to reach this if statement if radio form is contained within a hybrid form
		if($additionalInfo == "radio") {
			$first = False;
			$previousColumnName = $columnName;
			continue;
		}
		$sqlcommand = $last ? $sqlcommand . " OR " . $columnName . "=" . $value . ")": $sqlcommand . " OR " . $columnName . "=" . $value;
	}

	else{
		if($additionalInfo == "hybrid"){
			$comparator = $_POST[$keys[$i+1]]; //this should always exist if in a hybrid
			$isComparatorLast = $i+1 == count($keys)-1 ? True : False;

			$sqlcommand = $first ? $sqlcommand . "(" . $columnName . $comparator . $value : ($isComparatorLast ? $sqlcommand . ") AND " . "(" . $columnName . $comparator . $value . ")" : $sqlcommand . ") AND " . "(" . $columnName . $comparator . $value);

			$first = False;
			$previousColumnName = $columnName;	
			continue;
		}
		if($columnName == "Date"){

		}
		$sqlcommand = $first ? $sqlcommand . "(" . $columnName . "=" . $value : ($last ? $sqlcommand . ") AND " . "(" . $columnName . "=" . $value . ")" : $sqlcommand . ") AND " . "(" . $columnName . "=" . $value);
	}
	$first = False;
	$previousColumnName = $columnName;


}

$sqlcommand = $sqlcommand . ")";
*/


/*
//OLD
$query = $myPDO->prepare($sqlcommand); //"SELECT * FROM devices;"
$query->execute();
$result = $query->fetchALL(\PDO::FETCH_ASSOC);*/

$sqlcommand = createSQLCommand(formatData());

/////////////////// START OF SQL STUFF ////////////////////////


$output = fopen("php://output","w");
$headerarray = array();

try{
	$conn = mysqli_connect($hostname,$username,$password,$dbname);
}
catch(exception $e){
}

$getHeaderCommand = mysqli_query($conn,"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='data' AND TABLE_NAME='daybag';");

while($row = mysqli_fetch_assoc($getHeaderCommand)){
	array_push($headerarray,implode(" ",$row));
}

fputcsv($output,$headerarray);

$result = mysqli_query($conn, $sqlcommand);

if(mysqli_error($conn)){
	$sqlcommand = $sqlcommand . ")";
	$result = mysqli_query($conn,$sqlcommand);
}

while($row = mysqli_fetch_assoc($result)){
	fputcsv($output,$row);
}
fclose($output);


$result = mysqli_query($conn,$sqlcommand);


mysqli_close($conn);

////////// END OF SQL STUFF //////////
print_r($sqlcommand);
//print_r($_POST);
//print_r("<br>");

?>