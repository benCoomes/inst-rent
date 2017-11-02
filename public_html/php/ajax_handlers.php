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

class Response{
  private $msg = '';
  private $status = '';
  private $data = NULL;

  function __construct($status = '', $msg = '', $data = NULL){
    $this->msg = $msg;
    $this->status = $status;
    $this->data = $data;
  }

  public function setMsg($msg){
    $this->msg = $msg;
  }

  public function setStatus($status){
    // check for valid statuses? eg, must be success or error?
    $this->status = $status;
  }

  public function setData($data){
    $this->data = $data;
  }

  public function toJson(){
    $struct = [
      "msg" => $this->msg,
      "status" => $this->status,
      "data" => $this->data
    ];

    return json_encode($struct);
  }
}


class AjaxHandler{
  private $conn = NULL;

  // create connection and errors array
  function __construct($configLoc){
    $configFile = fopen($configLoc, "r") or die ('error : Could not find db configuration file.');
    $config = json_decode(fread($configFile, filesize($configLoc)), true);
    fclose($configFile);

    $conn = new mysqli($config["host"], $config["username"], $config["password"]);

    if($conn->connect_error){
      $response = new Response();
      $response->setMessage('Failed to connect to database');
      $response->setStatus('Error');
      $response->setData([]);
      die($response->toJson());
    }
  }

  /*****************************************
    Action Methods
  ****************************************/

  private function getInstruments(){
    // get instrumets from database, return as json
    $response = new Response(
      'Success',
      'Get Instruments function called'
    );
    print $response->toJson();
  }

  private function signIn(){
    // validate credentials against database.
    $response = new Response(
      'Success',
      'Sign In function called',
      [
        'username' => $_POST['username'],
        'password' => $_POST['password']
      ]
    );
    print $response->toJson();
  }

  private function signUp(){
    // try to create credentials in the database.
    $response = new Response(
      'Success',
      'Sign Up function called.',
      [
        'cuid' => $_POST['cuid'],
        'cuEmail' => $_POST['cuEmail'],
        'username' => $_POST['username'],
        'firstName' => $_POST['firstName'],
        'lastName' => $_POST['lastName'],
        'password' => $_POST['password'],
        'passwordConfirm' => $_POST['passwordConfirm']
      ]
    );
    print $response->toJson();
  }

  private function defaultAction(){
    http_response_code(400);
    $action = $_GET["action"];
    $response = new Response(
      'Error',
      'The action "'.$action.'" is not recognized.'
    );
    print $response->toJson();
  }

  /*************************
    Action selector
  *************************/

  public function doAction($action){
    switch($action){
      case "get_instruments":
        $this->getInstruments();
        break;

      case "sign_in":
        $this->signIn();
        break;

      case "sign_up":
        $this->signUp();
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
requireQSV("action");

// get query string variables
$action = $_GET["action"];

// process action with AjaxHandler instance
$ajaxHandler = new AjaxHandler('../../config/dbconfig.json');
$ajaxHandler->doAction($action);

?>