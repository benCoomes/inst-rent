<?

// BELOW FOR REFERENCE - NOT FOR USE IN INST_RENT

requireQSV("action");

// connect to database 
$configFile = fopen("configpathhere", "r") or die ("Could not find db configuration file!");
$config = json_decode(fread($configFile, filesize("configpathhere")), true);
fclose($configFile);

$db = mysqli_connect($config["host"], $config["username"], $config["password"], $config["database"]);

if (mysqli_connect_error()){
  die('{"error" : "There was an error connecting to the database: '.mysqli_connect_error().'"}');
}


// get all qs variables
$action = $_GET["action"];

//do specified action
switch($action){
  case "get_documents":
    getDocuments($db);
    break;

  case "get_cool_people":
    getCoolPeople($db);
    break;

  case "get_resources":
    getResources($db);
    break;

  case "get_nafme":
    getNafme($db);
    break;

  case "get_slides":
    getSlides($db);
    break;

  default:
    die('{"error" : "The action \''.$action.'\' is not recognized."}');
    break;
}

function getDocuments($db){
  $query = "SELECT * FROM documents ORDER BY due_date ASC";
  if ($result = mysqli_query($db, $query)){
    $rows = array();
    while($row = mysqli_fetch_assoc($result)){
      // decode actions here so that they are properly encoded later.
      $row['actions'] = json_decode($row['actions']);
      $rows[] = $row;
    }

    print json_encode($rows, 2);
  } else {
    die('{"error" : "There was an error executing the query"}');
  }
}

// must pass all needed variables to functions, they don't exist in the function's scope. - or get them once in the function
function getCoolPeople($db){
  $query = "SELECT * FROM cool_people";
  if ($result = mysqli_query($db, $query)){
    $rows = array();
    while($row = mysqli_fetch_assoc($result)){
      $rows[] = $row;   
    }

    print json_encode($rows);
  } else {
    die('{"error" : "'.mysqli_error($db).'"}');
  }
}

function getResources($db){
  $query = "SELECT * FROM resources ORDER BY 'date' ASC";
  if ($result = mysqli_query($db, $query)){
    $rows = array();
    while($row = mysqli_fetch_assoc($result)){
      // decode actions here so that they are properly encoded later.
      $row['actions'] = json_decode($row['actions']);
      $rows[] = $row;   
    }

    print json_encode($rows);
  } else {
    die('{"error" : "'.mysqli_error($db).'"}');
  }
}

function getNafme($db){
  $query = "SELECT * FROM national_association_for_music_education";
  if ($result = mysqli_query($db, $query)){
    $rows = array();
    while($row = mysqli_fetch_assoc($result)){
      // decode actions here so that they are properly encoded later.
      $row['actions'] = json_decode($row['actions']);
      $rows[] = $row;   
    }

    print json_encode($rows);
  } else {
    die('{"error" : "'.mysqli_error($db).'"}');
  }
}

function getSlides($db){
  $query = "SELECT * FROM slides";
  if ($result = mysqli_query($db, $query)){
    $rows = array();
    while($row = mysqli_fetch_assoc($result)){
      $rows[] = $row;
    }

    print json_encode($rows);
  } else {
    die('{"error":"'.mysqli_error($db).'"}');
  }
}


// require that a query string variable with the name $qsv exists
function requireQSV($qsv){
  $present = False;

  if(isset($_GET[$qsv])){
    if(!empty($_GET[$qsv])){
      $present = True;
    }
  }

  if(!$present){
    // TODO: what response code does this send? Should it be a 500 error? 
    die('{"error" : "The required query string variable \''.$qsv.'\' is not present."}');
  }
}

?>
