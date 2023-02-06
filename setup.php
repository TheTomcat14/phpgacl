<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="admin/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <title>phpGACL - Setup</title>
    <link rel="stylesheet" href="admin/css/font-awesome.min.css">
  </head>
  <body>
    <div class="container">
<?php

$config_file = './gacl.ini.php';

require_once './admin/gacl_admin.inc.php';

require_once ADODB_DIR .'/adodb-xmlschema.inc.php';

$dbTablePrefix = $gacl->dbTablePrefix;
$dbType = $gacl->dbType;
$dbName = $gacl->dbName;
$dbHost = $gacl->dbHost;
$dbUser = $gacl->dbUser;
$dbPassword = $gacl->dbPassword;

$failed = 0;

echo '<h1>phpGACL Database Setup</h1>
<p><strong>Configuration:</strong><br>
driver = <strong>'.$dbType.'</strong>,<br>
host = <strong>'.$dbHost.'</strong>,<br>
user = <strong>'.$dbUser.'</strong>,<br>
database = <strong>'.$dbName.'</strong>,<br>
table prefix = <strong>'.$dbTablePrefix.'</strong></p>';

function echoSuccess($text) {
  echo '<span class="text-success"><strong>Success!</strong></span> '.$text."<br>\n";
}

function echoFailed($text) {
  global $failed;
  echo '<span class="text-danger"><strong>Failed!</strong></span> '.$text."<br>\n";
  $failed++;
}

function echoNormal($text) {
  echo $text."<br>\n";
}

/*
 * Test database connection
 */
echo '<hr><h2>Testing database connection...</h2>'."\n";

if (is_object($db->_connectionID)) {
    echoSuccess('Connected to &quot;<strong>'.$dbType.'</strong>&quot; database on &quot;<strong>'.$dbHost.'</strong>&quot;.');
} else {
  echoFailed('<strong>ERROR</strong> connecting to database,<br>
      are you sure you specified the proper host, user name, password, and database in <strong>admin/gacl_admin.inc.php</strong>?<br>
      Did you create the database, and give read/write permissions to &quot;<strong>'.$dbUser.'</strong>&quot; already?');
  exit;
}

/*
 * Do database specific stuff.
 */
echo '<hr/><h2>Testing database type...</h2>'."\n";

switch ( $dbType ) {
    case ($dbType == "mysql" OR $dbType == "mysqli" OR $dbType == "mysqlt" OR $dbType == "maxsql" ):
        echoSuccess("Compatible database type \"<strong>$dbType</strong>\" detected!");
    echoNormal("Making sure database \"<strong>$dbName</strong>\" exists...");

    $databases = $db->GetCol("show databases");

    if (in_array($dbName, $databases) ) {
        echoSuccess("Good, database \"<strong>$dbName</strong>\" already exists!");
    } else {
        echoNormal("Database \"<strong>$dbName</strong>\" does not exist!");
        echoNormal("Lets try to create it...");

      if (!$db->Execute("create database $dbName") ) {
          echoFailed("Database \"<strong>$dbName</strong>\" could not be created, please do so manually.");
      } else {
          echoSuccess("Good, database \"<strong>$dbName</strong>\" has been created!!");

        //Reconnect. Hrmm, this is kinda weird.
        $db->Connect($dbHost, $dbUser, $dbPassword, $dbName);
      }
    }

    break;
    case ( $dbType == "postgres8" OR $dbType == "postgres7" ):
        echoSuccess("Compatible database type \"<strong>$dbType</strong>\" detected!");

        echoNormal("Making sure database \"<strong>$dbName</strong>\" exists...");

    $databases = $db->GetCol("select datname from pg_database");

    if (in_array($dbName, $databases) ) {
        echoSuccess("Good, database \"<strong>$dbName</strong>\" already exists!");
    } else {
        echoNormal("Database \"<strong>$dbName</strong>\" does not exist!");
        echoNormal("Lets try to create it...");

      if (!$db->Execute("create database $dbName") ) {
          echoFailed("Database \"<strong>$dbName</strong>\" could not be created, please do so manually.");
      } else {
          echoSuccess("Good, database \"<strong>$dbName</strong>\" has been created!!");

        //Reconnect. Hrmm, this is kinda weird.
        $db->Connect($dbHost, $dbUser, $dbPassword, $dbName);
      }
    }

    break;

  case "oci8-po":
      echoSuccess("Compatible database type \"<strong>$dbType</strong>\" detected!");

      echoNormal("Making sure database \"<strong>$dbName</strong>\" exists...");

    $databases = $db->GetCol("select '$dbName' from dual");

    if (in_array($dbName, $databases) ) {
        echoSuccess("Good, database \"<strong>$dbName</strong>\" already exists!");
    } else {
        echoNormal("Database \"<strong>$dbName</strong>\" does not exist!");
        echoNormal("Lets try to create it...");

        if (!$db->Execute("create database $dbName") ) {
            echoFailed("Database \"<strong>$dbName</strong>\" could not be created, please do so manually.");
        } else {
            echoSuccess("Good, database \"<strong>$dbName</strong>\" has been created!!");

            //Reconnect. Hrmm, this is kinda weird.
            $db->Connect($dbHost, $dbUser, $dbPassword, $dbName);
        }
    }

    break;

  case "mssql":
      echoSuccess("Compatible database type \"<strong>$dbType</strong>\" detected!");

      echoNormal("Making sure database \"<strong>$dbName</strong>\" exists...");

    $databases = $db->GetCol("select CATALOG_NAME from INFORMATION_SCHEMA.SCHEMATA");

    if (in_array($dbName, $databases) ) {
        echoSuccess("Good, database \"<strong>$dbName</strong>\" already exists!");
    } else {
        echoNormal("Database \"<strong>$dbName</strong>\" does not exist!");
        echoNormal("Lets try to create it...");

        if (!$db->Execute("create database $dbName") ) {
            echoFailed("Database \"<strong>$dbName</strong>\" could not be created, please do so manually.");
        } else {
            echoSuccess("Good, database \"<strong>$dbName</strong>\" has been created!!");

            //Reconnect. Hrmm, this is kinda weird.
            $db->Connect($dbHost, $dbUser, $dbPassword, $dbName);
        }
    }

    break;

  default:
      echoNormal("Sorry, <strong>setup.php</strong> currently does not fully support \"<strong>$dbType</strong>\" databases.
          <br>I'm assuming you've already created the database \"$dbName\", attempting to create tables.
          <br> Please email <strong>$authorEmail</strong> code to detect if a database is created or not so full support for \"<strong>$dbType</strong>\" can be added.");
}


/*
 * Attempt to create tables
 */
// Create the schema object and build the query array.
$schema = new adoSchema($db);
$schema->SetPrefix($dbTablePrefix, FALSE); //set $underscore == FALSE

// Build the SQL array
$schema->ParseSchema('schema.xml');

// maybe display this if $gacl->debug is true?
if ($gacl->_debug) {
  print "Here's the SQL to do the build:<br>\n<code>";
  print $schema->getSQL('html');
  print "</code>\n";
  // exit;
}

// Execute the SQL on the database
#ADODB's xmlschema is being lame, continue on error.
$schema->ContinueOnError(TRUE);
$result = $schema->ExecuteSchema();

if ($result != 2) {
    echoFailed('Failed creating tables. Please enable DEBUG mode (set it to TRUE in $gacl_options near top of admin/gacl_admin.inc.php) to see the error and try again. You will most likely need to delete any tables already created.');
}

if ( $failed <= 0 ) {
    echoSuccess('
Installation Successful!!!
<div class="text-center">
<span class="text-danger"><strong>*IMPORTANT*</strong></span><br>
<p>Please make sure you create the <strong>&lt;phpGACL root&gt;/admin/templates_c</strong> directory,
and give it <strong>write permissions</strong> for the user your web server runs as.</p>
<p>Please read the manual, and docs/examples/* to familiarize yourself with phpGACL.</p>
<a href="admin/about.php?first_run=1"><strong>Let\'s get started!</strong></a>
</div>
');
} else {
    echoFailed('Please fix the above errors and try again.');
}
?>
    </div>
  </body>
</html>