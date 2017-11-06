<?php 


// create connection and errors array
$configLoc = "../config/instrentdev.json";
$configFile = fopen($configLoc, "r") or die ('error : Could not find db configuration file.');
$config = json_decode(fread($configFile, filesize($configLoc)), true);
fclose($configFile);

$conn = new mysqli($config["host"], $config["username"], $config["password"]);

if($conn->connect_error){
  die("Connection failed: ".$conn->connect_error);
}
echo "Connected succesfully.";


// drop all current tables
	$query1 = "DROP TABLE student";
	$query2 = "DROP TABLE instrument";
	$query3 = "DROP TABLE rental_contract";
	$result1 = mysql_query($query1);
	$result2 = mysql_query($query2);
	$result3 = mysql_query($query3);
	if(!($result1 & $result2 & $result3)) 
	{
		die("Failed to get rid of old tables: ".mysql_error());
	}


// create new tables	
	$query1 = "CREATE TABLE student(
		CUID int, 
		username varchar(20),
		password varchar(20),
		FirstName varchar(20),
		LastName varchar(20),
		email varchar(20),
		Description varchar(200))";
	$query2 = "CREATE TABLE instrument(
		serialNo int,
		type varchar(20),
		quality varchar(20))";
	$query3 = "CREATE TABLE rental_contract(
		start_date date,
		end_date date
		CUID int
		SerialNo varchar(20)
		Confirmed boolean)";
	$result1 = mysql_query($query1);
	$result2 = mysql_query($query2);
	$result3 = mysql_query($query3);
	if(!($result1 & $result2 & $result3)) {
		die("failed to create new tables in the database: ".mysql_error());
	}



// load data from files into tables


?>
