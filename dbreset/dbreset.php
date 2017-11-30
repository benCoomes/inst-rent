<?php 


// create connection and errors array
$configLoc = "../config/dbconfig.json";
$configFile = fopen($configLoc, "r") or die ('error : Could not find db configuration file.');
$config = json_decode(fread($configFile, filesize($configLoc)), true);
fclose($configFile);

$conn = new mysqli($config["host"], $config["username"], $config["password"], $config["database"]);

if($conn->connect_error){
  die("Connection failed: ".$conn->connect_error);
}

// drop all current tables
	$query1 = "DROP TABLE IF EXISTS active_contracts";
   $query2 = "DROP TABLE IF EXISTS pending_contracts";
	$query3 = "DROP TABLE IF EXISTS users";
	$query4 = "DROP TABLE IF EXISTS instruments";
   
   $conn->query($query1) or die("Failed to delete table: ".$conn->error);
   $conn->query($query2) or die("Failed to delete table: ".$conn->error);
   $conn->query($query3) or die("Failed to delete table: ".$conn->error);
   $conn->query($query4) or die("Failed to delete table: ".$conn->error);


// create new tables	
	$query1 = "CREATE TABLE users(
		cuid int PRIMARY KEY NOT NULL, 
		username varchar(20) UNIQUE NOT NULL,
		password varchar(20) NOT NULL,
      role enum('user','manager','admin') NOT NULL,
		first_name varchar(20) NOT NULL,
		last_name varchar(20) NOT NULL,
		email varchar(200) UNIQUE NOT NULL,
		address varchar(200),
		age int,
		phone varchar(12))";
	$query2 = "CREATE TABLE instruments(
		serial_no varchar(20) PRIMARY KEY NOT NULL,
		type varchar(20) NOT NULL,
		cond enum('needs repair','poor','fair','good','new') NOT NULL)";
	$query3 = "CREATE TABLE active_contracts(
		start_date date NOT NULL,
		end_date date NOT NULL,
		cuid int NOT NULL,
		serial_no varchar(20) NOT NULL,
      CONSTRAINT PK_active_contracts PRIMARY KEY (serial_no),
      FOREIGN KEY (serial_no) REFERENCES instruments(serial_no),
      FOREIGN KEY (cuid) REFERENCES users(cuid))";
   $query4 = "CREATE TABLE pending_contracts(
      start_date date NOT NULL,
      end_date date NOT NULL,
      cuid int NOT NULL,
      serial_no varchar(20) NOT NULL,
      CONSTRAINT PK_pending_contracts PRIMARY KEY (serial_no,cuid),
      FOREIGN KEY (serial_no) REFERENCES instruments(serial_no),
      FOREIGN KEY (cuid) REFERENCES users(cuid))";
   $conn->query($query1) or die("Failed to create table: ".$conn->error);
   $conn->query($query2) or die("Failed to create table: ".$conn->error);
   $conn->query($query3) or die("Failed to create table: ".$conn->error);
   $conn->query($query4) or die("Failed to create table: ".$conn->error);

// populate new tables with test data set
	/*$query1 = "INSERT INTO users (cuid,username,password,role,email,first_name,last_name) VALUES
		(1000000,'jhopkins','password1','student','jhopkins@g.clemson.edu','John','Hopkins'),
		(2000000,'sfields','password2','student','sfields@g.clemson.edu','Susan','Fields'),
		(3000000,'cjwest','password3','student','cjwest@g.clemson.edu','Chris','West'),
		(4000000,'bcoomes','password4','student','bcoomes@g.clemson.edu','Ben','Coomes'),
		(5000000,'wbuffet','notunique','manager','wbuffet@g.clemson.edu','Warren','Buffet'),
		(6000000,'dvadar','notunique','manager','dvadar@g.clemson.edu','Darth','Vadar'),
		(7000000,'smario','password5','admin','smario@g.clemson.edu','Super','Mario'),
		(8000000,'tmorris','password6','student','tmorris@g.clemson.edu','Tony','Morris'),
		(9000000,'sadams','password7','student','sadams@g.clemson.edu','Sam','Adams'),
		(9999999,'sfalls','password8','student','sfalls@g.clemson.edu','Sarah','McFalls')";*/ 
	$query1 = "LOAD DATA LOCAL INFILE './test_data/student_big_data.csv' INTO TABLE users FIELDS TERMINATED BY '|' LINES TERMINATED BY '\n'";  
/*	$query2 = "INSERT INTO instruments (serial_no,type,cond) VALUES
		('SN0001','trumpet','good'),
		('SN0002','trumpet','good'),
		('SN0003','trumpet','fair'),
		('SN0004','clarinet','fair'),
		('SN0005','flute','poor'),
		('SN0006','french horn','poor'),
		('SN0007','tuba','new'),
      ('SN0008','tuba','good'),
      ('SN0009','saxophone','good'),
      ('SN0010','sousaphone','needs repair')"; */
	$query2 = "LOAD DATA LOCAL INFILE './test_data/instruments_big_data.csv' INTO TABLE instruments FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n'; "; 
/*   $query3 = "INSERT INTO active_contracts(start_date,end_date,cuid,serial_no) VALUES
      ('2017-08-01','2017-12-01',1000000,'SN0001'),
      ('1999-01-01','2050-01-01',3000000,'SN0002'),
      ('2016-08-22','2020-05-10',9999999,'SN0008'),
      ('2014-01-12','2014-12-13',2000000,'SN0009')"; */ 
	$query3 = "LOAD DATA LOCAL INFILE './test_data/active_contract_big_data.csv' INTO TABLE active_contracts FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n'; "; 
/*   $query4 = "INSERT INTO pending_contracts(start_date, end_date, cuid, serial_no) VALUES 
      ('2018-01-16','2018-05-05',1000000,'SN0001'),
      ('2017-08-01','2017-12-01',8000000,'SN0001'),
      ('2017-10-13','2017-10-30',9000000,'SN0008'),
      ('2018-02-14','2018-10-09',4000000,'SN0010')"; */
	$query4 = "LOAD DATA LOCAL INFILE './test_data/rental_contracts_big_data.csv' INTO TABLE pending_contracts FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n'; " ;
   $conn->query($query1) or die("Failed to populate table1: ".$conn->error);
   $conn->query($query2) or die("Failed to populate table2: ".$conn->error);
   $conn->query($query3) or die("Failed to populate table3: ".$conn->error);
   $conn->query($query4) or die("Failed to populate table4: ".$conn->error);

   echo "Success.\n";

?>
