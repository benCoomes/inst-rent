<?php 

/***********************************
  Utility Functions
***********************************/

// require that a query string variable with the name $qsv exists
function requireQSV($qsv){
  $present = False;

  if(isset($_GET[$qsv])){
    if(!empty($_GET[$qsv])){
      $present = True;
    }
  }

  if(!$present){
    http_response_code(400);
    die('Error: The query string variable '.$qsv.' is not present.');
  }
}

/*************************************
  Classes
*************************************/

class AjaxHandler{
  private $conn = NULL;

  // create connection and errors array
  function __construct($configLoc){
    $configFile = fopen($configLoc, "r") or die ('error : Could not find db configuration file.');
    $config = json_decode(fread($configFile, filesize($configLoc)), true);
    fclose($configFile);

    $conn = new mysqli($config["host"], $config["username"], $config["password"]);

    if($conn->connect_error){
      die("Connection failed: ".$conn->connect_error);
    }
    echo "Connected succesfully.";
  }

  /*****************************************
    Utility Methods
  *****************************************/


  /*****************************************
    Action Methods
  ****************************************/

  private function getInstruments(){
    // get instrumets from database, return as json
  }

  private function defaultAction(){
    http_response_code(400);
    $action = $_GET["action"];
    print 'error: The action "'.$action.'" is not recognized.';
  }

  /*************************
    Action selector
  *************************/

  public function doAction($action){
    switch($action){
      case "get_instruments":
        break;

      default:
        $this->defaultAction();
        break;
    }
  }
}

/********************************************
  Start Script
********************************************/

// check for required qs variables
//requireQSV("action");

// get query string variables
//$action = $_GET["action"];

// process action with AjaxHandler instance
$ajaxHandler = new AjaxHandler('../../config/dbconfig.json');
//$ajaxHandler->doAction($action);


?>