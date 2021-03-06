<?php
require_once '../../../vendor/autoload.php';
if (isset($_GET['do'])){
  $URL = $_GET['do'];
} else {
  $URL = 'index';
}

include_once('header.php');
?>
<div id="mid-r">
  <div id="mid-l">
      <div>
          <p>
          <b>Millenium Falcon API Example  by Ross Lawley.</b>
        </p>
<?php
include_once('millenniumFalcon.inc');
  switch ($URL) {
    case 'index':
      include('welcome.php');
    break;
    case 'example1':
      $outputDebug = true;
      $gaclApi = new gacl_api($gacl_options);
                        $gaclApi->clear_database();
      include('definingAccessControl.php');
    break;
    case 'example2':
      $outputDebug = false;
      $gaclApi = new gacl_api($gacl_options);
      $gaclApi->clear_database();
      include('definingAccessControl.php');
      $outputDebug = true;
      include('fineGrainAccessControl.php');
    break;
    case 'example3':
      $outputDebug = false;
      $gaclApi = new gacl_api($gacl_options);
      $gaclApi->clear_database();
      include('definingAccessControl.php');
      include('fineGrainAccessControl.php');
      $outputDebug = true;
      include('Multi-levelGroups.php');
    break;
  default:
    include('welcome.php');
    break;
}
?>
    </div>
  </div>
</div>
<?php include_once('footer.php'); ?>
