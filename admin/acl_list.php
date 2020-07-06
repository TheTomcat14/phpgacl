<?php
require_once 'gacl_admin.inc.php';

switch ($_GET['action']) {
    case 'Delete':
        $gaclApi->debugText('Delete!');

        if (is_array($_GET['delete_acl']) and !empty($_GET['delete_acl'])) {
            foreach ($_GET['delete_acl'] as $id) {
                $gaclApi->del_acl($id);
            }
        }

        // Return page.
        $gaclApi->returnPage($_GET['return_page']);
        break;
    case 'Submit':
        $gaclApi->debugText('Submit!!');
        break;
    default:
        /*
         * When the user requests to filter the list, run the filter and get just the matching IDs.
         * Use these IDs to get the entire ACL information in the second query.
         *
         * If we just put the LIKE statements in the second query, it will match the correct ACLs
         * but will only return the matching rows, so it won't show the entire ACL information.
         *
         */
        if (isset($_GET['action']) and $_GET['action'] == 'Filter') {
            $gaclApi->debugText('Filtering...');

            $query = 'SELECT DISTINCT a.id '
            . 'FROM ' . $gaclApi->dbTablePrefix . 'acl a '
            . 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'aco_map ac ON ac.acl_id = a.id '
            . 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'aro_map ar ON ar.acl_id = a.id '
            . 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'axo_map ax ON ax.acl_id = a.id';

            if (isset($_GET['filter_aco_section']) and $_GET['filter_aco_section'] != '-1') {
                $filterQuery[] = 'ac.section_value = ' . $db->qstr(strtolower($_GET['filter_aco_section'])) . ' ';
            }
            if (isset($_GET['filter_aco']) and $_GET['filter_aco'] != '') {
                $query .= 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'aco c ON (c.section_value = ac.section_value AND c.value = ac.value) ';

                $name = $db->qstr(strtolower($_GET['filter_aco']));

                $filterQuery[] = '(LOWER(c.value) LIKE ' . $name . ' OR LOWER(c.name) LIKE ' . $name . ') ';
            }

            if (isset($_GET['filter_aro_section']) and $_GET['filter_aro_section'] != '-1') {
                $filterQuery[] = 'ar.section_value = ' . $db->qstr(strtolower($_GET['filter_aro_section'])) . ' ';
            }
            if (isset($_GET['filter_aro']) and $_GET['filter_aro'] != '') {
                $query .= 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'aro r ON (r.section_value = ar.section_value AND r.value = ar.value) ';

                $name = $db->qstr(strtolower($_GET['filter_aro']));

                $filterQuery[] = '(LOWER(r.value) LIKE ' . $name . ' OR LOWER(r.name) LIKE ' . $name . ') ';
            }
            if (isset($_GET['filter_aro_group']) and $_GET['filter_aro_group'] != '') {
                $query .= 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'aro_groups_map arg ON arg.acl_id = a.id '
                . 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'aro_groups rg ON rg.id = arg.group_id ';

                $filterQuery[] = '(LOWER(rg.name) LIKE ' . $db->qstr(strtolower($_GET['filter_aro_group'])) . ') ';
            }

            if (isset($_GET['filter_axo_section']) and $_GET['filter_axo_section'] != '-1') {
                $filterQuery[] = 'ax.section_value = ' . $db->qstr(strtolower($_GET['filter_axo_section'])) . ' ';
            }
            if (isset($_GET['filter_axo']) and $_GET['filter_axo'] != '') {
                $query .= 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'axo x ON (x.section_value = ax.section_value AND x.value = ax.value) ';

                $name = $db->qstr(strtolower($_GET['filter_axo']));

                $filterQuery[] = '(LOWER(x.value) LIKE ' . $name . ' OR LOWER(x.name) LIKE ' . $name . ') ';
            }
            if (isset($_GET['filter_axo_group']) and $_GET['filter_axo_group'] != '') {
                $query .= 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'axo_groups_map axg ON axg.acl_id = a.id '
                . 'LEFT JOIN ' . $gaclApi->dbTablePrefix . 'axo_groups xg ON xg.id = axg.group_id ';

                $filterQuery[] = '(LOWER(xg.name) LIKE ' . $db->qstr(strtolower($_GET['filter_axo_group'])) . ') ';
            }

            if (isset($_GET['filter_acl_section']) and $_GET['filter_acl_section'] != '-1') {
                $filterQuery[] = 'a.section_value = ' . $db->qstr(strtolower($_GET['filter_acl_section'])) . ' ';
            }
            if (isset($_GET['filter_return_value']) and $_GET['filter_return_value'] != '') {
                $filterQuery[] = '(LOWER(a.return_value) LIKE ' . $db->qstr(strtolower($_GET['filter_return_value'])) . ') ';
            }
            if (isset($_GET['filter_allow']) and $_GET['filter_allow'] != '-1') {
                $filterQuery[] = '(a.allow LIKE ' . $db->qstr($_GET['filter_allow']) . ') ';
            }
            if (isset($_GET['filter_enabled']) and $_GET['filter_enabled'] != '-1') {
                $filterQuery[] = '(a.enabled LIKE ' . $db->qstr($_GET['filter_enabled']) . ') ';
            }

            if (isset($filterQuery) and is_array($filterQuery)) {
                    $query .= 'WHERE ' . implode(' AND ', $filterQuery) . ' ';
            }
        } else {
            $query = 'SELECT a.id FROM ' . $gaclApi->dbTablePrefix . 'acl a ';
        }

        $query .= 'ORDER BY a.id ASC ';

        $aclIds = [];

        $rs = $db->PageExecute($query, $gaclApi->itemsPerPage, $_GET['page']);
        if (is_object($rs)) {
            $smarty->assign('paging_data', $gaclApi->get_paging_data($rs));

            while ($row = $rs->FetchRow()) {
                $aclIds[] = $row[0];
            }

            $rs->Close();
        }

        if (!empty($aclIds)) {
            $aclIdsSql = implode(',', $aclIds);
        } else {
            // This shouldn't match any ACLs, returning 0 rows.
            $aclIdsSql = - 1;
        }

        $acls = [];

        // If the user is searching, and there are no results, don't run the query at all
        if (! ($_GET['action'] == 'Filter' and $aclIdsSql == - 1)) {
            // grab acl details
            $query = 'SELECT a.id,x.name,a.allow,a.enabled,a.return_value,a.note,a.updated_date '
            . 'FROM ' . $gaclApi->dbTablePrefix . 'acl a '
            . 'INNER JOIN ' . $gaclApi->dbTablePrefix . 'acl_sections x ON x.value = a.section_value '
            . 'WHERE a.id IN (' . $aclIdsSql . ') ';
            $rs = $db->Execute($query);

            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    $acls[$row[0]] = [
                        'id'           => $row[0],
                        // 'section_id' => $section_id,
                        'section_name' => $row[1],
                        'allow'        => (bool)$row[2],
                        'enabled'      => (bool)$row[3],
                        'return_value' => $row[4],
                        'note'         => $row[5],
                        'updated_date' => $row[6],
                        'aco'          => [],
                        'aro'          => [],
                        'aro_groups'   => [],
                        'axo'          => [],
                        'axo_groups'   => []
                    ];
                }
            }

            // grab ACO, ARO and AXOs
            foreach (['aco', 'aro', 'axo'] as $type) {
                $query = 'SELECT a.acl_id, o.name, s.name '
                . 'FROM ' . $gaclApi->dbTablePrefix . $type . '_map a '
                . 'INNER JOIN ' . $gaclApi->dbTablePrefix . $type . ' o ON (o.section_value = a.section_value AND o.value = a.value) '
                . 'INNER JOIN ' . $gaclApi->dbTablePrefix . $type . '_sections s ON s.value = a.section_value '
                . 'WHERE a.acl_id IN (' . $aclIdsSql . ') ';
                $rs = $db->Execute($query);

                if (is_object($rs)) {
                    while ($row = $rs->FetchRow()) {
                        list ($aclId, $name, $sectionName) = $row;

                        if (isset($acls[$aclId])) {
                            $acls[$aclId][$type][$sectionName][] = $name;
                        }
                    }
                }
            }

            // grab ARO and AXO groups
            foreach (['aro', 'axo'] as $type) {
                $query = 'SELECT a.acl_id, g.name '
                . 'FROM ' . $gaclApi->dbTablePrefix . $type . '_groups_map a '
                . 'INNER JOIN ' . $gaclApi->dbTablePrefix . $type . '_groups g ON g.id = a.group_id '
                . 'WHERE a.acl_id IN (' . $aclIdsSql . ') ';
                $rs = $db->Execute($query);

                if (is_object($rs)) {
                    while ($row = $rs->FetchRow()) {
                        list ($aclId, $name) = $row;

                        if (isset($acls[$aclId])) {
                            $acls[$aclId][$type . '_groups'][] = $name;
                        }
                    }
                }
            }
        }

        $smarty->assign('acls', $acls);

        $smarty->assign('filter_aco', $_GET['filter_aco']);

        $smarty->assign('filter_aro', $_GET['filter_aro']);
        $smarty->assign('filter_aro_group', $_GET['filter_aro_group']);

        $smarty->assign('filter_axo', $_GET['filter_axo']);
        $smarty->assign('filter_axo_group', $_GET['filter_axo_group']);

        $smarty->assign('filter_return_value', $_GET['filter_return_value']);

        foreach (['aco', 'aro', 'axo', 'acl'] as $type) {
            //
            // Grab all sections for select box
            //
            $options = [
                - 1 => 'Any'
            ];

            $query = 'SELECT value, name '
            . 'FROM ' . $gaclApi->dbTablePrefix . $type . '_sections '
            . 'WHERE hidden = 0 '
            . 'ORDER BY order_value, name';

            $rs = $db->Execute($query);
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    $options[$row[0]] = $row[1];
                }
            }

            $smarty->assign('options_filter_' . $type . '_sections', $options);

            if (! isset($_GET['filter_' . $type . '_section']) or $_GET['filter_' . $type . '_section'] == '') {
                $_GET['filter_' . $type . '_section'] = '-1';
            }

            $smarty->assign('filter_' . $type . '_section', $_GET['filter_' . $type . '_section']);
        }

        $smarty->assign('options_filter_allow', ['-1' => 'Any', 1 => 'Allow', 0 => 'Deny']);
        $smarty->assign('options_filter_enabled', ['-1' => 'Any', 1 => 'Yes', 0 => 'No']);

        if (! isset($_GET['filter_allow']) or $_GET['filter_allow'] == '') {
            $_GET['filter_allow'] = '-1';
        }
        if (! isset($_GET['filter_enabled']) or $_GET['filter_enabled'] == '') {
            $_GET['filter_enabled'] = '-1';
        }

        $smarty->assign('filter_allow', $_GET['filter_allow']);
        $smarty->assign('filter_enabled', $_GET['filter_enabled']);
}

$smarty->assign('action', $_GET['action']);
$smarty->assign('return_page', $_SERVER['PHP_SELF']);

$smarty->assign('current', 'acl_list');
$smarty->assign('page_title', 'ACL List');

$smarty->assign('phpgacl_version', $gaclApi->getVersion());
$smarty->assign('phpgacl_schema_version', $gaclApi->getSchemaVersion());

$smarty->display('phpgacl/acl_list.tpl');
