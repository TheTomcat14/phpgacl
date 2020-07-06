<?php
require_once 'gacl_admin.inc.php';

switch (strtolower($_GET['object_type'])) {
    case 'axo':
        $objectType = 'axo';
        break;
    default:
        $objectType = 'aro';
}

switch ($_GET['action']) {
    case 'Search':
        $gaclApi->debugText('Submit!!');

        // Function to pass array_walk to trim all entries in an array.
        function array_walk_trim(&$arrayField)
        {
            $arrayField = $db->qstr(strtolower(trim($arrayField)));
        }

        $valueSearchStr = trim($_GET['value_search_str']);
        $nameSearchStr  = trim($_GET['name_search_str']);

        $explodedValueSearchStr = explode("\n", $valueSearchStr);
        $explodedNameSearchStr  = explode("\n", $nameSearchStr);

        if (count($explodedValueSearchStr) > 1 or count($explodedNameSearchStr) > 1) {
            // Given a list, lets try to match all lines in it.
            array_walk($explodedValueSearchStr, 'array_walk_trim');
            array_walk($explodedNameSearchStr, 'array_walk_trim');
        } else {
            if ($valueSearchStr != '') {
                $valueSearchStr .= '%';
            }

            if ($nameSearchStr != '') {
                $nameSearchStr .= '%';
            }
        }

        // Search
        $query = 'SELECT section_value,value, name '
        . 'FROM ' . $gaclApi->dbTablePrefix . $objectType . ' '
        . 'WHERE section_value = ' . $db->qstr($_GET['section_value']) . ' '
        . 'AND ( ';

        if (count($explodedValueSearchStr) > 1) {
            $query .= 'LOWER(value) IN (' . implode(',', $explodedValueSearchStr) . ') ';
        } else {
            $query .= 'LOWER(value) LIKE ' . $db->qstr($valueSearchStr) . ' ';
        }

        $query .= ' OR ';

        if (count($explodedNameSearchStr) > 1) {
            $query .= 'LOWER(name) IN (' . implode(',', $explodedNameSearchStr) . ') ';
        } else {
            $query .= 'LOWER(name) LIKE ' . $db->qstr($nameSearchStr) . ' ';
        }

        $query .= ') ORDER BY section_value, order_value, name';
        $rs = $db->SelectLimit($query, $gaclApi->maxSearchReturnItems);

        $optionsObjects = [];
        $totalRows = 0;

        if (is_object($rs)) {
            $totalRows = $rs->RecordCount();

            while ($row = $rs->FetchRow()) {
                list ($sectionValue, $value, $name) = $row;
                $optionsObjects[$value] = $name;
            }
        }

        $smarty->assign('options_objects', $optionsObjects);
        $smarty->assign('total_rows', $totalRows);

        $smarty->assign('value_search_str', $_GET['value_search_str']);
        $smarty->assign('name_search_str', $_GET['name_search_str']);

    // break;
    default:
        $smarty->assign('src_form', $_GET['src_form']);
        $smarty->assign('section_value', $_GET['section_value']);
        $smarty->assign('section_value_name', ucfirst($_GET['section_value']));
        $smarty->assign('object_type', $objectType);
        $smarty->assign('object_type_name', strtoupper($objectType));

        break;
}

$smarty->assign('current', $objectType . '_search');
$smarty->assign('page_title', strtoupper($objectType) . ' Search');

$smarty->assign('phpgacl_version', $gaclApi->getVersion());
$smarty->assign('phpgacl_schema_version', $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/object_search.tpl');
