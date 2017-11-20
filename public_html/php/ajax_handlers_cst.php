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

  /**********************************************
  testing variables to replace DB query results
  **********************************************/
  private $users = NULL;

  // create pseudo DB
  function __construct($configLoc){
    $this->users = [
      ['username' => 'bcoomes', 'password' => 'bcpass', 'role' => 'user', 'cuid' => 1000100],
      ['username' => 'cjwest', 'password' => 'cwpass', 'role' => 'user', 'cuid' => 2000200],
      ['username' => 'admin', 'password' => 'ampass', 'role' => 'admin', 'cuid' => 3000300],
      ['username' => 'speedy', 'password' => 'sppass', 'role' => 'manager', 'cuid' => 4000400],
      ['username' => 'rando', 'password' => 'rdpass', 'role' => 'manager', 'cuid' => 5000500]
    ];

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
    foreach($this->users as $user){ 
      if($user['username'] == $username && $user['password'] == $password){
        return True;
      } 
    }
    return False;
  }

  /*
    Get user role, cuid for row with username = $username
    Return false if user with username is not found
  */
  private function startUserSession($username){
    // fake implementation for testing
    foreach($this->users as $user){
      if($user['username'] == $username){
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['cuid'] = $user['cuid'];
        return True;
      }
    }
    return False;
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
      GET with no variables
    Permissions: 
      No restrictions
    Success: 
      Condition: query completed, even if empty
      Status Code: 200
      Data: list of types present on instruments in the instruments table
    Failure: 
      No defined failure states
  */
  private function getInstrumentTypes(){
    $types = ["Trumpet", "Flute", "Tuba", "Clarinet", "French Horn", "Sousaphone", "Didgeridoo"];
    $response = new Response(
      'Success',
      'Got instrument types',
      $types
    );
    print $response->toJson();
  }

  /*
    Expects: 
      GET with no variables
    Permissions: 
      No restrictions
    Success: 
      Condition: query completed, even if empty
      Status Code: 200
      Data: list of conditions present on instruments in the instruments table
    Failure: 
      No defined failure statues
  */
  private function getInstrumentConditions(){
    $conditions = ["Needs Repair", "Poor", "Fair", "Good", "Excellent"];
    $response = new Response(
      'Success',
      'Got instrument conditions',
      $conditions
    );
    print $response->toJson();
  }

  /*
    Expects: 
      GET with optional variables: 'type', 'cond', 'search', 'available', and 'checkedOut'
    Permissions:
      User: users may only see available instruments.
      Manager: managers may see available and checked out instruments.
    Success:
      Condition: query completed, even if empty
      Status Code: 200
      Data: Instruments returned by query. Select by 'type', 'cond', and 'search' if specified.
        If 'available' is in QS, do not get available instruments (getting available instruments is default)
        If 'checkedOut' is in QS, get checked out instruments (do not get them by default)
    Failure: 
      No defined failure states
  */
  private function getInstruments(){
    // return fake instrument list
    $instruments = [
      ['serial_no' => '1234TXX', 'type' => 'Trumpet', 'cond' => 'Excellent', 'available' => True, 'rented_by' => Null],
      ['serial_no' => '9090877', 'type' => 'Flute', 'cond' => 'Needs Repair', 'available' => True, 'rented_by' => Null],
      ['serial_no' => 'TRU6473', 'type' => 'Trumpet', 'cond' => 'Fair', 'available' => False, 'rented_by' => 'cjwest'],
      ['serial_no' => '9874018', 'type' => 'Tuba', 'cond' => 'Poor', 'available' => True, 'rented_by' => Null],
      ['serial_no' => '10MRD92', 'type' => 'Clarinet', 'cond' => 'Good', 'available' => False, 'rented_by' => 'bcoomes'],
      ['serial_no' => '5390293', 'type' => 'French Horn', 'cond' => 'Fair', 'available' => True, 'rented_by' => Null],
      ['serial_no' => '9920994', 'type' => 'Sousaphone', 'cond' => 'Good', 'available' => False, 'rented_by' => 'bcoomes'],
      ['serial_no' => 'DDRD000', 'type' => 'Didgeridoo', 'cond' => 'Excellent', 'available' => True, 'rented_by' => Null]
    ];


    $response = new Response(
      'Success',
      'Get Instruments function called'
    );

    if($_SESSION['role']== 'user'){
      $availableInstruments = [];
      foreach($instruments as $inst){
        if($inst['available']){
          $availableInstruments[] = $inst;
        }
      }
      $response->setData($availableInstruments);

    } else if($_SESSION['role'] == 'manager'){
      $response->setData($instruments);
    }

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

      case "get_instrument_types":
        $this->getInstrumentTypes();
        break;

      case "get_instrument_conditions":
        $this->getInstrumentConditions();
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
$ajaxHandler = new AjaxHandler('../../config/dbconfig.json');
$ajaxHandler->doAction($action);

?>