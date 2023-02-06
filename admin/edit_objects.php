<?php
require_once "gacl_admin.inc.php";

// GET takes precedence.
if ($_GET['object_type'] != '') {
    $objectType = $_GET['object_type'];
} else {
    $objectType = $_POST['object_type'];
}

switch (strtolower(trim($objectType))) {
    case 'aco':
        $objectType          = 'aco';
        $objectTable         = $gaclApi->dbTablePrefix . 'aco';
        $objectSectionsTable = $gaclApi->dbTablePrefix . 'aco_sections';
        break;
    case 'aro':
        $objectType          = 'aro';
        $objectTable         = $gaclApi->dbTablePrefix . 'aro';
        $objectSectionsTable = $gaclApi->dbTablePrefix . 'aro_sections';
        break;
    case 'axo':
        $objectType          = 'axo';
        $objectTable         = $gaclApi->dbTablePrefix . 'axo';
        $objectSectionsTable = $gaclApi->dbTablePrefix . 'axo_sections';
        break;
    default:
        echo "ERROR: Must select an object type<br>\n";
        exit();
        break;
}

switch ($_POST['action']) {
    case 'Delete':
        if (count($_POST['delete_object']) > 0) {
            foreach ($_POST['delete_object'] as $id) {
                $gaclApi->delObject($id, $objectType, true);
            }
        }

        // Return page.
        $gaclApi->returnPage($_POST['return_page']);

        break;
    case 'Submit':
        $gaclApi->debugText("Submit!!");

        // Update objects
        if (!empty($_POST['objects']) && is_array($_POST['objects'])) {
            while (list (, $row) = @each($_POST['objects'])) {
                list ($id, $value, $order, $name) = $row;
                $gaclApi->editObject($id, $_POST['section_value'], $name, $value, $order, 0, $objectType);
            }
            unset($id);
            unset($sectionValue);
            unset($value);
            unset($order);
            unset($name);
        }

        // Insert new sections
        while (list (, $row) = @each($_POST['new_objects'])) {
            list ($value, $order, $name) = $row;

            if (!empty($value) and !empty($name)) {
                $objectId = $gaclApi->addObject($_POST['section_value'], $name, $value, $order, 0, $objectType);
            }
        }
        $gaclApi->debugText("return_page: " . $_POST['return_page']);
        $gaclApi->returnPage($_POST['return_page']);

        break;
    default:
        // Grab section name
        $query = "SELECT name FROM $objectSectionsTable WHERE value = '" . $_GET['section_value'] . "'";
        $sectionName = $db->GetOne($query);

        $query = "SELECT id, section_value, value, order_value, name "
        . "FROM $objectTable "
        . "WHERE section_value = '" . $_GET['section_value'] . "' "
        . "ORDER BY order_value";
        $rs = $db->pageexecute($query, $gaclApi->itemsPerPage, $_GET['page']);
        $rows = $rs->GetRows();

        while (list (, $row) = @each($rows)) {
            list ($id, $sectionValue, $value, $orderValue, $name) = $row;

            $objects[] = [
                'id'            => $id,
                'section_value' => $sectionValue,
                'value'         => $value,
                'order'         => $orderValue,
                'name'          => $name
            ];
        }

        for ($i = 0; $i < 5; $i ++) {
            $newObjects[] = [
                'id'            => $i,
                'section_value' => null,
                'value'         => null,
                'order'         => null,
                'name'          => null
            ];
        }

        $smarty->assign('objects', $objects);
        $smarty->assign('new_objects', $newObjects);

        $smarty->assign("paging_data", $gaclApi->getPagingData($rs));

        break;
}

$smarty->assign('section_value', stripslashes($_GET['section_value']));
$smarty->assign('section_name', $sectionName);
$smarty->assign('object_type', $objectType);
$smarty->assign('return_page', $_SERVER['REQUEST_URI']);

$smarty->assign('current', 'edit_' . $objectType . 's');
$smarty->assign('page_title', 'Edit ' . strtoupper($objectType) . ' Objects');

$smarty->assign("phpgacl_version", $gaclApi->getVersion());
$smarty->assign("phpgacl_schema_version", $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/edit_objects.tpl');
