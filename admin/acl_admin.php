<?php
require_once 'gacl_admin.inc.php';

if (!isset($_POST['action'])) {
    $_POST['action'] = false;
}

if (!isset($_GET['action'])) {
    $_GET['action'] = false;
}

switch ($_POST['action']) {
    case 'Delete':
        break;
    case 'Submit':
        $gaclApi->debugText('Submit!!');
        // showarray($_POST['selected_aco']);
        // showarray($_POST['selected_aro']);

        // Parse the form values
        foreach (['aco', 'aro', 'axo'] as $type) {
            $typeArray = 'selected' . ucfirst($type) . 'Array';
            $$typeArray = [];
            if (is_array($_POST['selected_' . $type])) {
                foreach ($_POST['selected_' . $type] as $value) {
                    $splitValue = explode('^', $value);
                    ${$typeArray}[$splitValue[0]][] = $splitValue[1];
                }
            }
            // showarray($$typeArray);
        }

        // Some sanity checks.
        if (empty($selectedAcoArray)) {
            echo 'Must select at least one Access Control Object<br>' . "\n";
            exit();
        }

        if (empty($selectedAroArray) and empty($_POST['aro_groups'])) {
            echo 'Must select at least one Access Request Object or Group<br>' . "\n";
            exit();
        }

        $enabled = $_POST['enabled'];
        if (empty($enabled)) {
            $enabled = 0;
        }

        // function addAcl($aco_array, $aro_array, $aro_group_ids=NULL, $axo_array=NULL, $axo_group_ids=NULL, $allow=1, $enabled=1, $aclId=FALSE ) {
        if (! empty($_POST['acl_id'])) {
            // Update existing ACL
            $aclId = $_POST['acl_id'];
            if ($gaclApi->editAcl($aclId, $selectedAcoArray, $selectedAroArray, $_POST['aro_groups'], $selectedAxoArray, $_POST['axo_groups'], $_POST['allow'], $enabled, $_POST['return_value'], $_POST['note'], $_POST['acl_section']) == false) {
                echo 'ERROR editing ACL, possible conflict or error found...<br>' . "\n";
                exit();
            }
        } else {
            // Insert new ACL.
            if ($gaclApi->addAcl($selectedAcoArray, $selectedAroArray, $_POST['aro_groups'], $selectedAxoArray, $_POST['axo_groups'], $_POST['allow'], $enabled, $_POST['return_value'], $_POST['note'], $_POST['acl_section']) == false) {
                echo 'ERROR adding ACL, possible conflict or error found...<br>' . "\n";
                exit();
            }
        }

        $gaclApi->returnPage($_POST['return_page']);
        break;
    default:
        // showarray($_GET);
        if ($_GET['action'] == 'edit' and !empty($_GET['acl_id'])) {
            $gaclApi->debugText('EDITING ACL');

            // Grab ACL information
            $query = 'SELECT id, section_value, allow, enabled, return_value, note '
            . 'FROM ' . $gaclApi->dbTablePrefix . 'acl '
            . 'WHERE id = ' . $db->qstr($_GET['acl_id']);
            $aclRow = $db->GetRow($query);
            list ($aclId, $aclSectionValue, $allow, $enabled, $returnValue, $note) = $aclRow;

            // Grab selected objects
            foreach (['aco', 'aro', 'axo'] as $type) {
                $typeArray = 'optionsSelected' . ucfirst($type);
                $$typeArray = [];

                $query = 'SELECT a.section_value, a.value, c.name, b.name '
                . 'FROM ' . $gaclApi->dbTablePrefix . $type . '_map a '
                . 'INNER JOIN ' . $gaclApi->dbTablePrefix . $type . ' b ON b.section_value = a.section_value AND b.value = a.value '
                . 'INNER JOIN ' . $gaclApi->dbTablePrefix . $type . '_sections c ON c.value = a.section_value '
                . 'WHERE a.acl_id = ' . $db->qstr($aclId);
                $rs = $db->Execute($query);

                if (is_object($rs)) {
                    while ($row = $rs->FetchRow()) {
                        list ($sectionValue, $value, $section, $obj) = $row;
                        $gaclApi->debugText("Section Value: $sectionValue Value: $value Section: $section ACO: $aco");
                        ${$typeArray}[$sectionValue . '^' . $value] = $section . ' > ' . $obj;
                    }
                }
                // showarray($$typeArray);
            }

            // Grab selected groups.
            foreach (['aro', 'axo'] as $type) {
                $typeArray = 'selected' . ucfirst($type) . 'Groups';

                $query = 'SELECT group_id '
                . 'FROM ' . $gaclApi->dbTablePrefix . $type . '_groups_map '
                . 'WHERE acl_id = ' . $db->qstr($aclId);
                $$typeArray = $db->GetCol($query);
                // showarray($$typeArray);
            }

            $showAxo = (! empty($selectedAxoGroups) or ! empty($optionsSelectedAxo));
        } else {
            $gaclApi->debugText('NOT EDITING ACL');
            $allow = 1;
            $enabled = 1;
            $aclSectionValue = 'user';

            $showAxo = isset($_COOKIE['show_axo']) && $_COOKIE['show_axo'] == '1';
        }

        // Grab sections for select boxes
        foreach (['acl', 'aco', 'aro', 'axo'] as $type) {
            $typeArray = 'options' . ucfirst($type) . 'Sections';
            $$typeArray = [];

            $query = 'SELECT value, name '
            . 'FROM ' . $gaclApi->dbTablePrefix . $type . '_sections '
            . 'WHERE hidden = 0 '
            . 'ORDER BY order_value, name';
            $rs = $db->Execute($query);

            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    ${$typeArray}[$row[0]] = $row[1];
                }
            }

            ${$type . 'SectionId'} = reset($$typeArray);
        }

        // Init the main js array
        $jsArray = 'var options = new Array();' . "\n";

        // Grab objects for select boxes
        foreach (['aco', 'aro', 'axo'] as $type) {
            // Init the main object js array.
            $jsArray .= 'options[\'' . $type . '\'] = new Array();' . "\n";

            unset($tmpSectionValue);

            $query = 'SELECT section_value, value,name '
            . 'FROM ' . $gaclApi->dbTablePrefix . $type . ' '
            . 'WHERE hidden = 0 '
            . 'ORDER BY section_value, order_value, name';
            $rs = $db->SelectLimit($query, $gaclApi->maxSelectBoxItems);

            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    $sectionValue = addslashes($row[0]);
                    $value        = addslashes($row[1]);
                    $name         = addslashes($row[2]);

                    // Prepare javascript code for dynamic select box.
                    // Init the javascript sub-array.
                    if (! isset($tmpSectionValue) or $sectionValue != $tmpSectionValue) {
                        $i = 0;
                        $jsArray .= 'options[\'' . $type . '\'][\'' . $sectionValue . '\'] = new Array();' . "\n";
                        $tmpSectionValue = $sectionValue;
                    }

                    // Add each select option for the section
                    $jsArray .= 'options[\'' . $type . '\'][\'' . $sectionValue . '\'][' . $i . '] = new Array(\'' . $value . '\', \'' . $name . "');\n";
                    $i ++;
                }
            }
        }

        // echo "Section ID: $section_id<br>\n";
        // echo "Section Value: ". $aclSectionValue ."<br>\n";

        $smarty->assign('options_acl_sections', $optionsAclSections);
        $smarty->assign('acl_section_value', $aclSectionValue);

        $smarty->assign('options_axo_sections', $optionsAxoSections);
        $smarty->assign('axo_section_value', $axoSectionValue);

        $smarty->assign('options_aro_sections', $optionsAroSections);
        $smarty->assign('aro_section_value', $aroSectionValue);

        $smarty->assign('options_aco_sections', $optionsAcoSections);
        $smarty->assign('aco_section_value', $acoSectionValue);

        $smarty->assign('js_array', $jsArray);

        $smarty->assign('js_aco_array_name', 'aco');
        $smarty->assign('js_aro_array_name', 'aro');
        $smarty->assign('js_axo_array_name', 'axo');

        // Grab formatted ARO Groups for select box
        $smarty->assign('options_aro_groups', $gaclApi->formatGroups($gaclApi->sortGroups('ARO')));
        $smarty->assign('selected_aro_groups', $selectedAroGroups);

        // Grab formatted AXO Groups for select box
        $smarty->assign('options_axo_groups', $gaclApi->formatGroups($gaclApi->sortGroups('AXO')));
        $smarty->assign('selected_axo_groups', $selectedAxoGroups);

        $smarty->assign('allow', $allow);
        $smarty->assign('enabled', $enabled);
        $smarty->assign('return_value', $returnValue);
        $smarty->assign('note', $note);

        if (isset($optionsSelectedAco)) {
            $smarty->assign('options_selected_aco', $optionsSelectedAco);
        }
        $smarty->assign('selected_aco', @array_keys($optionsSelectedAco));

        if (isset($optionsSelectedAro)) {
            $smarty->assign('options_selected_aro', $optionsSelectedAro);
        }
        $smarty->assign('selected_aro', @array_keys($optionsSelectedAro));

        if (isset($optionsSelectedAxo)) {
            $smarty->assign('options_selected_axo', $optionsSelectedAxo);
        }
        $selectedAxo = @array_keys($optionsSelectedAxo);

        $smarty->assign('selected_axo', $selectedAxo);

        // Show AXO layer if AXO's are selected.
        $smarty->assign('show_axo', $showAxo);

        if (isset($_GET['acl_id'])) {
            $smarty->assign('acl_id', $_GET['acl_id']);
        }

        break;
}

// $smarty->assign('return_page', urlencode($_SERVER[REQUEST_URI]) );
if (isset($_GET['return_page'])) {
    $smarty->assign('return_page', $_GET['return_page']);
}
if (isset($_GET['action'])) {
    $smarty->assign('action', $_GET['action']);
}

$smarty->assign('current', 'acl_admin');
$smarty->assign('page_title', 'ACL Admin');

$smarty->assign('phpgacl_version', $gaclApi->getVersion());
$smarty->assign('phpgacl_schema_version', $gaclApi->getSchemaVersion());
$smarty->display('phpgacl/acl_admin.tpl');
