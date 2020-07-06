<?php
require_once 'gacl_admin.inc.php';

// GET takes precedence.
if ($_GET['group_type'] == '') {
    $groupType = $_POST['group_type'];
} else {
    $groupType = $_GET['group_type'];
}

if ($_GET['return_page'] == '') {
    $returnPage = $_POST['return_page'];
} else {
    $returnPage = $_GET['return_page'];
}

switch (strtolower(trim($groupType))) {
    case 'axo':
        $groupType = 'axo';
        $groupTable = $gaclApi->dbTablePrefix . 'axo_groups';
        break;
    default:
        $groupType = 'aro';
        $groupTable = $gaclApi->dbTablePrefix . 'aro_groups';
        break;
}

switch ($_POST['action']) {
    case 'Delete':
        $gaclApi->debugText('Delete');

        if (count($_POST['delete_group']) > 0) {
            // Always reparent children when deleting a group.
            foreach ($_POST['delete_group'] as $groupId) {
                $gaclApi->debugText('Deleting group_id: ' . $groupId);

                $result = $gaclApi->delGroup($groupId, true, $groupType);
                if ($result == false) {
                    $retry[] = $groupId;
                }
            }

            if (count($retry) > 0) {
                foreach ($retry as $groupId) {
                    $gaclApi->del_group($groupId, true, $groupType);
                }
            }
        }

        // Return page.
        $gaclApi->returnPage($returnPage);
        break;
    case 'Submit':
        $gaclApi->debugText('Submit');

        if (empty($_POST['parent_id'])) {
            $parentId = 0;
        } else {
            $parentId = $_POST['parent_id'];
        }

        // Make sure we're not reparenting to ourself.
        if (! empty($_POST['group_id']) and $parentId == $_POST['group_id']) {
            echo "Sorry, can't reparent to self!<br />\n";
            exit();
        }

        // No parent, assume a "root" group, generate a new parent id.
        if (empty($_POST['group_id'])) {
            $gaclApi->debugText('Insert');

            $insertId = $gaclApi->add_group($_POST['value'], $_POST['name'], $parentId, $groupType);
        } else {
            $gaclApi->debugText('Update');

            $gaclApi->edit_group($_POST['group_id'], $_POST['value'], $_POST['name'], $parentId, $groupType);
        }

        $gaclApi->returnPage($returnPage);
        break;
    default:
        // Grab specific group data
        if (! empty($_GET['group_id'])) {
            $query = 'SELECT id, parent_id, value, name FROM ' . $groupTable . ' WHERE id = ' . (int) $_GET['group_id'];

            list ($id, $parentId, $value, $name) = $db->GetRow($query);
            // showarray($row);
        } else {
            $parentId = $_GET['parent_id'];
            $value = '';
            $name = '';
        }

        $smarty->assign('id', $id);
        $smarty->assign('parent_id', $parentId);
        $smarty->assign('value', $value);
        $smarty->assign('name', $name);

        $smarty->assign('options_groups', $gaclApi->formatGroups($gaclApi->sortGroups($groupType)));
        break;
}

$smarty->assign('group_type', $groupType);
$smarty->assign('return_page', $returnPage);

$smarty->assign('current', 'edit_' . $groupType . '_group');
$smarty->assign('page_title', 'Edit ' . strtoupper($groupType) . ' Group');

$smarty->assign('phpgacl_version', $gaclApi->getVersion());
$smarty->assign('phpgacl_schema_version', $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/edit_group.tpl');
