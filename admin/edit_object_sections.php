<?php
require_once "gacl_admin.inc.php";

//GET takes precedence.
if (isset($_GET['object_type']) && $_GET['object_type'] != '') {
    $objectType = $_GET['object_type'];
} else {
    $objectType = $_POST['object_type'];
}

switch (strtolower(trim($objectType))) {
    case 'aco':
        $objectType          = 'aco';
        $objectSectionsTable = $gaclApi->dbTablePrefix . 'aco_sections';
        break;
    case 'aro':
        $objectType          = 'aro';
        $objectSectionsTable = $gaclApi->dbTablePrefix . 'aro_sections';
        break;
    case 'axo':
        $objectType          = 'axo';
        $objectSectionsTable = $gaclApi->dbTablePrefix . 'axo_sections';
        break;
    case 'acl':
        $objectType          = 'acl';
        $objectSectionsTable = $gaclApi->dbTablePrefix . 'acl_sections';
        break;
    default:
        echo "ERROR: Must select an object type<br>\n";
        exit();
        break;
}

switch ($_POST['action']) {
    case 'Delete':
        if (count($_POST['delete_sections']) > 0) {
            foreach ($_POST['delete_sections'] as $id) {
                $gaclApi->delObjectSection($id, $objectType, true);
            }
        }

        //Return page.
        $gaclApi->returnPage($_POST['return_page']);

        break;
    case 'Submit':
        $gaclApi->debugText("Submit!!");

        //Update sections
        if (!empty($_POST['sections']) && is_array($_POST['sections'])) {
            while (list(,$row) = @each($_POST['sections'])) {
                list($id, $value, $order, $name) = $row;
                $gaclApi->editObjectSection($id, $name, $value, $order, 0, $objectType);
            }
            unset($id);
            unset($value);
            unset($order);
            unset($name);
        }

        //Insert new sections
        while (list(,$row) = @each($_POST['new_sections'])) {
            list($value, $order, $name) = $row;

            if (!empty($value) && !empty($order) && !empty($name)) {
                $objectSectionId = $gaclApi->addObjectSection($name, $value, $order, 0, $objectType);
                $gaclApi->debugText("Section ID: $objectSectionId");
            }
        }
        $gaclApi->debugText("return_page: ". $_POST['return_page']);
        $gaclApi->returnPage($_POST['return_page']);

        break;
    default:
        $query = "SELECT id, value, order_value, name FROM $objectSectionsTable ORDER BY order_value";

        $rs = $db->pageexecute($query, $gaclApi->itemsPerPage, $_GET['page']);
        $rows = $rs->GetRows();

        $sections = [];

        while (list(, $row) = @each($rows)) {
            list($id, $value, $orderValue, $name) = $row;
            $sections[] = [
                'id'    => $id,
                'value' => $value,
                'order' => $orderValue,
                'name'  => $name
            ];
        }

        $newSections = [];

        for ($i = 0; $i < 5; $i++) {
                $newSections[] = [
                    'id' => $i,
                    'value' => null,
                    'order' => null,
                    'name' => null
                ];
        }

        $smarty->assign('sections', $sections);
        $smarty->assign('new_sections', $newSections);

        $smarty->assign("paging_data", $gaclApi->getPagingData($rs));

        break;
}

$smarty->assign('object_type', $objectType);
$smarty->assign('return_page', $_SERVER['REQUEST_URI']);

$smarty->assign('current', 'edit_'. $objectType .'_sections');
$smarty->assign('page_title', 'Edit '. strtoupper($objectType) .' Sections');

$smarty->assign("phpgacl_version", $gaclApi->getVersion());
$smarty->assign("phpgacl_schema_version", $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/edit_object_sections.tpl');
