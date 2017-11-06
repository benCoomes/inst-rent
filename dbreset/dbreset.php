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
	$query1 = "INSERT INTO users (cuid,username,password,role,email,first_name,last_name) VALUES
		(1000000,'jhopkins','password1','student','jhopkins@g.clemson.edu','John','Hopkins'),
		(2000000,'sfields','password2','student','sfields@g.clemson.edu','Susan','Fields'),
		(3000000,'cjwest','password3','student','cjwest@g.clemson.edu','Chris','West'),
		(4000000,'bcoomes','password4','student','bcoomes@g.clemson.edu','Ben','Coomes'),
		(5000000,'wbuffet','notunique','manager','wbuffet@g.clemson.edu','Warren','Buffet'),
		(6000000,'dvadar','notunique','manager','dvadar@g.clemson.edu','Darth','Vadar'),
		(7000000,'smario','password5','admin','smario@g.clemson.edu','Super','Mario'),
		(8000000,'tmorris','password6','student','tmorris@g.clemson.edu','Tony','Morris'),
		(9000000,'sadams','password7','student','sadams@g.clemson.edu','Sam','Adams'),
		(9999999,'sfalls','password8','student','sfalls@g.clemson.edu','Sarah','McFalls')";  
	$query2 = "INSERT INTO instruments (serial_no,type,cond) VALUES
		('SN0001','trumpet','good'),
		('SN0002','trumpet','good'),
		('SN0003','trumpet','fair'),
		('SN0004','clarinet','fair'),
		('SN0005','flute','poor'),
		('SN0006','french horn','poor'),
		('SN0007','tuba','new'),
		('SN0008','tuba','good'),
		('SN0009','saxophone','good'),
		('SN0010','sousaphone','needs repair')";
	$query3 = "INSERT INTO rental_contracts(start_date,end_date,cuid,serial_no,confirmed) VALUES
		('2017-08-01','2017-12-01',1000000,'SN0001','true'),
		('1999-01-01','2050-01-01',3000000,'SN0002','true'),
		('2018-01-16','2018-05-05',1000000,'SN0001','false'),
		('2017-08-01','2017-12-01',8000000,'SN0001','false'),
		('2016-08-22','2020-05-10',9999999,'SN0008','true'),
		('2017-10-13','2017-10-30',9000000,'SN0008','false'),
		('2014-01-12','2014-12-13',2000000,'SN0009','true'),
		('2018-02-14','2018-10-09',4000000,'SN0010','false')";
	$result1 = $conn->query($query1);
	$result2 = $conn->query($query2);
	$result3 = $conn->query($query3);
	if(!($result1 & $result2 & $result3)) {
		die("failed to read data in from the files in the database: ".$conn->error);
	}
	
?>
