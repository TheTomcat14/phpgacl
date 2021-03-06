<?php
/*
meinhard_jahn@web.de, 20041102: link to acl_test2.php and acl_test3.php
*/
/*
if (!empty($_GET['debug'])) {
  $debug = $_GET['debug'];
}
*/
@set_time_limit(600);

require_once('../profiler.inc');
$profiler = new Profiler(true, true);

require_once "gacl_admin.inc.php";

$smarty->assign("return_page", $_SERVER['PHP_SELF']);

$smarty->assign('current', 'acl_test');
$smarty->assign('page_title', 'ACL Test');

$smarty->assign("phpgacl_version", $gaclApi->getVersion());
$smarty->assign("phpgacl_schema_version", $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/acl_test.tpl');
