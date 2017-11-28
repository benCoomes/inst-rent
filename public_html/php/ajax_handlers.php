<?php 
session_start();


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

//TODO: create require Post Variable

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

    $this->conn = new mysqli($config["host"], $config["username"], $config["password"], $config["database"]);

    if($this->conn->connect_error){
      $response = new Response();
      $response->setMsg('Failed to connect to database');
      $response->setStatus('Error');
      $response->setData([]);
      die($response->toJson());
    }
  }

  /*****************************************
    Helper Methods
  ****************************************/
  /*
    Check DB for username, password combination
    return True if found
    return False otherwise
  */
  private function isUser($username, $password){
    // fake implementation for testing
    if($username == 'bcoomes' && $password == 'password'){
      return True;
    } else {
      return False;
    }
  }

  /*
    Get user role, cuid for row with username = $username
  */
  private function startUserSession($username){
    // fake implementation for testing
      $_SESSION['username'] = $username;
      $_SESSION['role'] = 'user';
      $_SESSION['cuid'] = 'C1234567';
  }


  /*****************************************
    Action Methods
  ****************************************/

  private function getSession(){
    $response = new Response();
    if(array_key_exists('cuid', $_SESSION)){
      $response->setStatus('Success');
      $response->setMsg('Session data retrieved.');
      $response->setData([
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'cuid' => $_SESSION['cuid'],
        'signedIn' => True
      ]);
    } else {
      $response->setStatus('Success');
      $response->setMsg('No session data');
      $response->setData([
        'username' => Null,
        'role' => Null,
        'cuid' => Null,
        'signedIn' => False
      ]);
    }

    print $response->toJson();
  }

  private function getInstruments(){
    // get instrumets from database, return as json
    $sql = "SELECT i.serial_no serial_no, i.type type, i.cond cond, ac.cuid cuid FROM instruments i LEFT JOIN active_contracts ac ON i.serial_no = ac.serial_no WHERE 1=1";

    if(isset($_GET['type'])){
      $type = mysqli_real_escape_string($this->conn, $_GET['type']);
      $sql = $sql." AND i.type = '".$type."'";
    }

    if(isset($_GET['cond'])){
      $cond = mysqli_real_escape_string($this->conn, $_GET['cond']);
      $sql = $sql." AND i.cond='".$cond."'";
    }

    if(isset($_GET['search'])){
      $search = mysqli_real_escape_string($this->conn, $_GET['search']);
      $sql = $sql." AND (i.serial_no LIKE '%".$search."%' 
        OR i.type LIKE '%".$search."%' 
        OR i.cond LIKE '%".$search."%')";
    }

    if(isset($_GET['serial_no'])){
      $serial_no = mysqli_real_escape_string($this->conn, $_GET['serial_no']);
      $sql = $sql." AND i.serial_no ='".$serial_no."'";
    }

    if(isset($_GET['available']) && $_GET['available'] == 'false'){
      $sql = $sql." AND ac.cuid IS NOT NULL";
    }

    if(($_SESSION['role'] != 'manager' || $_SESSION['role'] != 'admin') || (isset($_GET['checkedout']) && $_GET['checkedout'] == 'false')){
      $sql = $sql." AND ac.cuid IS NULL";
    }


    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(500);
      $response = new Response(
        'Error',
        'Failed to execute query'
      );
      print $response->toJson();
      return;
    }

    $instruments = [];

    while($row = $result->fetch_assoc()){
      if(!empty($row['cuid'])){
        $row['available'] = False;
      } else {
        $row['available'] = True;
      }
      $instruments[] = $row;
    }
    $response = new Response(
      'Success',
      'Got instruments',
      $instruments
    );
    print $response->toJson();

  }


  /*
    Expects: 
      Post with variables 'username' and 'password'
    Success:
      Condition: User with 'username' and 'password' exists
      Status Code: 200
      Data: username, role
    Failure (bad credentials):
      Status Code: 401
      Data: username
  */
  private function signIn($username, $password){
    $status = '';
    $msg = '';
    $data = [
        'username' => $username
    ];

    $isUser = $this->isUser($username, $password);
    
    if($isUser){
      $this->startUserSession($username);

      $status = 'Success';
      $msg = 'User signed in.';
      $data['role'] = $_SESSION['role'];
    } else {
      $status = 'Error';
      $msg = 'Could not find user in database.';
      http_response_code(401);
    }


    $response = new Response($status, $msg, $data);
    print $response->toJson();
  }


  /*
    Expects: 
      Post with variables:
        'cuid', 'cuEmail', 'username', 'firstName',
        'lastName', 'password', 'passwordConfirm'
    Success: 
      Condition: User entry is created in db. User is signed in.
      Response Code: 200
      Data: username, role
    Error: 
      Condition: User session is active. Username is taken.
        Password is invalid. Email is not valid. CUID is taken or invalid.
      Response Code: 400
  */
  private function signUp(){
    // fake method for testing
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

  private function signOut(){
    $_SESSION = array();

    if(ini_get("session.use_cookies")){
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
      );
    }

    session_destroy();

    $response = new Response('Success', 'User logged out');
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
      case "get_session":
        $this->getSession();
        break;

      case "get_instruments":
        $this->getInstruments();
        break;

      case "sign_in":
        // TODO: check for PVs here
        $this->signIn($_POST['username'], $_POST['password']);
        break;

      case "sign_up":
        $this->signUp();
        break;

      case "sign_out":
        $this->signOut();
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
$ajaxHandler = new AjaxHandler('../../config/localdb.json');
$ajaxHandler->doAction($action);

?>