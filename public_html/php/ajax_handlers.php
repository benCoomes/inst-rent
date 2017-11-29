<?php 
session_start();


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

  /*
    Expects: 
      GET with optional variables: 'serial_no', 'type', 'cond', 'search', 'available', and 'checkedOut'
    Permissions:
      User: users may only see available instruments.
      Manager: managers may see available and checked out instruments.
      Admins: Admins may see available and checked out instruments.
    Success:
      Condition: query completed, even if empty
      Status Code: 200
      Data: Instruments returned by query. Filter by 'type', 'cond', and 'search', 'available', and 'checkedOut' if specified.
    Failure: 
      No defined failure states
  */
  private function getInstruments(){
    // get instrumets from database, return as json
    $sql = "SELECT 
      i.serial_no serial_no, 
      i.type type, 
      i.cond cond, 
      (i.serial_no NOT IN (SELECT DISTINCT serial_no FROM active_contracts)) AS available,
      (i.serial_no IN (SELECT DISTINCT serial_no FROM pending_contracts)) AS pending
     FROM instruments i WHERE 1=1";

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
      $sql = $sql." AND i.serial_no IN (SELECT DISTINCT serial_no FROM active_contracts)";
    }

    if(($_SESSION['role'] == 'user') || (isset($_GET['checkedout']) && $_GET['checkedout'] == 'false')){
      $sql = $sql." AND i.serial_no NOT IN (SELECT DISTINCT serial_no FROM active_contracts)";
    }

    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Failed to execute query',
        $this->conn->error
      );
      print $response->toJson();
      return;
    }

    $instruments = [];
    while($row = $result->fetch_assoc()){
      if($row['available'] == 0){
        $row['available'] = False;
      } else {
        $row['available'] = True;
      }
      if($row['pending'] == 0){
        $row['pending'] = False;
      } else {
        $row['pending'] = True;
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
      Post with variables 'serial_no', 'type' and 'cond'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Insert row into instruments table using given data - no errors
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username
    Failure (integrity error/ duplicate keys):
      Status Code: 400
      Data: error message
  */
  private function addInstrument(){
    $serial_no = mysqli_real_escape_string($this->conn, $_POST['serial_no']);
    $type = mysqli_real_escape_string($this->conn, $_POST['type']);
    $type = strtolower($type);
    $cond = mysqli_real_escape_string($this->conn, $_POST['cond']);
    $cond = strtolower($cond);

    $sql = "INSERT INTO instruments (serial_no, type, cond) 
      VALUES ('".$serial_no."', '".$type."', '".$cond."')";

    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Failed to execute query',
        $this->conn->error
      );
      print $response->toJson();
    } else {
      $response = new Response(
        'Success',
        'Successfully added instrument.'
      );
      print $response->toJson();
    }
  }

  /*
    Expects: 
      Post with variables 'serial_no' and 'cond'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Update condition for instrument with matching serial number
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error):
      Status Code: 400
      Data: serial_no and cond from post
  */
  private function editInstrument(){
    $cond = mysqli_real_escape_string($this->conn, $_POST['cond']);
    $cond = strtolower($cond);
    $serial_no = mysqli_real_escape_string($this->conn, $_POST['serial_no']);
    $sql = "UPDATE instruments SET cond='".$cond."' WHERE serial_no='".$serial_no."'";

    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Failed to execute query',
        $this->conn->error
      );
      print $response->toJson();
    } else {
      $response = new Response(
        'Success',
        'Successfully updated instrument.'
      );
      print $response->toJson();
    } 
  }

  /*
    Expects: 
      Post with variables 'serial_no'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Delete row from instruments table
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username
    Failure (integrity error/ referential integrity):
      Status Code: 400
      Data: serialNo
  */
  private function deleteInstrument(){
    $serial_no = mysqli_real_escape_string($this->conn, $_POST['serial_no']);
    $sql = "DELETE FROM instruments WHERE serial_no='".$serial_no."'";

    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Failed to execute query',
        $this->conn->error
      );
      print $response->toJson();
    } else {
      $response = new Response(
        'Success',
        'Successfully deleted instrument.'
      );
      print $response->toJson();
    } 
  }

  /*
    Expects: 
      No expectations
    Permissions:
      Must be an active session
    Success:
      Condition: Get types of instruments present in instruments table
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: None
  */
  private function getInstrumentTypes(){
    $sql = "SELECT DISTINCT type FROM instruments";
    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Failed to execute query',
        $this->conn->error
      );
      print $response->toJson();
      return;
    } 

    $types = [];
    while($row = $result->fetch_assoc()){
      $types[] = $row['type'];
    }

    $response = new Response(
      'Success',
      'Got instrument types.',
      $types
    );
    print $response->toJson();
  }

  /*
    Expects: 
      No expectations
    Permissions:
      Must be an active session
    Success:
      Condition: Get conditions of instruments present in instruments table
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: None
  */
  private function getInstrumentConditions(){
    $sql = "SELECT DISTINCT cond FROM instruments";
    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Failed to execute query',
        $this->conn->error
      );
      print $response->toJson();
      return;
    } 

    $conds = [];
    while($row = $result->fetch_assoc()){
      $conds[] = $row['cond'];
    }

    $response = new Response(
      'Success',
      'Got instrument conditions.',
      $conds
    );
    print $response->toJson();
  }

  private function getUsers(){
    $sql = "SELECT cuid, username, first_name, last_name, role, email FROM users WHERE 1=1";

    if(isset($_GET['search'])){
      $search = mysqli_real_escape_string($this->conn, $_GET['search']);
      $sql = $sql." AND (cuid LIKE '%".$search."%' 
        OR username LIKE '%".$search."%' 
        OR first_name LIKE '%".$search."%' 
        OR last_name LIKE '%".$search."%' 
        OR email LIKE '%".$search."%')";
    }
    if(isset($_GET['show_admins']) && $_GET['show_admins'] == 'false'){
      $sql = $sql." AND role <> 'admin'";
    }
    if(isset($_GET['show_managers']) && $_GET['show_managers'] == 'false'){
      $sql = $sql." AND role <> 'manager'";
    }
    if(isset($_GET['show_users']) && $_GET['show_users'] == 'false'){
      $sql = $sql." AND role <> 'user'";
    }

    $result = $this->conn->query($sql);
    if(!$result){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Failed to execute query',
        $this->conn->error
      );
      print $response->toJson();
      return;
    }

    $users = [];
    while($row = $result->fetch_assoc()){
      $users[] = $row;
    }

    $response = new Response(
      'Success',
      'Got users',
      $users
    );
    print $response->toJson();
  }

  private function getProfileData(){
    //stubbed
    $response = new Response(
      'Success',
      'Get profile data called.'
    );
    print $resonse->toJson();
    return;
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

  private function unauthorized($msg){
    http_response_code(401);
      $response = new Response(
        'Error',
        $msg,
        ["username" => $_SESSION["username"], "role" => $_SESSION['role']]
      );
      print $response->toJson();
      return;
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
        if(isset($_SESSION['cuid'])){
          $this->getInstruments();
        } else {
          $this->unauthorized('Must be signed in to access instruments.');
        }
        break;

      case "add_instrument":
        requirePost('serial_no');
        requirePost('cond');
        requirePost('type');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->addInstrument();
        } else {
          $this->unauthorized("Only managers can add instruments.");
        }
        break;

      case "edit_instrument":
        requirePost('serial_no');
        requirePost('cond');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->editInstrument();
        } else {
          $this->unauthorized("Only managers can edit instruments.");
        }
        break;

      case "delete_instrument":
        requirePost('serial_no');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->deleteInstrument();
        } else {
          $this->unauthorized('Only managers can delete instruments.');
        }
        break;

      case "get_instrument_types":
        if(isset($_SESSION['cuid'])){
          $this->getInstrumentTypes();
        } else {
          $this->unauthorized('Must be signed in to access instrument types');
        }
        break;

      case "get_instrument_conditions":
        if(isset($_SESSION['cuid'])){
          $this->getInstrumentConditions();
        } else {
          $this->unauthorized('Must be signed in to access instrument conditions');
        }
        break;

      case "get_users":
        if(isset($_SESSION['role']) && $_SESSION['role'] == 'user'){
          $this->getProfileData();
        } else if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
          $this->getUsers();
        } else {
          $this->unauthorized("Only admins can view all users. Users may view themselves.");
        }
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


/***********************************
  Utility Functions
***********************************/

// require that a query string variable with the name $name exists and is not empty
function requireGet($name){
  $present = False;

  if(isset($_GET[$name])){
    if(!empty($_GET[$name])){
      $present = True;
    }
  }

  if(!$present){
    http_response_code(400);
    $response = new Response(
      'Error',
      'The POST variable '.$qsv.' is not present.'
    );
    print $reponse->toJson();
    die();
  }
}

function requirePost($name){
  $present = False;

  if(isset($_POST[$name])){
    if(!empty($_POST[$name])){
      $present = True;
    }
  }

  if(!$present){
    http_response_code(400);
    $response = new Response(
      'Error',
      'The POST variable '.$qsv.' is not present.'
    );
    print $reponse->toJson();
    die();
  }
}


/********************************************
  Start Script
********************************************/

// check for required qs variables
requireGET("action");

// get query string variables
$action = $_GET["action"];

// process action with AjaxHandler instance
$ajaxHandler = new AjaxHandler('../../config/localdb.json');
$ajaxHandler->doAction($action);

?>