<?php
/*
 * meinhard_jahn@web.de, 20041102: axo implemented
 */
/*
 * if (!empty($_GET['debug'])) {
 * $debug = $_GET['debug'];
 * }
 */
@set_time_limit(600);

require_once '../profiler.inc.php';
$profiler = new Profiler(true, true);

require_once "gacl_admin.inc.php";
/*
 * $query = '
 * SELECT a.value AS a_value, a.name AS a_name,
 * b.value AS b_value, b.name AS b_name,
 * c.value AS c_value, c.name AS c_name,
 * d.value AS d_value, d.name AS d_name
 * FROM '. $gaclApi->dbTablePrefix .'aco_sections a
 * LEFT JOIN '. $gaclApi->dbTablePrefix .'aco b ON a.value=b.section_value,
 * '. $gaclApi->dbTablePrefix .'aro_sections c
 * LEFT JOIN '. $gaclApi->dbTablePrefix .'aro d ON c.value=d.section_value
 * ORDER BY a.value, b.value, c.value, d.value';
 */

$query = 'SELECT a.value AS a_value, a.name AS a_name, '
. 'b.value AS b_value, b.name AS b_name, '
. 'c.value AS c_value, c.name AS c_name, '
. 'd.value AS d_value, d.name AS d_name, '
. 'e.value AS e_value, e.name AS e_name, '
. 'f.value AS f_value, f.name AS f_name '
. 'FROM ' . $gaclApi->dbTablePrefix . 'aco_sections a '
. 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'aco b ON a.value = b.section_value, '
. $gaclApi->dbTablePrefix . 'aro_sections c '
. 'LEFT JOIN	' . $gaclApi->dbTablePrefix . 'aro d ON c.value = d.section_value, '
. $gaclApi->dbTablePrefix . 'axo_sections e '
. 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'axo f ON e.value = f.section_value '
. 'ORDER BY a.value, b.value, c.value, d.value, e.value, f.value';

// $rs = $db->Execute($query);
$rs = $db->pageexecute($query, $gaclApi->itemsPerPage, $_GET['page']);
$rows = $rs->GetRows();

/*
 * echo("<pre>");
 * print_r($rows);
 * echo("</pre>");
 */

$totalRows = count($rows);

while (list (, $row) = @each($rows)) {
    list ($acoSectionValue, $acoSectionName, $acoValue, $acoName,
        $aroSectionValue, $aroSectionName, $aroValue, $aroName,
        $axoSectionValue, $axoSectionName, $axoValue, $axoName) = $row;

    $aclCheckBeginTime = $profiler->getMicroTime();
    $aclResult = $gacl->aclQuery($acoSectionValue, $acoValue, $aroSectionValue, $aroValue, $axoSectionValue, $axoValue);
    $aclCheckEndTime = $profiler->getMicroTime();

    $access = &$aclResult['allow'];
    $returnValue = &$aclResult['return_value'];

    $aclCheckTime = ($aclCheckEndTime - $aclCheckBeginTime) * 1000;
    $totalAclCheckTime += $aclCheckTime;

    if ($acoSectionName != $tmpAcoSectionName or $acoName != $tmpAcoName) {
        $displayAcoName = "$acoSectionName > $acoName";
    } else {
        $displayAcoName = "<br>";
    }

    $acls[] = [
        'aco_section_value' => $acoSectionValue,
        'aco_section_name'  => $acoSectionName,
        'aco_value'         => $acoValue,
        'aco_name'          => $acoName,
        'aro_section_value' => $aroSectionValue,
        'aro_section_name'  => $aroSectionName,
        'aro_value'         => $aroValue,
        'aro_name'          => $aroName,
        'axo_section_value' => $axoSectionValue,
        'axo_section_name'  => $axoSectionName,
        'axo_value'         => $axoValue,
        'axo_name'          => $axoName,
        'access'            => $access,
        'return_value'      => $returnValue,
        'acl_check_time'    => number_format($aclCheckTime, 2),
        'display_aco_name'  => $displayAcoName
    ];

    $tmpAcoSectionName = $acoSectionName;
    $tmpAcoName = $acoName;
}

// echo "<br><br>$x ACL_CHECK()'s<br>\n";

$smarty->assign("acls", $acls);

$smarty->assign("total_acl_checks", $totalRows);
$smarty->assign("total_acl_check_time", $totalAclCheckTime);

if ($totalRows > 0) {
    $avgAclCheckTime = $totalAclCheckTime / $totalRows;
}
$smarty->assign("avg_acl_check_time", number_format(($avgAclCheckTime + 0), 2));
$smarty->assign("paging_data", $gaclApi->getPagingData($rs));
$smarty->assign("return_page", $_SERVER['PHP_SELF']);
$smarty->assign('current', 'acl_test');
$smarty->assign('page_title', '3-dim. ACL Test');
$smarty->assign("phpgacl_version", $gaclApi->getVersion());
$smarty->assign("phpgacl_schema_version", $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/acl_test3.tpl');
