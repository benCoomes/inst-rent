<?php 
session_start();

// http response code for php less than 5.4
// source: http://php.net/manual/en/function.http-response-code.php 
// craigs comment
if (!function_exists('http_response_code')) {
        function http_response_code($code = NULL) {

            if ($code !== NULL) {

                switch ($code) {
                    case 100: $text = 'Continue'; break;
                    case 101: $text = 'Switching Protocols'; break;
                    case 200: $text = 'OK'; break;
                    case 201: $text = 'Created'; break;
                    case 202: $text = 'Accepted'; break;
                    case 203: $text = 'Non-Authoritative Information'; break;
                    case 204: $text = 'No Content'; break;
                    case 205: $text = 'Reset Content'; break;
                    case 206: $text = 'Partial Content'; break;
                    case 300: $text = 'Multiple Choices'; break;
                    case 301: $text = 'Moved Permanently'; break;
                    case 302: $text = 'Moved Temporarily'; break;
                    case 303: $text = 'See Other'; break;
                    case 304: $text = 'Not Modified'; break;
                    case 305: $text = 'Use Proxy'; break;
                    case 400: $text = 'Bad Request'; break;
                    case 401: $text = 'Unauthorized'; break;
                    case 402: $text = 'Payment Required'; break;
                    case 403: $text = 'Forbidden'; break;
                    case 404: $text = 'Not Found'; break;
                    case 405: $text = 'Method Not Allowed'; break;
                    case 406: $text = 'Not Acceptable'; break;
                    case 407: $text = 'Proxy Authentication Required'; break;
                    case 408: $text = 'Request Time-out'; break;
                    case 409: $text = 'Conflict'; break;
                    case 410: $text = 'Gone'; break;
                    case 411: $text = 'Length Required'; break;
                    case 412: $text = 'Precondition Failed'; break;
                    case 413: $text = 'Request Entity Too Large'; break;
                    case 414: $text = 'Request-URI Too Large'; break;
                    case 415: $text = 'Unsupported Media Type'; break;
                    case 500: $text = 'Internal Server Error'; break;
                    case 501: $text = 'Not Implemented'; break;
                    case 502: $text = 'Bad Gateway'; break;
                    case 503: $text = 'Service Unavailable'; break;
                    case 504: $text = 'Gateway Time-out'; break;
                    case 505: $text = 'HTTP Version not supported'; break;
                    default:
                        exit('Unknown http status code "' . htmlentities($code) . '"');
                    break;
                }

                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

                header($protocol . ' ' . $code . ' ' . $text);

                $GLOBALS['http_response_code'] = $code;

            } else {

                $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

            }

            return $code;

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
    $struct = array(
      "msg" => $this->msg,
      "status" => $this->status,
      "data" => $this->data
    );

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
      $response->setData(array());
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
    $username = mysqli_real_escape_string($this->conn, $username);
    $sql = "SELECT * FROM users WHERE username='".$username."'";

    $result = $this->conn->query($sql);
    if(!$result){
      return false;
    }

    $user = $result->fetch_assoc();  
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['cuid'] = $user['cuid'];
    return true;
  }


  /*****************************************
    Action Methods
  ****************************************/

  private function getSession(){
    $response = new Response();
    if(array_key_exists('cuid', $_SESSION)){
      $response->setStatus('Success');
      $response->setMsg('Session data retrieved.');
      $response->setData(array(
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'cuid' => $_SESSION['cuid'],
        'signedIn' => True
      ));
    } else {
      $response->setStatus('Success');
      $response->setMsg('No session data');
      $response->setData(array(
        'username' => Null,
        'role' => Null,
        'cuid' => Null,
        'signedIn' => False
      ));
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

    $instruments = array();
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

    $types = array();
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

    $conds = array();
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

  /*
    Expects: 
      GET with optional variables: 'search', 'show_users', 'show_managers', 'show_admins', and 'cuid'.
    Permissions:
      Admins: only admins may view this information.
    Success:
      Condition: query completed, even if empty
      Status Code: 200
      Data: Users returned by query, without password data. Select by 'search', role if specified.
        If 'show_<role>' is not in QS, get users with role = <role>
        If 'show_<role>' is in QS, check its value (false or true). Do not get users with role = <role> if false.
        If 'cuid' is in QS, select by exact cuid.
    Failure (permissions):
      status code: 401
      data: session username and role
  */
  private function getUsers(){
    $sql = "SELECT cuid, username, first_name, last_name, role, email, age, address, phone FROM users WHERE 1=1";

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
    if(isset($_GET['cuid'])){
      $cuid = mysqli_real_escape_string($this->conn, $_GET['cuid']);
      $sql = $sql." AND cuid ='".$cuid."'";
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

    $users = array();
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

  /*
    Expects: 
      Post with variables 'cuid', 'email', 'username', 'password', 
      'password_confirm', and 'role'
    Permissions:
      Admin: Only admins may perform this action.
    Success:
      Condition: Insert row into users table using given data - no errors
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error/ duplicate keys / bad passwords / invalid role):
      Status Code: 400
      Data: error message
  */
  private function addUser(){
    $cuid = mysqli_real_escape_string($this->conn, $_POST['cuid']);
    $email = mysqli_real_escape_string($this->conn, $_POST['email']);
    $username = mysqli_real_escape_string($this->conn, $_POST['username']);
    $password = mysqli_real_escape_string($this->conn, $_POST['password']);
    $password_confirm = mysqli_real_escape_string($this->conn, $_POST['password_confirm']);
    $role = mysqli_real_escape_string($this->conn, $_POST['role']);

    // validate passwords
    if($password != $password_confirm){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Passwords do not match.'
      );
      print $response->toJson();
      return;
    }

    // validate role
    if($role != "admin" && $role != "user" && $role != "manager"){
      http_response_code(400);
      $response = new Response(
        'Error',
        "'".$role."' is not a valid role."
      );
      print $response->toJson();
      return;
    }

    // create column -> value mapping array
    $colVal = array(
      "cuid" => $cuid,
      "email" => $email,
      "username" => $username,
      "password" => $password,
      "role" => $role
    );

    // add other fields if present
    if(isset($_POST['first_name']) && !empty($_POST['first_name'])){
      $first_name = mysqli_real_escape_string($this->conn, $_POST['first_name']);
      $colVal['first_name'] = $first_name;
    }
    if(isset($_POST['last_name']) && !empty($_POST['last_name'])){
      $last_name = mysqli_real_escape_string($this->conn, $_POST['last_name']);
      $colVal['last_name'] = $last_name;
    }
    if(isset($_POST['age']) && !empty($_POST['age'])){
      $age = mysqli_real_escape_string($this->conn, $_POST['age']);
      $colVal['age'] = $age;
    }
    if(isset($_POST['phone']) && !empty($_POST['phone'])){
      $phone = mysqli_real_escape_string($this->conn, $_POST['phone']);
      $colVal['phone'] = $phone;
    }
    if(isset($_POST['address']) && !empty($_POST['address'])){
      $address = mysqli_real_escape_string($this->conn, $_POST['address']);
      $colVal['address'] = $address;
    }

    // build sql
    $colstr = '';
    $valstr = '';
    $first = true;
    foreach($colVal as $col => $val){
      if($first){
        $first = false;
        $colstr = $colstr.$col;
        $valstr = $valstr."'".$val."'";
      } else {
        $colstr = $colstr.", ".$col;
        $valstr = $valstr.", '".$val."'";
      }
    }
    $sql = "INSERT INTO users (".$colstr.") VALUES (".$valstr.")";

    // execute and send success/error message
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
        'Successfully added user.'
      );
      print $response->toJson();
    } 
  }

  /*
    Expects: 
      Post with variables 'cuid', at least one other variable from 
      'email', 'username', 'first_name', 'last_name', 'password', 'role'.
    Permissions:
      Admin: Admins may perform this action on any user, and update roles and
        passwords.
      User: Users may perform this action on themselves, but cannot change their
        role or password.
    Success:
      Condition: Update row in users table using given data - no errors
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error/ duplicate keys / bad passwords / invalid role):
      Status Code: 400
      Data: error message
  */
  private function editUser(){
    $cuid = mysqli_real_escape_string($this->conn, $_POST['cuid']);

    $colVal = array();

    // add fields to update if present
    if(isset($_POST['email']) && !empty($_POST['email'])){
      $email = mysqli_real_escape_string($this->conn, $_POST['email']);
      $colVal['email'] = $email;
    }
    if(isset($_POST['username']) && !empty($_POST['username'])){
      $username = mysqli_real_escape_string($this->conn, $_POST['username']);
      $colVal['username'] = $username;
    }
    if(isset($_POST['first_name']) && !empty($_POST['first_name'])){
      $first_name = mysqli_real_escape_string($this->conn, $_POST['first_name']);
      $colVal['first_name'] = $first_name;
    }
    if(isset($_POST['last_name']) && !empty($_POST['last_name'])){
      $last_name = mysqli_real_escape_string($this->conn, $_POST['last_name']);
      $colVal['last_name'] = $last_name;
    }
    if(isset($_POST['age']) && !empty($_POST['age'])){
      $age = mysqli_real_escape_string($this->conn, $_POST['age']);
      $colVal['age'] = $age;
    }
    if(isset($_POST['phone']) && !empty($_POST['phone'])){
      $phone = mysqli_real_escape_string($this->conn, $_POST['phone']);
      $colVal['phone'] = $phone;
    }
    if(isset($_POST['address']) && !empty($_POST['address'])){
      $address = mysqli_real_escape_string($this->conn, $_POST['address']);
      $colVal['address'] = $address;
    }
    // these fields are restricted to admins
    if($_SESSION['role'] == 'admin'){
      if(isset($_POST['password']) && !empty($_POST['password'])){
        if(isset($_POST['password_confirm']) && $_POST['password'] == $_POST['password_confirm']){
          $password = mysqli_real_escape_string($this->conn, $_POST['password']);
          $colVal['password'] = $password;
        } else {
          $response = new Response(
            'Error',
            'Provided passwords do not match.'
          );
          print $response->toJson();
          return;
        }
      }
      if(isset($_POST['role']) && !empty($_POST['role'])){
        $role = mysqli_real_escape_string($this->conn, $_POST['role']);
        if($role != 'user' && $role != 'manager' && $role != 'admin'){
          http_response_code(400);
          $response = new Response(
            'Error',
            "'".$role."' is not a valid role."
          );
          print $reponse->toJson();
          return;
        }
        $colVal['role'] = $role;
      }
    }

    if(count($colVal) < 1){
      http_response_code(400);
      $response = new Response(
        'Error',
        'No data provided to update user.'
      );
      print $response->toJson();
      return;
    }

    // build sql
    $setstr = '';
    $first = true;
    foreach($colVal as $col => $val){
      if($first){
        $first = false;
        $setstr = $setstr.$col."='".$val."'";
      } else {
        $setstr = $setstr.", ".$col."='".$val."'";
      }
    }
    $sql = "UPDATE users SET ".$setstr." WHERE cuid=".$cuid;

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
        'Successfully updated user.'
      );
      print $response->toJson();
    } 
  }

  /*
    Expects: 
      Post with variable 'cuid'
    Permissions:
      Admin: Only admins may perform this action.
    Success:
      Condition: Delete row from users table using given cuid - no errors
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: error message
  */
  private function deleteUser(){
    $cuid = mysqli_real_escape_string($this->conn, $_POST['cuid']);
    $sql = "DELETE FROM users WHERE cuid='".$cuid."'";

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
        'Successfully deleted user.'
      );
      print $response->toJson();
    } 
  }


  /*
    Expects: 
      GET with optional variables 'cuid', 'search', 'show_active', and 'show_pending'
    Permissions:
      Manager: Managers may perform this action.
      User: User may perform this action only when cuid is specified, and only results with matching cuid are returned
    Success:
      Condition: Return rows from contracts table using given variables - no errors
      Status Code: 200
      Data: for each contract: 
        start_date, end_date, cuid, username, serial_no, type, active
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
  */
  private function getContracts(){
    $ac_sql = "SELECT ac.cuid cuid, ac.start_date start_date, ac.end_date end_date, 
      u.username username, i.serial_no serial_no, i.type type, 'true' active 
      FROM active_contracts ac 
      INNER JOIN instruments i ON ac.serial_no = i.serial_no
      INNER JOIN users u ON u.cuid = ac.cuid
      WHERE 1=1";
    $pc_sql = "SELECT pc.cuid cuid, pc.start_date start_date, pc.end_date end_date, 
      u.username username, pc.serial_no serial_no, i.type type, 'false' active 
      FROM pending_contracts pc
      INNER JOIN instruments i ON pc.serial_no = i.serial_no
      INNER JOIN users u ON u.cuid = pc.cuid
      WHERE 1=1";

    if(isset($_GET['cuid'])){
      $cuid = mysqli_real_escape_string($this->conn, $_GET['cuid']);
      $ac_sql = $ac_sql." AND ac.cuid ='".$cuid."'";
      $pc_sql = $pc_sql." AND pc.cuid ='".$cuid."'";
    }
    if(isset($_GET['search'])){
      $search = mysqli_real_escape_string($this->conn, $_GET['search']);
      $ac_sql = $ac_sql." AND (ac.cuid LIKE '%".$search."%' OR 
        ac.serial_no LIKE '%".$search."%' OR 
        u.username LIKE '%".$search."%' OR 
        i.type LIKE '%".$search."%')";
      $pc_sql = $pc_sql." AND (pc.cuid LIKE '%".$search."%' OR 
        pc.serial_no LIKE '%".$search."%' OR 
        u.username LIKE '%".$search."%' OR 
        i.type LIKE '%".$search."%')";
    }

    $sql = '';
    $getActive = (!isset($_GET['show_active']) || $_GET['show_active'] != 'false');
    $getPending = (!isset($_GET['show_pending']) || $_GET['show_pending'] != 'false');
    if($getActive && $getPending){
      $sql = $ac_sql." UNION ALL ".$pc_sql;
    } else if($getActive){
      $sql = $ac_sql;
    } else if($getPending){
      $sql = $pc_sql;
    } else {
      // no possible results, so don't execute query
      $response = new Response(
        'Success',
        'Got contracts',
        array()
      );
      print $response->toJson();
      return;
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

    $contracts = array();
    while($row = $result->fetch_assoc()){
      $contracts[] = $row;
    }

    $response = new Response(
      'Success',
      'Got contracts',
      $contracts
    );
    print $response->toJson();
  }

  /*
    Expects: 
      POST with variables 'cuid', 'serial_no', 'start_date', and 'end_date'
    Permissions:
      User: User may perform this action for themselves (post cuid matches session cuid)
    Success:
      Condition: Insert row into pending contracts table.
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integity error / duplicate keys)
      Status Code: 400
      Data: error message
  */
  private function makeRequest(){
    $cuid = mysqli_real_escape_string($this->conn, $_POST['cuid']);
    $serial_no = mysqli_real_escape_string($this->conn, $_POST['serial_no']);
    $start_date = mysqli_real_escape_string($this->conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($this->conn, $_POST['end_date']);

    $end_comp = strtotime($end_date);
    $start_comp = strtotime($start_date);
    if(!$end_comp || !$start_comp || $end_comp <= $start_comp){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Invalid dates',
        array('end' => $end_comp, 'start' => $start_comp)
      );
      print $response->toJson();
      return;
    }

    $sql = "INSERT INTO pending_contracts (cuid, serial_no, start_date, end_date)
      VALUES ('".$cuid."', '".$serial_no."', '".$start_date."', '".$end_date."')";

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
        'Successfully made request.'
      );
      print $response->toJson();
    } 
  }

  /*
    Expects: 
      Post with variables 'cuid', 'serial_no', 'start_date', and 'end_date'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: insert row into the active_contracts table and then delete it from pending_contracts table if insertion does not fail 
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: error message
  */
  private function approveRequest(){
    $cuid = mysqli_real_escape_string($this->conn, $_POST['cuid']);
    $serial_no = mysqli_real_escape_string($this->conn, $_POST['serial_no']);
    
    // get pending contract information
    $sql = "SELECT * FROM pending_contracts WHERE cuid=".$cuid." AND serial_no='".$serial_no."'";
    
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

    $request = $result->fetch_assoc();
    if(!$request){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Did not find matching pending contract',
        $this->conn->error
      );
      print $response->toJson();
      return;
    }

    $start_date = $request['start_date'];
    $end_date = $request['end_date'];

    // insert into active_contracts
    $sql = "INSERT INTO active_contracts (cuid, serial_no, start_date, end_date) 
    VALUES ('".$cuid."', '".$serial_no."', '".$start_date."', '".$end_date."')";

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

    // delete from pending_contracts
    $sql = "DELETE FROM pending_contracts WHERE cuid=".$cuid." AND serial_no='".$serial_no."'";

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
        'Successfully approved contract'
      );
      print $response->toJson();
    }
  }

  /*
    Expects: 
      Post with variables 'cuid' and 'serial_no'
    Permissions:
      Manager: Managers may perform this action.
      User: Users may perform this action on thier own requests.
    Success:
      Condition: Delete row from pending_contracts table using given cuid - no errors
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: error message
  */
  private function denyRequest(){
    $cuid = mysqli_real_escape_string($this->conn, $_POST['cuid']);
    $serial_no = mysqli_real_escape_string($this->conn, $_POST['serial_no']);
    $sql = "DELETE FROM pending_contracts WHERE cuid=".$cuid." AND serial_no='".$serial_no."'";

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
        'Successfully denied contract.'
      );
      print $response->toJson();
    } 
  }

  /*
    Expects: 
      Post with variable  'serial_no'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Delete row from active_contracts table using given cuid - no errors
      Status Code: 200
      Data: none
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: error message
  */
  private function endContract(){
    $serial_no = mysqli_real_escape_string($this->conn, $_POST['serial_no']);
    $sql = "DELETE FROM active_contracts WHERE serial_no='".$serial_no."'";

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
        'Successfully denied contract.'
      );
      print $response->toJson();
    } 
  }


  /*
    Expects: 
      Nothing
    Perminssions: 
      Admins only
    Success:
      Condition: backup tables deleted and restored to current tables
      Status Code: 200
    Failure (bad credentials):
      Status Code: 401
      Data: username
  */
  private function backupDatabase(){
    $queries = array(
      "delAC_backup" => "DROP TABLE IF EXISTS active_contracts_backup",
      "delPC_backup" => "DROP TABLE IF EXISTS pending_contracts_backup",
      "delUsers_backup" => "DROP TABLE IF EXISTS users_backup",
      "delInstruments_backup" => "DROP TABLE IF EXISTS instruments_backup",
      "copyUsers" => "CREATE TABLE users_backup AS SELECT * FROM users",
      "copyInstruments" => "CREATE TABLE instruments_backup AS SELECT * FROM instruments",
      "moveAC" => "CREATE TABLE active_contracts_backup AS SELECT * FROM active_contracts",
      "copyPC" => "CREATE TABLE pending_contracts_backup AS SELECT * FROM pending_contracts"
    );

    foreach($queries as $name => $sql){
      $result = $this->conn->query($sql);
      if(!$result){
        http_response_code(400);
        $response = new Response(
          'Error',
          'Failed to execute query: '.$name,
          $this->conn->error
        );
        print $response->toJson();
        return;
      } 
    }

    $response = new Response(
      'Success',
      'Successfully created backup.'
    );
    print $response->toJson();
    return;
  }


  /*
    Expects: 
      Nothing
    Perminssions: 
      Admins only
    Success:
      Condition: all backup tables found to exist or at least one doesn't
      Status Code: 200
    Failure (bad credentials):
      Status Code: 401
      Data: username
  */
  private function backupExists(){
    $queries = array(
      "check_users_backup" => "SELECT 1 FROM users_backup LIMIT 1",
      "check_instruments_backup" => "SELECT 1 FROM instruments_backup LIMIT 1",
      "check_active_contracts_backup" => "SELECT 1 FROM active_contracts_backup LIMIT 1",
      "check_pending_contracts_backup" => "SELECT 1 FROM pending_contracts_backup LIMIT 1"
    );

    foreach($queries as $name => $sql){
      $result = $this->conn->query($sql);
      if(!$result){
        $response = new Response(
          'Success',
          'Backup does not exist.',
          0
        );
        print $response->toJson();
        return;
      } 
    }

    $response = new Response(
      'Success',
      'Backup exists.',
      1
    );
    print $response->toJson();
    return;
  }


  /*
    Expects: 
      Nothing
    Perminssions: 
      Admins only
    Success:
      Condition: all queries execute succssfully and database is restored.
      Status Code: 200
    Failure (bad credentials):
      Status Code: 401
      Data: username
  */
  private function restoreDatabase(){
    $queries = array(
      "check_users_backup" => "SELECT 1 FROM users_backup",
      "check_instruments_backup" => "SELECT 1 FROM instruments_backup",
      "check_active_contracts_backup" => "SELECT 1 FROM active_contracts_backup",
      "check_pending_contracts_backup" => "SELECT 1 FROM pending_contracts_backup",
      "drop_active_contracts" => "DROP TABLE IF EXISTS active_contracts",
      "drop_pending_contracts" => "DROP TABLE IF EXISTS pending_contracts",
      "drop_users" => "DROP TABLE IF EXISTS users",
      "drop_instruments" => "DROP TABLE IF EXISTS instruments",
      "restore_users" => "CREATE TABLE users(
          cuid int PRIMARY KEY NOT NULL, 
          username varchar(20) UNIQUE NOT NULL,
          password varchar(20) NOT NULL,
          role enum('user','manager','admin') NOT NULL,
          first_name varchar(20) NULL,
          last_name varchar(20) NULL,
          age int NULL,
          phone varchar(20) NULL,
          address varchar(50) NULL,
          email varchar(40) UNIQUE NOT NULL) 
        AS SELECT * FROM users_backup",
    "restore_instruments" => "CREATE TABLE instruments(
        serial_no varchar(20) PRIMARY KEY NOT NULL, 
        type varchar(20) NOT NULL, 
        cond enum('needs repair','poor','fair','good','new')) 
      AS SELECT * FROM instruments_backup",
    "restore_active_contracts" => "CREATE TABLE active_contracts(
        start_date date NOT NULL,
        end_date date NOT NULL,
        cuid int NOT NULL,
        serial_no varchar(20) NOT NULL,
        CONSTRAINT PK_active_contracts PRIMARY KEY (serial_no),
        FOREIGN KEY (serial_no) REFERENCES instruments(serial_no),
        FOREIGN KEY (cuid) REFERENCES users(cuid)) 
      AS SELECT * FROM active_contracts_backup",
    "restore_pending_contracts" => "CREATE TABLE pending_contracts(
        start_date date NOT NULL,
        end_date date NOT NULL,
        cuid int NOT NULL,
        serial_no varchar(20) NOT NULL,
        CONSTRAINT PK_pending_contracts PRIMARY KEY (serial_no,cuid),
        FOREIGN KEY (serial_no) REFERENCES instruments(serial_no),
        FOREIGN KEY (cuid) REFERENCES users(cuid))
      AS SELECT * FROM pending_contracts_backup",
    );

    foreach($queries as $name => $sql){
      $result = $this->conn->query($sql);
      if(!$result){
        http_response_code(400);
        $response = new Response(
          'Error',
          'Failed to execute query: '.$name,
          $this->conn->error
        );
        print $response->toJson();
        return;
      } 
    }

    $response = new Response(
      'Success',
      'Restored database.'
    );
    print $response->toJson();
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
    $username = mysqli_real_escape_string($this->conn, $username);
    $password = mysqli_real_escape_string($this->conn, $password);

    $sql = "SELECT * FROM users WHERE username='".$username."' AND password='".$password."'";

    // catch bad queries
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

    // if user does not exist, login fails
    $user = $result->fetch_assoc();
    if(!$user){
      http_response_code(401);
      $response = new Response(
        'Error',
        'Invalid Credentials'
      );
      print $response->toJson();
      return;
    }

    // sign user in 
    $this->startUserSession($username);
    $response = new Response(
      'Success',
      'User logged in.'
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variables:
        'cuid', 'email', 'username', 'firstName',
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
    $cuid = mysqli_real_escape_string($this->conn, $_POST['cuid']);
    $email = mysqli_real_escape_string($this->conn, $_POST['email']);
    $first_name = mysqli_real_escape_string($this->conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($this->conn, $_POST['last_name']);
    $username = mysqli_real_escape_string($this->conn, $_POST['username']);
    $password = mysqli_real_escape_string($this->conn, $_POST['password']);
    $password_confirm = mysqli_real_escape_string($this->conn, $_POST['password_confirm']);
    $role = 'user';

    // validate passwords
    if($password != $password_confirm){
      http_response_code(400);
      $response = new Response(
        'Error',
        'Passwords do not match.'
      );
      print $response->toJson();
      return;
    }

    $sql = "INSERT INTO users (cuid, email, username, first_name, last_name, password, role) 
      VALUES ('".$cuid."', '".$email."', '".$username."', '".$first_name."', '".$last_name."', '".$password."', '".$role."')";

    // execute and send success/error message
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

    // sign user in 
    $this->startUserSession($username);
    $response = new Response(
      'Success',
      'User created and logged in.'
    );
    print $response->toJson();
    return;
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
        array("username" => $_SESSION["username"], "role" => $_SESSION['role'])
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
        if(isset($_SESSION['role']) && $_SESSION['role'] == 'user' && isset($_SESSION['cuid']) && $_SESSION['cuid'] == $_GET['cuid']){
          $this->getUsers();
        } else if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
          $this->getUsers();
        } else {
          $this->unauthorized("Only admins can view all users. Users may view themselves.");
        }
        break;

      case "add_user":
        requirePost('cuid');
        requirePost('username');
        requirePost('password');
        requirePost('password_confirm');
        requirePost('role');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
          $this->addUser();
        } else {
          $this->unauthorized('Only admins can add users.');
        }
        break;

      case "edit_user":
        requirePost('cuid');

        if(isset($_SESSION['role']) && (($_SESSION['role'] == 'user' && $_SESSION['cuid'] == $_POST['cuid']) || ($_SESSION['role'] == 'admin'))){
          $this->editUser();
        } else {
          $this->unauthorized('You do not have permission to edit this user.');
        }
        break;

      case "delete_user":
        requirePost("cuid");

        if($_SESSION['cuid'] == $_POST['cuid']){
          $this->unauthorized("You cannot delete yourself.");
        } else if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
          $this->deleteUser();
        } else {
          $this->unauthorized("Only admins can delete users.");
        }
        break;

      case "get_contracts":
        if(isset($_SESSION['role']) && $_SESSION['role'] == 'user' && isset($_GET['cuid']) && $_GET['cuid'] == $_SESSION['cuid']){
          $this->getContracts();
        } else if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->getContracts();
        } else {
          $this->unauthorized("You do not have permission to view the requested contracts.");
        }
        break;

      case "make_request":
        requirePost('cuid');
        requirePost('serial_no');
        requirePost('start_date');
        requirePost('end_date');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'user' && isset($_SESSION['cuid']) && $_SESSION['cuid'] == $_POST['cuid']){
          $this->makeRequest();
        } else {
          $this->unauthorized('You do not have permission to make this request.');
        }
        break;

      case "approve_request":
        requirePost('cuid');
        requirePost('serial_no');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->approveRequest();
        } else {
          $this->unauthorized('Only managers can approve requests.');
        }
        break;

      case "deny_request":
        requirePost('cuid');
        requirePost('serial_no');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'user' && isset($_SESSION['cuid']) && $_SESSION['cuid'] == $_POST['cuid']){
          $this->denyRequest();
        } else if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->denyRequest();
        } else {
          $this->unauthorized('Only managers can deny requests.');
        }
        break;

      case "end_contract":
        requirePost('serial_no');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->endContract();
        } else {
          $this->unauthorized('Only managers can terminate contracts.');
        }
        break;

      case "backup_database":
        if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
          $this->backupDatabase();
        } else {
          $this->unauthorized();
        }
        break;

      case "backup_exists":
        if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
          $this->backupExists();
        } else {
          $this->unauthorized();
        }
        break;

      case "restore_database":
        if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
          $this->restoreDatabase();
        } else {
          $this->unauthorized();
        }
        break;

      case "sign_in":
        requirePost('username');
        requirePost('password');

        $this->signIn($_POST['username'], $_POST['password']);
        break;

      case "sign_up":
        requirePost('cuid');
        requirePost('email');
        requirePost('username');
        requirePost('first_name');
        requirePost('last_name');
        requirePost('password');
        requirePost('password_confirm');

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