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
      Post with variables 'cuid' and 'serial_no'
    Permissions:
      Manager: Only managers may perform this action.
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
    $data = array(
        'username' => $username
    );

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
      array(
        'cuid' => $_POST['cuid'],
        'cuEmail' => $_POST['cuEmail'],
        'username' => $_POST['username'],
        'firstName' => $_POST['firstName'],
        'lastName' => $_POST['lastName'],
        'password' => $_POST['password'],
        'passwordConfirm' => $_POST['passwordConfirm']
      )
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
        if(isset($_SESSION['role']) && $_SESSION['role'] == 'user'){
          $this->getProfileData();
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

      case "deny_request":
        requirePost('cuid');
        requirePost('serial_no');

        if(isset($_SESSION['role']) && $_SESSION['role'] == 'manager'){
          $this->denyRequest();
        } else {
          $this->unauthorized('Only managers can approve and deny requests.');
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

      case "sign_in":
        requirePost('username');
        requirePost('password');

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