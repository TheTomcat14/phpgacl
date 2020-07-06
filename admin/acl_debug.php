<?php
require_once 'gacl_admin.inc.php';

switch ($_GET['action']) {
    case 'Submit':
        $gaclApi->debugText('Submit!!');
        // $result = $gaclApi->aclQuery('system', 'email_pw', 'users', '1', null, null, null, null, true);
        $result = $gaclApi->aclQuery(
            $_GET['aco_section_value'],
            $_GET['aco_value'],
            $_GET['aro_section_value'],
            $_GET['aro_value'],
            $_GET['axo_section_value'],
            $_GET['axo_value'],
            $_GET['root_aro_group_id'],
            $_GET['root_axo_group_id'],
            true
        );

        // Grab all relavent columns
        $result['query'] = str_replace(
            'a.id,a.allow,a.return_value',
            'a.id, a.allow, a.return_value, a.note, a.updated_date, '
            . 'ac.section_value as aco_section_value, ac.value as aco_value, '
            . 'ar.section_value as aro_section_value, ar.value as aro_value, '
            . 'ax.section_value as axo_section_value, ax.value as axo_value',
            $result['query']
        );
        $rs = $gaclApi->db->Execute($result['query']);

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                list ($id, $allow, $returnValue, $note, $updatedDate, $acoSectionValue, $acoValue, $aroSectionValue, $aroValue, $axoSectionValue, $axoValue) = $row;

                $acls[] = [
                    'id'           => $id,
                    'allow'        => $allow,
                    'return_value' => $returnValue,
                    'note'         => $note,
                    'updated_date' => date('d-M-y H:m:i', $updatedDate),

                    'aco_section_value' => $acoSectionValue,
                    'aco_value'         => $acoValue,

                    'aro_section_value' => $aroSectionValue,
                    'aro_value'         => $aroValue,

                    'axo_section_value' => $axoSectionValue,
                    'axo_value'         => $axoValue
                ];
            }
        }

        // echo "<br><br>$x ACL_CHECK()'s<br>\n";

        $smarty->assign('acls', $acls);

        $smarty->assign('aco_section_value', $_GET['aco_section_value']);
        $smarty->assign('aco_value', $_GET['aco_value']);
        $smarty->assign('aro_section_value', $_GET['aro_section_value']);
        $smarty->assign('aro_value', $_GET['aro_value']);
        $smarty->assign('axo_section_value', $_GET['axo_section_value']);
        $smarty->assign('axo_value', $_GET['axo_value']);
        $smarty->assign('root_aro_group_id', $_GET['root_aro_group_id']);
        $smarty->assign('root_axo_group_id', $_GET['root_axo_group_id']);
        break;
    default:
        break;
}

$smarty->assign('return_page', $_SERVER['PHP_SELF']);

$smarty->assign('current', 'acl_debug');
$smarty->assign('page_title', 'ACL Debug');

$smarty->assign('phpgacl_version', $gaclApi->getVersion());
$smarty->assign('phpgacl_schema_version', $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/acl_debug.tpl');
