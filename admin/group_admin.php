<?php
require_once 'gacl_admin.inc.php';

// GET takes precedence.
if ($_GET['group_type'] != '') {
    $groupType = $_GET['group_type'];
} else {
    $groupType = $_POST['group_type'];
}

switch (strtolower(trim($groupType))) {
    case 'axo':
        $groupType     = 'axo';
        $groupTable    = $gaclApi->dbTablePrefix . 'axo_groups';
        $groupMapTable = $gaclApi->dbTablePrefix . 'groups_axo_map';

        $smarty->assign('current', 'axo_group');
        break;
    default:
        $groupType     = 'aro';
        $groupTable    = $gaclApi->dbTablePrefix . 'aro_groups';
        $groupMapTable = $gaclApi->dbTablePrefix . 'groups_aro_map';

        $smarty->assign('current', 'aro_group');
        break;
}

switch ($_POST['action']) {
    case 'Delete':
        // See edit_group.php
        break;
    default:
        $formattedGroups = $gaclApi->formatGroups($gaclApi->sortGroups($groupType), 'HTML');

        $query = 'SELECT a.id, a.name, a.value, COUNT(b.' . $groupType . '_id) '
        . 'FROM ' . $groupTable . ' a '
        . 'LEFT JOIN ' . $groupMapTable . ' b ON b.group_id = a.id '
        . 'GROUP BY a.id, a.name, a.value';

        $rs = $db->Execute($query);

        $groupData = [];

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                $groupData[$row[0]] = [
                    'name'  => $row[1],
                    'value' => $row[2],
                    'count' => $row[3]
                ];
            }
        }

        $groups = [];

        foreach ($formattedGroups as $id => $name) {
            $groups[] = [
                'id'           => $id,
                // 'parent_id' => $parentId,
                // 'family_id' => $family_id,
                'name'         => $name,
                'raw_name'     => $groupData[$id]['name'],
                'value'        => $groupData[$id]['value'],
                'object_count' => $groupData[$id]['count']
            ];
        }

        $smarty->assign('groups', $groups);
        break;
}

$smarty->assign('group_type', $groupType);
$smarty->assign('return_page', $_SERVER['REQUEST_URI']);

$smarty->assign('current', $groupType . '_group');
$smarty->assign('page_title', strtoupper($groupType) . ' Group Admin');

$smarty->assign('phpgacl_version', $gaclApi->getVersion());
$smarty->assign('phpgacl_schema_version', $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/group_admin.tpl');
