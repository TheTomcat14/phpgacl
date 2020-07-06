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
        $groupType          = 'axo';
        $table              = $gaclApi->dbTablePrefix . 'axo';
        $groupTable         = $gaclApi->dbTablePrefix . 'axo_groups';
        $groupSectionsTable = $gaclApi->dbTablePrefix . 'axo_sections';
        $groupMapTable      = $gaclApi->dbTablePrefix . 'groups_axo_map';
        $objectType         = 'Access eXtension Object';
        break;
    default:
        $groupType          = 'aro';
        $table              = $gaclApi->dbTablePrefix . 'aro';
        $groupTable         = $gaclApi->dbTablePrefix . 'aro_groups';
        $groupSectionsTable = $gaclApi->dbTablePrefix . 'aro_sections';
        $groupMapTable      = $gaclApi->dbTablePrefix . 'groups_aro_map';
        $objectType         = 'Access Request Object';
        break;
}

switch ($_POST['action']) {
    case 'Remove':
        $gaclApi->debugText('Delete!!');

        // Parse the form values
        // foreach ($_POST['delete_assigned_aro'] as $aroValue) {
        while (list (, $objectValue) = @each($_POST['delete_assigned_object'])) {
            $splitObjectValue = explode('^', $objectValue);
            $selectedObjectArray[$splitObjectValue[0]][] = $splitObjectValue[1];
        }

        // Insert Object -> GROUP mappings
        while (list ($objectSectionValue, $objectArray) = @each($selectedObjectArray)) {
            $gaclApi->debugText('Assign: Object ID: ' . $objectSectionValue . ' to Group: ' . $_POST['group_id']);

            foreach ($objectArray as $objectValue) {
                $gaclApi->del_group_object($_POST['group_id'], $objectSectionValue, $objectValue, $groupType);
            }
        }

        // Return page.
        $gaclApi->returnPage($_SERVER['PHP_SELF'] . '?group_type=' . $_POST['group_type'] . '&group_id=' . $_POST['group_id']);

        break;
    case 'Submit':
        $gaclApi->debugText('Submit!!');

        // showarray($_POST['selected_'.$_POST['group_type']]);
        // Parse the form values
        // foreach ($_POST['selected_aro'] as $aroValue) {
        while (list (, $objectValue) = @each($_POST['selected_' . $_POST['group_type']])) {
            $splitObjectValue = explode('^', $objectValue);
            $selectedObjectArray[$splitObjectValue[0]][] = $splitObjectValue[1];
        }

        // Insert ARO -> GROUP mappings
        while (list ($objectSectionValue, $objectArray) = @each($selectedObjectArray)) {
            $gaclApi->debugText('Assign: Object ID: ' . $objectSectionValue . ' to Group: ' . $_POST['group_id']);

            foreach ($objectArray as $objectValue) {
                $gaclApi->add_group_object($_POST['group_id'], $objectSectionValue, $objectValue, $groupType);
            }
        }

        $gaclApi->returnPage($_SERVER['PHP_SELF'] . '?group_type=' . $_POST['group_type'] . '&group_id=' . $_POST['group_id']);

        break;
    default:
        //
        // Grab all sections for select box
        //
        $query = 'SELECT value, name FROM ' . $groupSectionsTable . ' ORDER BY order_value, name';
        $rs = $db->Execute($query);

        $optionsSections = [];

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                $optionsSections[$row[0]] = $row[1];
            }
        }

        // showarray($optionsSections);
        $smarty->assign('options_sections', $optionsSections);
        $smarty->assign('section_value', reset($optionsSections));

        //
        // Grab all objects for select box
        //
        $query = 'SELECT section_value, value, name FROM ' . $table . ' ORDER BY section_value, order_value, name';
        $rs = $db->SelectLimit($query, $gaclApi->maxSelectBoxItems);

        $jsArrayName = 'options[\'' . $groupType . '\']';
        // Init the main aro js array.
        $jsArray = 'var options = new Array();' . "\n";
        $jsArray .= $jsArrayName . ' = new Array();' . "\n";

        unset($tmpSectionValue);

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                // list($sectionValue, $value, $name) = $row;

                $sectionValue = addslashes($row[0]);
                $value = addslashes($row[1]);
                $name = addslashes($row[2]);

                // Prepare javascript code for dynamic select box.
                // Init the javascript sub-array.
                if (! isset($tmpSectionValue) or $sectionValue != $tmpSectionValue) {
                    $i = 0;
                    $jsArray .= $jsArrayName . '[\'' . $sectionValue . '\'] = new Array();' . "\n";
                }

                // Add each select option for the section
                $jsArray .= $jsArrayName . '[\'' . $sectionValue . '\'][' . $i . '] = new Array(\'' . $value . '\', \'' . $name . "');\n";

                $tmpSectionValue = $sectionValue;
                $i ++;
            }
        }

        $smarty->assign('js_array', $jsArray);
        $smarty->assign('js_array_name', $groupType);

        // Grab list of assigned Objects
        $query = 'SELECT b.section_value, b.value, b.name AS b_name, c.name AS c_name '
        . 'FROM ' . $groupMapTable . ' a '
        . 'INNER JOIN ' . $table . ' b ON b.id = a.' . $groupType . '_id '
        . 'INNER JOIN ' . $groupSectionsTable . ' c ON c.value = b.section_value '
        . 'WHERE a.group_id = ' . $db->qstr($_GET['group_id']) . ' '
        . 'ORDER BY c.name, b.name';
        // $rs = $db->Execute($query);
        $rs = $db->PageExecute($query, $gaclApi->itemsPerPage, $_GET['page']);

        $objectRows = [];

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                list ($sectionValue, $value, $name, $section) = $row;

                $objectRows[] = [
                    'section_value' => $row[0],
                    'value'         => $row[1],
                    'name'          => $row[2],
                    'section'       => $row[3]
                ];
            }

            $smarty->assign('total_objects', $rs->_maxRecordCount);

            $smarty->assign('paging_data', $gaclApi->get_paging_data($rs));
        }
        // showarray($aros);

        $smarty->assign('rows', $objectRows);

        // Get group name.
        $groupData = $gaclApi->get_group_data($_GET['group_id'], $groupType);
        $smarty->assign('group_name', $groupData[2]);

        $smarty->assign('group_id', $_GET['group_id']);

        break;
}

$smarty->assign('group_type', $groupType);
$smarty->assign('object_type', $objectType);
$smarty->assign('return_page', $_SERVER['REQUEST_URI']);

$smarty->assign('current', 'assign_group_' . $groupType);
$smarty->assign('page_title', 'Assign Group - ' . strtoupper($groupType));

$smarty->assign('phpgacl_version', $gaclApi->getVersion());
$smarty->assign('phpgacl_schema_version', $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/assign_group.tpl');
