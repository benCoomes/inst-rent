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
    $types = ["trumpet", "flute", "tuba", "clarinet", "french horn", "sousaphone", "didgeridoo"];
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

    if($_GET['serial_no']){
      // just set serial no requests to same inst for now
      $lookUpResults = [];
      $lookUpResults[] = $instruments[0];
      $response->setData($lookUpResults);
    } else if($_SESSION['role']== 'user'){
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
      Post with variables 'serialNo', 'type' and 'condition'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Insert row into instruments table using given data - no errors
      Status Code: 200
      Data: serialNo, type, condition
    Failure (insuffecient permission):
      Status Code: 401
      Data: username
    Failure (integrity error/ duplicate keys):
      Status Code: 400
      Data: serialNo, type, condition
  */
  private function addInstrument(){
    // check permissions
    if($_SESSION['role'] != 'manager'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to add an instrument.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    // 'add' instrument - put sql code below in production
    $serial_no = $_POST["serialNo"];
    $type = $_POST["type"];
    $condition = $_POST["condition"];

    $response = new Response(
      'Success',
      'Successfully added instrument.',
      ['serial_no' => $serial_no, 'type' => $type, 'condition' => $condition]
    );

    print $response->toJson();
  }

  /*
    Expects: 
      Post with variables 'serial_no' and 'cond'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Update row in instruments table using given data
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
    if($_SESSION['role'] != 'manager'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to update an instrument.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $serial_no = $_POST['serial_no'];
    $cond = $_POST['cond'];

    // sql goes here

    $updatedInst = [
      "serial_no" => $serial_no,
      "cond" => $cond
    ];


    $response = new Response(
      'Success',
      'Updated instrument.',
      $updatedInst
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variables 'serialNo'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Delete row from instruments table using given data - no errors
      Status Code: 200
      Data: serialNo of deleted instrument
    Failure (insuffecient permission):
      Status Code: 401
      Data: username
    Failure (integrity error/ referential integrity):
      Status Code: 400
      Data: serialNo
  */
  private function deleteInstrument(){
    // check permissions
    if($_SESSION['role'] != 'manager'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to add an instrument.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    // sql here

    $response = new Response(
      'Success',
      'Successfully added instrument.',
      ['serial_no' => $serial_no]
    );

    print $response->toJson();
  }

  private function getProfileData(){
    if(!isset($_GET['cuid']) || $_GET['cuid'] != $_SESSION['cuid']){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to view users',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
    }

    $userData = [
      [
        "cuid" => "2000200",
        "cuEmail" => "bcoomes@g.clemson.edu",
        "firstName" => "Ben",
        "lastName" => "Coomes",
        "age" => 21,
        "telephone" => "864-999-2180",
        "address" => "251 Happy Rock Lane, Clemson, SC"
      ]
    ];

    $response = new Response(
      'Success',
      'User data retrieved',
      $userData
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      GET with optional variables: 'search', 'showUsers', 'showManagers', 'showAdmins', and 'cuid'.
    Permissions:
      Admins: only admins may view this information.
    Success:
      Condition: query completed, even if empty
      Status Code: 200
      Data: Users returned by query, without password data. Select by 'search', role if specified.
        If 'show*' is not in QS, get users with role = *
        If 'show*' is in QS, check its value (false or true). Do not get users with role = * if false.
        If 'cuid' is in QS, select by exact cuid.
    Failure (permissions):
      status code: 401
      data: session username 
  */
  private function getUsers(){
    if($_SESSION['role'] == 'user'){
      $this->getProfileData();
      return;
    } else if($_SESSION['role'] != 'admin'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to view users',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $users = [
      [
        "cuid" => "1000100",
        "username" => "bcoomes",
        "firstName" => "Ben",
        "lastName" => "Coomes",
        "role" => "user",
        "email" => "bcoomes@email.com"
      ],
      [
        "cuid" => "2000200",
        "username" => "cjwest",
        "firstName" => "Chris",
        "lastName" => "West",
        "role" => "user",
        "email" => "cjwest@email.com"
      ],
      [
        "cuid" => "3000300",
        "username" => "admin",
        "firstName" => "Database",
        "lastName" => "Administrator",
        "role" => "admin",
        "email" => "admin@email.com"
      ],
      [
        "cuid" => "4000400",
        "username" => "speedy",
        "firstName" => "John",
        "lastName" => "Speed",
        "role" => "manager",
        "email" => "speed@clemson.edu"
      ],
      [
        "cuid" => "5000500",
        "username" => "rando",
        "firstName" => "Random",
        "lastName" => "Man",
        "role" => "manager",
        "email" => "rando@email.com"
      ]
    ];

    $data = [];

    if(isset($_GET['cuid'])){
      if(!empty($_GET['cuid'])){
        foreach($users as $user){
          if($user['cuid'] == $_GET['cuid']){
            $data[] = $user;
            break;
          }
        }
      }
    } else {
      $data = $users;
    }

    $response = new Response(
      'Success',
      'Retrieved users data',
      $data
    );
    print $response->toJson();
    return;
  }

  private function editProfile(){
    if(!isset($_POST['cuid']) || $_POST['cuid'] != $_SESSION['cuid']){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to edit other users',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
    }

    // sql 

    $response = new Response(
      'Success',
      'Updated profile.'
    );
    print $response->toJson();
    return;

  }

  /*
    Expects: 
      Post with variables shown in function body, pasword and passwordConfirm optional.
    Permissions:
      Admin: Only admins may perform this action.
    Success:
      Condition: Update row in users table using given data - no errors
      Status Code: 200
      Data: username, role, cuid, firstName, lastName, email, of updated user
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error/ duplicate keys / bad passwords / invalid role):
      Status Code: 400
      Data: all user data from post
  */
  private function editUser(){
    if($_SESSION['role'] == 'user'){
      $this->editProfile();
      return;
    }
    if($_SESSION['role'] != 'admin'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to update a user.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $cuid = $_POST['cuid'];
    $cuEmail = $_POST['cuEmail'];
    $username = $_POST['username'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $role = $_POST['role'];

    // sql goes here

    $updatedUser = [
      "cuid" => $cuid,
      "cuEmail" => $cuEmail,
      "username" => $username,
      "firstName" => $firstName,
      "lastName" => $lastName,
      "role" => $role
    ];

    if(isset($_POST['password']) && isset($_POST['password'])){
      if(!empty($_POST['password']) && $_POST['passwordConfirm'] == $_POST['password']){
        $updatedUser['password'] = $_POST['password'];
      }
    }

    $response = new Response(
      'Success',
      'Updated user.',
      $updatedUser
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variables shown in function body.
    Permissions:
      Admin: Only admins may perform this action.
    Success:
      Condition: Insert row into users table using given data - no errors
      Status Code: 200
      Data: username, role, cuid of new user
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error/ duplicate keys / bad passwords / invalid role):
      Status Code: 400
      Data: all user data from post
  */
  private function addUser(){
    if($_SESSION['role'] != 'admin'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to add a user.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    // 'add' user - put sql code below in production
    $cuid = $_POST['cuid'];
    $cuEmail = $_POST['cuEmail'];
    $username = $_POST['username'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $password = $_POST['password'];
    $passwordConfirm = $_POST['passwordConfirm'];
    $role = $_POST['role'];

    $newUser = [
      "cuid" => $cuid,
      "cuEmail" => $cuEmail,
      "username" => $username,
      "firstName" => $firstName,
      "lastName" => $lastName,
      "password" => $password,
      "passwordConfirm" => $passwordConfirm,
      "role" => $role
    ];

    $response = new Response(
      'Success',
      'Added user.',
      $newUser
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variable 'cuid'
    Permissions:
      Admin: Only admins may perform this action.
    Success:
      Condition: Delete row from users table using given cuid - no errors
      Status Code: 200
      Data: username, cuid of deleted user
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: cuid
  */
  private function deleteUser(){
    $cuid = $_POST["cuid"];

    if($_SESSION['role'] != 'admin'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to delete a user.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    if($_SESSION['cuid'] == $cuid){
      http_response_code(400);
      $response = new Response(
        'Error',
        'User cannot delete themselves.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }


    // do sql here

    $response = new Response(
      'Success',
      'Delted user.',
      ["cuid" => $cuid]
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      GET with variables 'cuid'
    Permissions:
      Manager: Managers may perform this action.
      User: User may perform this action only when cuid is qs matches session cuid
    Success:
      Condition: Return rows from contracts table where cuid matches given cuid
      Status Code: 200
      Data: for each contract: 
        start, end, cuid, username, serial_no, type, status
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
  */
  private function getUserContracts(){
    if($_SESSION['cuid'] != $_GET['cuid']){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to view other users contracts',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $contracts = [
      [
        "start" => "10/10/10",
        "end" => "10/10/11",
        "cuid" => $_SESSION['cuid'],
        "serial_no" => "LP123213",
        "type" => "Guitar",
        "status" => "active"
      ],
      [
        "start" => "12/12/12",
        "end" => "6/6/15",
        "cuid" => $_SESSION['cuid'],
        "serial_no" => "EF12342",
        "type" => "West",
        "status" => "active"
      ],
      [
        "start" => "10/19/10",
        "end" => "11/11/11",
        "cuid" => $_SESSION['cuid'],
        "serial_no" => "LPLPS435",
        "type" => "Cello",
        "status" => "pending"
      ]
    ];


    $response = new Response(
      'Success',
      'Called get user contracts (not implemented)',
      $contracts
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      GET with optional variables 'cuid', 'search', 'showActive', and 'showPending'
    Permissions:
      Manager: Managers may perform this action.
      User: User may perform this action only when cuid is specified, and only results with matching cuid are returned
    Success:
      Condition: Return rows from contracts table using given variables - no errors
      Status Code: 200
      Data: for each contract: 
        start, end, cuid, username, serial_no, type, status
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
  */
  private function getContracts(){
    if($_SESSION['role'] == 'user'){
      // spaghettii code, woooooo!
      $this->getUserContracts();
      return;
    } else if($_SESSION['role'] != 'manager'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to view contracts',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $contracts = [
      [
        "start" => "10/10/10",
        "end" => "10/10/11",
        "cuid" => "1000100",
        "username" => "bcoomes",
        "serial_no" => "LP123213",
        "type" => "Guitar",
        "status" => "active"
      ],
      [
        "start" => "12/12/12",
        "end" => "6/6/15",
        "cuid" => "2000200",
        "username" => "cjwest",
        "serial_no" => "EF12342",
        "type" => "West",
        "status" => "active"
      ],
      [
        "start" => "10/19/10",
        "end" => "11/11/11",
        "cuid" => "1000100",
        "username" => "bcoomes",
        "serial_no" => "LPLPS435",
        "type" => "Cello",
        "status" => "pending"
      ]
    ];

    if(isset($_GET['showPending'])){
      if(!empty($_GET['showPending'])){
        if($_GET['showPending'] == 'false'){   
          $contracts = [
            [
              "start" => "10/10/10",
              "end" => "10/10/11",
              "cuid" => "1000100",
              "username" => "bcoomes",
              "serial_no" => "LP123213",
              "type" => "Guitar",
              "status" => "active"
            ],
            [
              "start" => "12/12/12",
              "end" => "6/6/15",
              "cuid" => "2000200",
              "username" => "cjwest",
              "serial_no" => "EF12342",
              "type" => "West",
              "status" => "active"
            ]
          ];
        }
      }
    }

    if(isset($_GET['showActive'])){
      if(!empty($_GET['showActive'])){
        if($_GET['showActive'] == 'false'){   
          $contracts = [
            [
              "start" => "10/19/10",
              "end" => "11/11/11",
              "cuid" => "1000100",
              "username" => "bcoomes",
              "serial_no" => "LPLPS435",
              "type" => "Cello",
              "status" => "pending"
            ]
          ];
        }
      }
    }

    if(isset($_GET['showActive']) && isset($_GET['showPending'])){
      if(!empty($_GET['showActive']) && !empty($_GET['showPending'])){
        if($_GET['showActive'] == 'false' && $_GET['showPending'] == 'false'){
          $contracts = [];
        }
      }
    }

    $response = new Response(
      'Success',
      'Retrieved contacts data',
      $contracts
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variable 'serial_no', 'cuid', 'start', and 'end'
    Permissions:
      User: Only users may perform this action.
    Success:
      Condition: Add row to pending requests.
      Status Code: 200
      Data: cuid, serial_no, start and end for added request
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / bad data / constraint violation):
      Status Code: 400
      Data: serial_no, cuid, start, end 
  */
  private function makeRequest(){
    if($_SESSION['role'] != 'user' || $_SESSION['cuid'] != $_POST['cuid']){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to create a request.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    // check dates are valid

    // do sql here

    $response = new Response(
      'Success',
      'Created request (not implemented)',
      []
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variable 'serial_no, cuid'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Delete row from pending requests. Add row to active requests.
      Status Code: 200
      Data: data for approved contract
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: serial_no, cuid
  */
  private function approveRequest(){
    if($_SESSION['role'] != 'manager'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to approve a request.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $response = new Response(
      'Success',
      'Approved request (not implemented)',
      []
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variable 'serial_no, cuid'
    Permissions:
      Manager: Managers may perform this action.
      User: Users may only perform this action on their own contracts
    Success:
      Condition: Delete row from pending requests.
      Status Code: 200
      Data: data for deleted request
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: serial_no, cuid
  */
  private function denyRequest(){
    if(!($_SESSION['role'] == 'manager') &&
      !($_SESSION['role'] == 'user' && $_POST['cuid'] == $_SESSION['cuid']) ){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to deny a request.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $response = new Response(
      'Success',
      'Denied request (not implemented)',
      []
    );
    print $response->toJson();
    return;
  }

  /*
    Expects: 
      Post with variable 'serial_no'
    Permissions:
      Manager: Only managers may perform this action.
    Success:
      Condition: Delete row from active requests.
      Status Code: 200
      Data: data for deleted contract
    Failure (insuffecient permission):
      Status Code: 401
      Data: username of session
    Failure (integrity error / referential integrity):
      Status Code: 400
      Data: serial_no
  */
  private function endContract(){
    if($_SESSION['role'] != 'manager'){
      http_response_code(401);
      $response = new Response(
        'Error',
        'User does not have permission to end a contract.',
        ["username" => $_SESSION["username"]]
      );
      print $response->toJson();
      return;
    }

    $response = new Response(
      'Success',
      'Ended contract (not implemented)',
      []
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

      case "add_instrument":
        $this->addInstrument();
        break;

      case "edit_instrument":
        $this->editInstrument();
        break;

      case "delete_instrument":
        $this->deleteInstrument();
        break;

      case "get_users":
        $this->getUsers();
        break;

      case "edit_user":
        $this->editUser();
        break;

      case "add_user":
        $this->addUser();
        break;

      case "delete_user":
        $this->deleteUser();
        break;

      case "get_contracts":
        $this->getContracts();
        break;

      case "make_request":
        $this->makeRequest();
        break;

      case "approve_request":
        $this->approveRequest();
        break;

      case "deny_request":
        $this->denyRequest();
        break;

      case "end_contract":
        $this->endContract();
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