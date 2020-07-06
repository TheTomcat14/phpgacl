<?php
require_once "gacl_admin.inc.php";

function getSystemInfo()
{
    global $gaclApi;

    // Grab system info
    $systemInfo .= 'PHP Version: ' . phpversion() . "\n";
    $systemInfo .= 'Zend Version: ' . zend_version() . "\n";
    $systemInfo .= 'Web Server: ' . $_SERVER['SERVER_SOFTWARE'] . "\n\n";
    $systemInfo .= 'phpGACL Settings: ' . "\n";
    $systemInfo .= '  phpGACL Version: ' . $gaclApi->getVersion() . "\n";
    $systemInfo .= '  phpGACL Schema Version: ' . $gaclApi->getSchemaVersion() . "\n";

    if ($gaclApi->_caching == true) {
        $caching = 'True';
    } else {
        $caching = 'False';
    }
    $systemInfo .= '  Caching Enabled: ' . $caching . "\n";

    if ($gaclApi->forceCacheExpire == true) {
        $forceCacheExpire = 'True';
    } else {
        $forceCacheExpire = 'False';
    }
    $systemInfo .= '  Force Cache Expire: ' . $forceCacheExpire . "\n";

    $systemInfo .= '  Database Prefix: \'' . $gaclApi->dbTablePrefix . "'\n";
    $systemInfo .= '  Database Type: ' . $gaclApi->dbType . "\n";

    $databaseServerInfo = $gaclApi->db->ServerInfo();
    $systemInfo .= '  Database Version: ' . $databaseServerInfo['version'] . "\n";
    $systemInfo .= '  Database Description: ' . $databaseServerInfo['description'] . "\n\n";

    $systemInfo .= 'Server Name: ' . $_SERVER["SERVER_NAME"] . "\n";
    $systemInfo .= '  OS: ' . PHP_OS . "\n";
    $systemInfo .= '  IP Address: ' . $_SERVER["REMOTE_ADDR"] . "\n";
    $systemInfo .= '  Browser: ' . $_SERVER["HTTP_USER_AGENT"] . "\n\n";

    $systemInfo .= 'System Information: ' . php_uname() . "\n";

    return trim($systemInfo);
}

function submitSystemInfo($systemInformation, $systemInfoMd5)
{
    $md5sum = md5(trim($systemInformation));

    if (trim($systemInfoMd5) == $md5sum) {
        $tainted = 'FALSE';
    } else {
        $tainted = 'TRUE';
    }

    mail('phpgacl@snappymail.ca', 'phpGACL Report... ', "" . $systemInformation . "\n\nTainted: $tainted");

    return $tainted;
}

switch ($_POST['action']) {
    case 'Submit':
        $gaclApi->debugText("Submit!!");

        submitSystemInfo($_POST['system_information'], $_POST['system_info_md5']);

        echo "<div class=\"text-center\">Thanks for contributing to phpGACL. <br> Click <a href=\"acl_list.php\">here</a> to proceed to the Administration Interface.</div><br>\n";
        exit();
        break;
    default:
        $systemInfo = getSystemInfo();

        // Read credits.
        $smarty->assign("credits", htmlentities(implode('', file('../CREDITS'))));

        $smarty->assign("system_info", $systemInfo);
        $smarty->assign("system_info_md5", md5($systemInfo));
        break;
}

$smarty->assign("first_run", $_GET['first_run']);
$smarty->assign("return_page", $_SERVER['PHP_SELF']);
$smarty->assign('current', 'about');

if ($_GET['first_run']) {
    $smarty->assign('page_title', 'Installation Report');
    $smarty->assign('hidemenu', 1);
} else {
    $smarty->assign('page_title', 'About phpGACL');
}

$smarty->assign("phpgacl_version", $gaclApi->getVersion());
$smarty->assign("phpgacl_schema_version", $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/about.tpl');
