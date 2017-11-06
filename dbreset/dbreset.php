<?php 


// create connection and errors array
$configLoc = "../config/instrentdev.json";
$configFile = fopen($configLoc, "r") or die ('error : Could not find db configuration file.');
$config = json_decode(fread($configFile, filesize($configLoc)), true);
fclose($configFile);

$conn = new mysqli($config["host"], $config["username"], $config["password"], $config["database"]);

if($conn->connect_error){
  die("Connection failed: ".$conn->connect_error);
}
echo "Connected succesfully.";


// drop all current tables
	$query1 = "DROP TABLE IF EXISTS users";
	$query2 = "DROP TABLE IF EXISTS instruments";
	$query3 = "DROP TABLE IF EXISTS rental_contracts";
	$result1 = $conn->query($query1);
	$result2 = $conn->query($query2);
	$result3 = $conn->query($query3);
	if(!($result1 & $result2 & $result3)) 
	{
		die("Failed to get rid of old tables: ".$conn->error);
	}


// create new tables	
	$query1 = "CREATE TABLE users(
		cuid int, 
		username varchar(20),
		password varchar(20),
		first_name varchar(20),
		last_name varchar(20),
		email varchar(20))";
	$query2 = "CREATE TABLE instruments(
		serial_no int,
		type varchar(20),
		cond varchar(20))";
	$query3 = "CREATE TABLE rental_contracts(
		start_date date,
		end_date date,
		cuid int,
		serial_no varchar(20),
		confirmed enum('true', 'false'))";
	$result1 = $conn->query($query1);
	$result2 = $conn->query($query2);
	$result3 = $conn->query($query3);
	if(!($result1 & $result2 & $result3)) {
		die("failed to create new tables in the database: ".$conn->error);
	}



// load data from files into tables need to change delimiters, - don't think this will work. files are not 
	// on same computer as server. will probably have to go line by line and 
	// generate queries for each row. 
	$query1 = "LOAD DATA INFILE './test_data/instruments_test.csv' 
			INTO TABLE instruments
			COLUMNS TERMINATED BY ','
			LINES TERMINATED BY '\n'";  
	$query2 = "LOAD DATA INFILE './test_data/users_test.csv' 
			INTO TABLE users
			COLUMNS TERMINATED BY ','
			LINES TERMINATED BY '\n'";
	$query3 = "LOAD DATA INFILE './test_data/rental_contracts.csv' 
			INTO TABLE rental_contracts
			COLUMNS TERMINATED BY ','
			LINES TERMINATED BY '\n'";
	$result1 = $conn->query($query1);
	$result2 = $conn->query($query2);
	$result3 = $conn->query($query3);
	if(!($result1 & $result2 & $result3)) {
		die("failed to read data in from the files in the database: ".$conn->error);
	}
	
?>
