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


// create new tables


// load data from files into tables


?>