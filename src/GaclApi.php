<?php

/**
 * phpGACL - Generic Access Control List
 * Copyright (C) 2002,2003 Mike Benoit
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions, help, comments, discussion, etc., please join the
 * phpGACL mailing list. http://sourceforge.net/mail/?group_id=57103
 *
 * You may contact the author of phpGACL by e-mail at:
 * ipso@snappymail.ca
 *
 * The latest version of phpGACL can be obtained from:
 * http://phpgacl.sourceforge.net/
 *
 * @package phpGACL
 *
 */

/*
 *
 * For examples, see example.php or the Administration interface,
 * as it makes use of nearly every API Call.
 *
 */

namespace Higis\PhpGacl;

/**
 * gacl_api Extended API Class
 *
 * Class gacl_api should be used for applications that must interface directly with
 * phpGACL's data structures, objects, and rules.
 *
 * @package phpGACL
 * @author Mike Benoit <ipso@snappymail.ca>
 *
 */
class GaclApi extends Gacl
{

    const FORMAT_TYPE_TEXT  = 'TEXT';
    const FORMAT_TYPE_HTML  = 'HTML';
    const FORMAT_TYPE_ARRAY = 'ARRAY';

    const FETCH_RECURSE    = 'RECURSE';
    const FETCH_NO_RECURSE = 'NO_RECURSE';

    /*
     *
     * Misc helper functions.
     *
     */

    /**
     * Dump all contents of an array in HTML (kinda)
     *
     * @param array $array
     * @return void
     */
    public function showarray($array)
    {
        echo "<br><pre>\n";
        var_dump($array);
        echo "</pre><br>\n";
    }

    /**
     * Recursively counts elements in an array and sub-arrays.
     *
     * This is different from count($arg, COUNT_RECURSIVE) in PHP >= 4.2.0, which includes sub-arrays in the count.
     *
     * @param array $arg Array to count
     * @return integer The returned count is a count of all scalar elements found.
     */
    public function countAll($arg = null)
    {
        switch (true) {
            case is_scalar($arg):
            case is_object($arg):
                // single object
                return 1;
            case is_array($arg):
                // call recursively for all elements of $arg
                $count = 0;
                foreach ($arg as $val) {
                    $count += $this->countAll($val);
                }
                return $count;
        }
        return false;
    }

    /**
     * Grabs phpGACL version from the database.
     *
     * @return string Version of phpGACL
     */
    public function getVersion()
    {
        $query = "select value from " . $this->dbTablePrefix . "phpgacl where name = 'version'";
        $version = $this->db->GetOne($query);

        return $version;
    }

    /**
     * Grabs phpGACL schema version from the database.
     *
     * @return string Schema Version
     */
    public function getSchemaVersion()
    {
        $query = "select value from " . $this->dbTablePrefix . "phpgacl where name = 'schema_version'";
        $version = $this->db->GetOne($query);

        return $version;
    }

    /*
     *
     * ACL
     *
     */

    /**
     * Add's an ACL but checks to see if it can consolidate it with another one first.
     *
     * This ONLY works with ACO's and ARO's. Groups, and AXO are excluded.
     * As well this function is designed for handling ACLs with return values,
     * and consolidating on the return_value, in hopes of keeping the ACL count to a minimum.
     *
     * A return value of false must _always_ be handled outside this function.
     * As this function will remove AROs from ACLs and return false, in most cases
     * you will need to a create a completely new ACL on a false return.
     *
     * @param string $acoSectionValue ACO Section Value
     * @param string $acoValue ACO Value
     * @param string $aroSectionValue ARO Section Value
     * @param string $aroValue ARO Value
     * @param string $returnValue Return Value of ACL
     * @return boolean Special boolean return value. See note.
     */
    public function consolidatedEditAcl($acoSectionValue, $acoValue, $aroSectionValue, $aroValue, $returnValue)
    {
        $this->debugText("consolidated_editAcl(): ACO Section Value: $acoSectionValue ACO Value: $acoValue ARO Section Value: $aroSectionValue ARO Value: $aroValue Return Value: $returnValue");

        $aclIds = [];

        if (empty($acoSectionValue)) {
            $this->debugText("consolidated_editAcl(): ACO Section Value ($acoSectionValue) is empty, this is required!");
            return false;
        }

        if (empty($acoValue)) {
            $this->debugText("consolidated_editAcl(): ACO Value ($acoValue) is empty, this is required!");
            return false;
        }

        if (empty($aroSectionValue)) {
            $this->debugText("consolidated_editAcl(): ARO Section Value ($aroSectionValue) is empty, this is required!");
            return false;
        }

        if (empty($aroValue)) {
            $this->debugText("consolidated_editAcl(): ARO Value ($aroValue) is empty, this is required!");
            return false;
        }

        if (empty($returnValue)) {
            $this->debugText("consolidated_editAcl(): Return Value ($returnValue) is empty, this is required!");
            return false;
        }

        // See if a current ACL exists with the current objects, excluding return value
        $currentAclIds = $this->searchAcl(
            $acoSectionValue,
            $acoValue,
            $aroSectionValue,
            $aroValue,
            false,
            false,
            false,
            false,
            false
        );
        // showarray($currentAclIds);

        if (is_array($currentAclIds)) {
            $this->debugText("add_consolidated_acl(): Found current ACL_IDs, counting ACOs");

            foreach ($currentAclIds as $currentAclId) {
                // Check to make sure these ACLs only have a single ACO mapped to them.
                $currentAclArray = $this->getAcl($currentAclId);

                // showarray($currentAclArray);
                $this->debugText("add_consolidated_acl(): Current Count: " . $this->countAll($currentAclArray['aco']) . "");

                if ($this->countAll($currentAclArray['aco']) == 1) {
                    $this->debugText("add_consolidated_acl(): ACL ID: $currentAclId has 1 ACO.");

                    // Test to see if the return values match, if they do, no need removing or appending ARO. Just return true.
                    if ($currentAclArray['return_value'] == $returnValue) {
                        $this->debugText("add_consolidated_acl(): ACL ID: $currentAclId has 1 ACO, and the same return value. No need to modify.");
                        return true;
                    }

                    $aclIds[] = $currentAclId;
                }
            }
        }

        // showarray($aclIds);
        $aclIdsCount = count($aclIds);

        // If acl_id's turns up more then one ACL, lets remove the ARO from all of them in hopes to
        // eliminate any conflicts.
        if (is_array($aclIds) and $aclIdsCount > 0) {
            $this->debugText("add_consolidated_acl(): Removing specified ARO from existing ACL.");

            foreach ($aclIds as $aclId) {
                // Remove ARO from current ACLs, so we don't create conflicting ACLs later on.
                if (!$this->shift_acl($aclId, [
                    $aroSectionValue => [
                        $aroValue
                    ]
                ])) {
                    $this->debugText("add_consolidated_acl(): Error removing specified ARO from ACL ID: $aclId");
                    return false;
                }
            }
        } else {
            $this->debugText("add_consolidated_acl(): Didn't find any current ACLs with a single ACO. ");
        }
        // unset($aclIds);
        $aclIds = [];
        unset($aclIdsCount);

        // At this point there should be no conflicting ACLs, searching for an existing ACL with the new values.
        $newAclIds = $this->searchAcl($acoSectionValue, $acoValue, false, false, null, null, null, null, $returnValue);
        // $newAclCount = count($newAclIds);
        // showarray($newAclIds);

        if (is_array($newAclIds)) {
            $this->debugText("add_consolidated_acl(): Found new ACL_IDs, counting ACOs");

            foreach ($newAclIds as $newAclId) {
                // Check to make sure these ACLs only have a single ACO mapped to them.
                $newAclArray = $this->getAcl($newAclId);
                // showarray($newAclArray);
                $this->debugText("add_consolidated_acl(): New Count: " . $this->countAll($newAclArray['aco']) . "");
                if ($this->countAll($newAclArray['aco']) == 1) {
                    $this->debugText("add_consolidated_acl(): ACL ID: $newAclId has 1 ACO, append should be able to take place.");
                    $aclIds[] = $newAclId;
                }
            }
        }

        // showarray($aclIds);
        $aclIdsCount = count($aclIds);

        if (is_array($aclIds) and $aclIdsCount == 1) {
            $this->debugText("add_consolidated_acl(): Appending specified ARO to existing ACL.");

            $aclId = $aclIds[0];

            if (!$this->appendAcl($aclId, [
                $aroSectionValue => [
                    $aroValue
                ]
            ])) {
                $this->debugText("add_consolidated_acl(): Error appending specified ARO to ACL ID: $aclId");
                return false;
            }

            $this->debugText("add_consolidated_acl(): Hot damn, ACL consolidated!");
            return true;
        } elseif ($aclIdsCount > 1) {
            $this->debugText("add_consolidated_acl(): Found more then one ACL with a single ACO. Possible conflicting ACLs.");
            return false;
        } elseif ($aclIdsCount == 0) {
            $this->debugText("add_consolidated_acl(): No existing ACLs found, create a new one.");

            if (!$this->addAcl([
                $acoSectionValue => [
                    $acoValue
                ]
            ], [
                $aroSectionValue => [
                    $aroValue
                ]
            ], null, null, null, true, true, $returnValue, null)) {
                $this->debugText("add_consolidated_acl(): Error adding new ACL for ACO Section: $acoSectionValue ACO Value: $acoValue Return Value: $returnValue");
                return false;
            }

            $this->debugText("add_consolidated_acl(): ADD_ACL() successfull, returning True.");
            return true;
        }

        $this->debugText("add_consolidated_acl(): Returning false.");
        return false;
    }

    /**
     * Searches for ACL's with specified objects mapped to them.
     *
     * NULL values are included in the search, if you want to ignore for instance aro_groups use false instead of NULL.
     *
     * @param string $acoSectionValue ACO Section Value
     * @param string $acoValue ACO Value
     * @param string $aroSectionValue ARO Section Value
     * @param string $aroValue ARO Value
     * @param string $aroGroupName ARO Group Name
     * @param string $axoSectionValue AXO Section Value
     * @param string $axoValue AXO Value
     * @param string $axoGroupName AXO Group Name
     * @param string $returnValue Return Value
     * @return array containing ACL IDs if search is successful
     */
    public function searchAcl(
        $acoSectionValue = null,
        $acoValue = null,
        $aroSectionValue = null,
        $aroValue = null,
        $aroGroupName = null,
        $axoSectionValue = null,
        $axoValue = null,
        $axoGroupName = null,
        $returnValue = null
    ) {
        $this->debugText("searchAcl(): aco_section_value: $acoSectionValue aco_value: $acoValue, aro_section_value: $aroSectionValue, aro_value: $aroValue, aro_group_name: $aroGroupName, axo_section_value: $axoSectionValue, axo_value: $axoValue, axo_group_name: $axoGroupName, return_value: $returnValue");

        $query = '
        SELECT		a.id
        FROM		' . $this->dbTablePrefix . 'acl a';

        $whereQuery = [];

        // ACO
        if ($acoSectionValue !== false and $acoValue !== false) {
            $query .= '
        LEFT JOIN	' . $this->dbTablePrefix . 'aco_map ac ON a.id=ac.acl_id';

            if ($acoSectionValue == null and $acoValue == null) {
                $whereQuery[] = '(ac.section_value IS NULL AND ac.value IS NULL)';
            } else {
                $whereQuery[] = '(ac.section_value=' . $this->db->quote($acoSectionValue) . ' AND ac.value=' . $this->db->quote($acoValue) . ')';
            }
        }

        // ARO
        if ($aroSectionValue !== false and $aroValue !== false) {
            $query .= '
        LEFT JOIN	' . $this->dbTablePrefix . 'aro_map ar ON a.id=ar.acl_id';

            if ($aroSectionValue == null and $aroValue == null) {
                $whereQuery[] = '(ar.section_value IS NULL AND ar.value IS NULL)';
            } else {
                $whereQuery[] = '(ar.section_value=' . $this->db->quote($aroSectionValue) . ' AND ar.value=' . $this->db->quote($aroValue) . ')';
            }
        }

        // AXO
        if ($axoSectionValue !== false and $axoValue !== false) {
            $query .= '
        LEFT JOIN	' . $this->dbTablePrefix . 'axo_map ax ON a.id=ax.acl_id';

            if ($axoSectionValue == null and $axoValue == null) {
                $whereQuery[] = '(ax.section_value IS NULL AND ax.value IS NULL)';
            } else {
                $whereQuery[] = '(ax.section_value=' . $this->db->quote($axoSectionValue) . ' AND ax.value=' . $this->db->quote($axoValue) . ')';
            }
        }

        // ARO Group
        if ($aroGroupName !== false) {
            $query .= '
        LEFT JOIN	' . $this->dbTablePrefix . 'aro_groups_map arg ON a.id=arg.acl_id
        LEFT JOIN	' . $this->dbTablePrefix . 'aro_groups rg ON arg.group_id=rg.id';

            if ($aroGroupName == null) {
                $whereQuery[] = '(rg.name IS NULL)';
            } else {
                $whereQuery[] = '(rg.name=' . $this->db->quote($aroGroupName) . ')';
            }
        }

        // AXO Group
        if ($axoGroupName !== false) {
            $query .= '
        LEFT JOIN	' . $this->dbTablePrefix . 'axo_groups_map axg ON a.id=axg.acl_id
        LEFT JOIN	' . $this->dbTablePrefix . 'axo_groups xg ON axg.group_id=xg.id';

            if ($axoGroupName == null) {
                $whereQuery[] = '(xg.name IS NULL)';
            } else {
                $whereQuery[] = '(xg.name=' . $this->db->quote($axoGroupName) . ')';
            }
        }
        if ($returnValue != false) {
            if ($returnValue == null) {
                $whereQuery[] = '(a.return_value IS NULL)';
            } else {
                $whereQuery[] = '(a.return_value=' . $this->db->quote($returnValue) . ')';
            }
        }

        if (count($whereQuery) > 0) {
            $query .= '
        WHERE		' . implode(' AND ', $whereQuery);
        }

        return $this->db->GetCol($query);
    }

    /**
     * Appends objects on to a specific ACL.
     *
     * @param integer $aclId ACL ID #
     * @param array   $aroArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $aroGroupIds Array of Group IDs
     * @param array   $axoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $axoGroupIds Array of Group IDs
     * @param array   $acoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @return boolean true if successful, false otherwise.
     */
    public function appendAcl(
        $aclId,
        $aroArray = null,
        $aroGroupIds = null,
        $axoArray = null,
        $axoGroupIds = null,
        $acoArray = null
    ) {
        $this->debugText("appendAcl(): ACL_ID: $aclId");

        $update = 0;

        if (empty($aclId)) {
            $this->debugText("appendAcl(): No ACL_ID specified! ACL_ID: $aclId");
            return false;
        }

        // Grab ACL data.
        $aclArray = $this->getAcl($aclId);

        // Append each object type seperately.
        if (is_array($aroArray) and count($aroArray) > 0) {
            $this->debugText("appendAcl(): Appending ARO's");

            while (list ($aroSectionValue, $aroValueArray) = @each($aroArray)) {
                foreach ($aroValueArray as $aroValue) {
                    if (count($aclArray['aro'][$aroSectionValue]) != 0) {
                        if (!in_array($aroValue, $aclArray['aro'][$aroSectionValue])) {
                            $this->debugText("appendAcl(): ARO Section Value: $aroSectionValue ARO VALUE: $aroValue");
                            $aclArray['aro'][$aroSectionValue][] = $aroValue;
                            $update = 1;
                        } else {
                            $this->debugText("appendAcl(): Duplicate ARO, ignoring... ");
                        }
                    } else { // Array is empty so add this aro value.
                        $aclArray['aro'][$aroSectionValue][] = $aroValue;
                        $update = 1;
                    }
                }
            }
        }

        if (is_array($aroGroupIds) and count($aroGroupIds) > 0) {
            $this->debugText("appendAcl(): Appending ARO_GROUP_ID's");

            while (list (, $aroGroupId) = @each($aroGroupIds)) {
                if (!is_array($aclArray['aro_groups']) or !in_array($aroGroupId, $aclArray['aro_groups'])) {
                    $this->debugText("appendAcl(): ARO Group ID: $aroGroupId");
                    $aclArray['aro_groups'][] = $aroGroupId;
                    $update = 1;
                } else {
                    $this->debugText("appendAcl(): Duplicate ARO_Group_ID, ignoring... ");
                }
            }
        }

        if (is_array($axoArray) and count($axoArray) > 0) {
            $this->debugText("appendAcl(): Appending AXO's");

            while (list ($axoSectionValue, $axoValueArray) = @each($axoArray)) {
                foreach ($axoValueArray as $axoValue) {
                    if (!in_array($axoValue, $aclArray['axo'][$axoSectionValue])) {
                        $this->debugText("appendAcl(): AXO Section Value: $axoSectionValue AXO VALUE: $axoValue");
                        $aclArray['axo'][$axoSectionValue][] = $axoValue;
                        $update = 1;
                    } else {
                        $this->debugText("appendAcl(): Duplicate AXO, ignoring... ");
                    }
                }
            }
        }

        if (is_array($axoGroupIds) and count($axoGroupIds) > 0) {
            $this->debugText("appendAcl(): Appending AXO_GROUP_ID's");
            while (list (, $axoGroupId) = @each($axoGroupIds)) {
                if (!is_array($aclArray['axo_groups']) or !in_array($axoGroupId, $aclArray['axo_groups'])) {
                    $this->debugText("appendAcl(): AXO Group ID: $axoGroupId");
                    $aclArray['axo_groups'][] = $axoGroupId;
                    $update = 1;
                } else {
                    $this->debugText("appendAcl(): Duplicate ARO_Group_ID, ignoring... ");
                }
            }
        }

        if (is_array($acoArray) and count($acoArray) > 0) {
            $this->debugText("appendAcl(): Appending ACO's");

            while (list ($acoSectionValue, $acoValueArray) = @each($acoArray)) {
                foreach ($acoValueArray as $acoValue) {
                    if (!in_array($acoValue, $aclArray['aco'][$acoSectionValue])) {
                        $this->debugText("appendAcl(): ACO Section Value: $acoSectionValue ACO VALUE: $acoValue");
                        $aclArray['aco'][$acoSectionValue][] = $acoValue;
                        $update = 1;
                    } else {
                        $this->debugText("appendAcl(): Duplicate ACO, ignoring... ");
                    }
                }
            }
        }

        if ($update == 1) {
            $this->debugText("appendAcl(): Update flag set, updating ACL.");
            // function editAcl($aclId, $acoArray, $aroArray, $aroGroupIds=NULL, $axoArray=NULL, $axoGroupIds=NULL, $allow=1, $enabled=1, $returnValue=NULL, $note=NULL) {
            return $this->editAcl(
                $aclId,
                $aclArray['aco'],
                $aclArray['aro'],
                $aclArray['aro_groups'],
                $aclArray['axo'],
                $aclArray['axo_groups'],
                $aclArray['allow'],
                $aclArray['enabled'],
                $aclArray['return_value'],
                $aclArray['note']
            );
        }

        // Return true if everything is duplicate and no ACL id updated.
        $this->debugText("appendAcl(): Update flag not set, NOT updating ACL.");
        return true;
    }

    /**
     * Opposite of appendAcl(). Removes objects from a specific ACL. (named after PHP's array_shift())
     *
     * @param integer $aclId ACL ID #
     * @param array $aroArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array $aroGroupIds Array of Group IDs
     * @param array $axoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array $axoGroupIds Array of Group IDs
     * @param array $acoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @return boolean true if successful, false otherwise.
     */
    public function shiftAcl(
        $aclId,
        $aroArray = null,
        $aroGroupIds = null,
        $axoArray = null,
        $axoGroupIds = null,
        $acoArray = null
    ) {
        $this->debugText("shift_acl(): ACL_ID: $aclId");

        $update = 0;

        if (empty($aclId)) {
            $this->debugText("shift_acl(): No ACL_ID specified! ACL_ID: $aclId");
            return false;
        }

        // Grab ACL data.
        $aclArray = $this->getAcl($aclId);

        // showarray($aclArray);
        // Remove each object type seperately.
        if (is_array($aroArray) and count($aroArray) > 0) {
            $this->debugText("shift_acl(): Removing ARO's");

            while (list ($aroSectionValue, $aroValueArray) = @each($aroArray)) {
                foreach ($aroValueArray as $aroValue) {
                    $this->debugText("shift_acl(): ARO Section Value: $aroSectionValue ARO VALUE: $aroValue");

                    // Only search if aro array contains data.
                    if (count($aclArray['aro'][$aroSectionValue]) != 0) {
                        $aroKey = array_search($aroValue, $aclArray['aro'][$aroSectionValue]);

                        if ($aroKey !== false) {
                            $this->debugText("shift_acl(): Removing ARO. ($aroKey)");
                            unset($aclArray['aro'][$aroSectionValue][$aroKey]);
                            $update = 1;
                        } else {
                            $this->debugText("shift_acl(): ARO doesn't exist, can't remove it.");
                        }
                    }
                }
            }
        }

        if (is_array($aroGroupIds) and count($aroGroupIds) > 0) {
            $this->debugText("shift_acl(): Removing ARO_GROUP_ID's");

            while (list (, $aroGroupId) = @each($aroGroupIds)) {
                $this->debugText("shift_acl(): ARO Group ID: $aroGroupId");
                $aroGroupKey = array_search($aroGroupId, $aclArray['aro_groups']);

                if ($aroGroupKey !== false) {
                    $this->debugText("shift_acl(): Removing ARO Group. ($aroGroupKey)");
                    unset($aclArray['aro_groups'][$aroGroupKey]);
                    $update = 1;
                } else {
                    $this->debugText("shift_acl(): ARO Group doesn't exist, can't remove it.");
                }
            }
        }

        if (is_array($axoArray) and count($axoArray) > 0) {
            $this->debugText("shift_acl(): Removing AXO's");

            while (list ($axoSectionValue, $axoValueArray) = @each($axoArray)) {
                foreach ($axoValueArray as $axoValue) {
                    $this->debugText("shift_acl(): AXO Section Value: $axoSectionValue AXO VALUE: $axoValue");
                    $axoKey = array_search($axoValue, $aclArray['axo'][$axoSectionValue]);

                    if ($axoKey !== false) {
                        $this->debugText("shift_acl(): Removing AXO. ($axoKey)");
                        unset($aclArray['axo'][$axoSectionValue][$axoKey]);
                        $update = 1;
                    } else {
                        $this->debugText("shift_acl(): AXO doesn't exist, can't remove it.");
                    }
                }
            }
        }

        if (is_array($axoGroupIds) and count($axoGroupIds) > 0) {
            $this->debugText("shift_acl(): Removing AXO_GROUP_ID's");

            while (list (, $axoGroupId) = @each($axoGroupIds)) {
                $this->debugText("shift_acl(): AXO Group ID: $axoGroupId");
                $axoGroupKey = array_search($axoGroupId, $aclArray['axo_groups']);

                if ($axoGroupKey !== false) {
                    $this->debugText("shift_acl(): Removing AXO Group. ($axoGroupKey)");
                    unset($aclArray['axo_groups'][$axoGroupKey]);
                    $update = 1;
                } else {
                    $this->debugText("shift_acl(): AXO Group doesn't exist, can't remove it.");
                }
            }
        }

        if (is_array($acoArray) and count($acoArray) > 0) {
            $this->debugText("shift_acl(): Removing ACO's");

            while (list ($acoSectionValue, $acoValueArray) = @each($acoArray)) {
                foreach ($acoValueArray as $acoValue) {
                    $this->debugText("shift_acl(): ACO Section Value: $acoSectionValue ACO VALUE: $acoValue");
                    $acoKey = array_search($acoValue, $aclArray['aco'][$acoSectionValue]);

                    if ($acoKey !== false) {
                        $this->debugText("shift_acl(): Removing ACO. ($acoKey)");
                        unset($aclArray['aco'][$acoSectionValue][$acoKey]);
                        $update = 1;
                    } else {
                        $this->debugText("shift_acl(): ACO doesn't exist, can't remove it.");
                    }
                }
            }
        }

        if ($update == 1) {
            // We know something was changed, so lets see if no ACO's or no ARO's are left assigned to this ACL, if so, delete the ACL completely.
            // $this->showarray($aclArray);
            $this->debugText("shift_acl(): ACOs: " . $this->countAll($aclArray['aco']) . " AROs: " . $this->countAll($aclArray['aro']) . "");

            if ($this->countAll($aclArray['aco']) == 0 or ($this->countAll($aclArray['aro']) == 0 and ($this->countAll($aclArray['axo']) == 0 or $aclArray['axo'] == false) and (count($aclArray['aro_groups']) == 0 or $aclArray['aro_groups'] == false) and (count($aclArray['axo_groups']) == 0 or $aclArray['axo_groups'] == false))) {
                $this->debugText("shift_acl(): No ACOs or ( AROs AND AXOs AND ARO Groups AND AXO Groups) left assigned to this ACL (ID: $aclId), deleting ACL.");

                return $this->delAcl($aclId);
            }

            $this->debugText("shift_acl(): Update flag set, updating ACL.");

            return $this->editAcl(
                $aclId,
                $aclArray['aco'],
                $aclArray['aro'],
                $aclArray['aro_groups'],
                $aclArray['axo'],
                $aclArray['axo_groups'],
                $aclArray['allow'],
                $aclArray['enabled'],
                $aclArray['return_value'],
                $aclArray['note']
            );
        }

        // Return true if everything is duplicate and no ACL id updated.
        $this->debugText("shift_acl(): Update flag not set, NOT updating ACL.");
        return true;
    }

    /**
     * Grabs ACL data.
     *
     * @param integer $aclId ACL ID #
     * @return boolean false if not found, or Associative Array with the following items:
     * - 'aco' => Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * - 'aro' => Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * - 'axo' => Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * - 'aro_groups' => Array of Group IDs
     * - 'axo_groups' => Array of Group IDs
     * - 'acl_id' => integer ACL ID #
     * - 'allow' => integer Allow flag
     * - 'enabled' => integer Enabled flag
     * - 'return_value' => string Return Value
     * - 'note' => string Note
     */
    public function getAcl($aclId)
    {
        $this->debugText("getAcl(): ACL_ID: $aclId");

        if (empty($aclId)) {
            $this->debugText("getAcl(): No ACL_ID specified! ACL_ID: $aclId");
            return false;
        }

        // Grab ACL information
        $query = "select id, allow, enabled, return_value, note from " . $this->dbTablePrefix . "acl where id = " . $aclId . "";
        $aclRow = $this->db->GetRow($query);

        // return false if not found
        if (!$aclRow) {
            $this->debugText("getAcl(): No ACL found for that ID! ACL_ID: $aclId");
            return false;
        }

        $retarr = [];

        list ($retarr['acl_id'], $retarr['allow'], $retarr['enabled'], $retarr['return_value'], $retarr['note']) = $aclRow;

        // Grab selected ACO's
        $query = "select distinct a.section_value, a.value, c.name, b.name from " . $this->dbTablePrefix . "aco_map a, " . $this->dbTablePrefix . "aco b, " . $this->dbTablePrefix . "aco_sections c
              where ( a.section_value=b.section_value AND a.value = b.value) AND b.section_value=c.value AND a.acl_id = $aclId";
        $rs = $this->db->Execute($query);
        $rows = $rs->GetRows();

        $retarr['aco'] = [];
        while (list (, $row) = @each($rows)) {
            list ($sectionValue, $value, $section, $aco) = $row;
            $this->debugText("Section Value: $sectionValue Value: $value Section: $section ACO: $aco");

            $retarr['aco'][$sectionValue][] = $value;
        }
        // showarray($aco);

        // Grab selected ARO's
        $query = "select distinct a.section_value, a.value, c.name, b.name from " . $this->dbTablePrefix . "aro_map a, " . $this->dbTablePrefix . "aro b, " . $this->dbTablePrefix . "aro_sections c
              where ( a.section_value=b.section_value AND a.value = b.value) AND b.section_value=c.value AND a.acl_id = $aclId";
        $rs = $this->db->Execute($query);
        $rows = $rs->GetRows();

        $retarr['aro'] = [];
        while (list (, $row) = @each($rows)) {
            list ($sectionValue, $value, $section, $aro) = $row;
            $this->debugText("Section Value: $sectionValue Value: $value Section: $section ARO: $aro");

            $retarr['aro'][$sectionValue][] = $value;
        }
        // showarray($options_aro);

        // Grab selected AXO's
        $query = "select distinct a.section_value, a.value, c.name, b.name from " . $this->dbTablePrefix . "axo_map a, " . $this->dbTablePrefix . "axo b, " . $this->dbTablePrefix . "axo_sections c
              where ( a.section_value=b.section_value AND a.value = b.value) AND b.section_value=c.value AND a.acl_id = $aclId";
        $rs = $this->db->Execute($query);
        $rows = $rs->GetRows();

        $retarr['axo'] = [];
        while (list (, $row) = @each($rows)) {
            list ($sectionValue, $value, $section, $axo) = $row;
            $this->debugText("Section Value: $sectionValue Value: $value Section: $section AXO: $axo");

            $retarr['axo'][$sectionValue][] = $value;
        }
        // showarray($options_aro);

        // Grab selected ARO groups.
        $retarr['aro_groups'] = [];
        $query = "select distinct group_id from " . $this->dbTablePrefix . "aro_groups_map where  acl_id = $aclId";
        $retarr['aro_groups'] = $this->db->GetCol($query);
        // showarray($selected_groups);

        // Grab selected AXO groups.
        $retarr['axo_groups'] = [];
        $query = "select distinct group_id from " . $this->dbTablePrefix . "axo_groups_map where  acl_id = $aclId";
        $retarr['axo_groups'] = $this->db->GetCol($query);
        // showarray($selected_groups);

        return $retarr;
    }

    /**
     * Checks for conflicts when adding a specific ACL.
     *
     * @param array $acoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array $aroArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array $aroGroupIds Array of Group IDs
     * @param array $axoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array $axoGroupIds Array of Group IDs
     * @param array $ignoreAclIds Array of ACL IDs to ignore from the result set.
     * @return boolean Returns true if conflict is found.
     */
    public function isConflictingAcl(
        $acoArray,
        $aroArray,
        $aroGroupIds = null,
        $axoArray = null,
        $axoGroupIds = null,
        $ignoreAclIds = null
    ) {
        // Check for potential conflicts. Ignore groups, as groups will almost always have "conflicting" ACLs.
        // Thats part of inheritance.
        if (!is_array($acoArray)) {
            $this->debugText('isConflictingAcl(): Invalid ACO Array.');
            return false;
        }

        if (!is_array($aroArray)) {
            $this->debugText('isConflictingAcl(): Invalid ARO Array.');
            return false;
        }

        $query = '
      SELECT		a.id
      FROM		' . $this->dbTablePrefix . 'acl a
      LEFT JOIN	' . $this->dbTablePrefix . 'aco_map ac ON ac.acl_id=a.id
      LEFT JOIN	' . $this->dbTablePrefix . 'aro_map ar ON ar.acl_id=a.id
      LEFT JOIN	' . $this->dbTablePrefix . 'axo_map ax ON ax.acl_id=a.id
      LEFT JOIN	' . $this->dbTablePrefix . 'axo_groups_map axg ON axg.acl_id=a.id
      LEFT JOIN	' . $this->dbTablePrefix . 'axo_groups xg ON xg.id=axg.group_id
      ';

        // ACO
        foreach ($acoArray as $acoSectionValue => $acoValueArray) {
            $this->debugText("isConflictingAcl(): ACO Section Value: $acoSectionValue ACO VALUE: $acoValueArray");
            // showarray($acoArray);

            if (!is_array($acoValueArray)) {
                $this->debugText('isConflictingAcl(): Invalid Format for ACO Array item. Skipping...');
                continue;
                // return true;
            }
            // Move the below line in to the LEFT JOIN above for PostgreSQL sake.
            // 'ac1' => 'ac.acl_id=a.id',
            $whereQuery = [
                'ac2' => '(ac.section_value=' . $this->db->quote($acoSectionValue) . ' AND ac.value IN (\'' . implode('\',\'', $acoValueArray) . '\'))'
            ];

            // ARO
            foreach ($aroArray as $aroSectionValue => $aroValueArray) {
                $this->debugText("isConflictingAcl(): ARO Section Value: $aroSectionValue ARO VALUE: $aroValueArray");

                if (!is_array($aroValueArray)) {
                    $this->debugText('isConflictingAcl(): Invalid Format for ARO Array item. Skipping...');
                    continue;
                    // return true;
                }

                $this->debugText("isConflictingAcl(): Search: ACO Section: $acoSectionValue ACO Value: $acoValueArray ARO Section: $aroSectionValue ARO Value: $aroValueArray");

                // Move the below line in to the LEFT JOIN above for PostgreSQL sake.
                // $whereQuery['ar1'] = 'ar.acl_id=a.id';
                $whereQuery['ar2'] = '(ar.section_value=' . $this->db->quote($aroSectionValue) . ' AND ar.value IN (\'' . implode('\',\'', $aroValueArray) . '\'))';

                if (is_array($axoArray) and count($axoArray) > 0) {
                    foreach ($axoArray as $axoSectionValue => $axoValueArray) {
                        $this->debugText("isConflictingAcl(): AXO Section Value: $axoSectionValue AXO VALUE: $axoValueArray");

                        if (!is_array($axoValueArray)) {
                            $this->debugText('isConflictingAcl(): Invalid Format for AXO Array item. Skipping...');
                            continue;
                            // return true;
                        }

                        $this->debugText("isConflictingAcl(): Search: ACO Section: $acoSectionValue ACO Value: $acoValueArray ARO Section: $aroSectionValue ARO Value: $aroValueArray AXO Section: $axoSectionValue AXO Value: $axoValueArray");

                        // $whereQuery['ax1'] = 'ax.acl_id=x.id';
                        $whereQuery['ax1'] = 'ax.acl_id=a.id';
                        $whereQuery['ax2'] = '(ax.section_value=' . $this->db->quote($axoSectionValue) . ' AND ax.value IN (\'' . implode('\',\'', $axoValueArray) . '\'))';

                        $where = 'WHERE ' . implode(' AND ', $whereQuery);

                        $conflictResult = $this->db->GetCol($query . $where);

                        if (is_array($conflictResult) and !empty($conflictResult)) {
                            // showarray($conflictResult);

                            if (is_array($ignoreAclIds)) {
                                $conflictResult = array_diff($conflictResult, $ignoreAclIds);
                            }

                            if (count($conflictResult) > 0) {
                                $conflictingAclsStr = implode(',', $conflictResult);
                                $this->debugText("isConflictingAcl(): Conflict FOUND!!! ACL_IDS: ($conflictingAclsStr)");
                                return true;
                            }
                        }
                    }
                } else {
                    $whereQuery['ax1'] = '(ax.section_value IS NULL AND ax.value IS NULL)';
                    $whereQuery['ax2'] = 'xg.name IS NULL';

                    $where = 'WHERE ' . implode(' AND ', $whereQuery);

                    $conflictResult = $this->db->GetCol($query . $where);

                    if (is_array($conflictResult) and !empty($conflictResult)) {
                        // showarray($conflictResult);

                        if (is_array($ignoreAclIds)) {
                            $conflictResult = array_diff($conflictResult, $ignoreAclIds);
                        }

                        if (count($conflictResult) > 0) {
                            $conflictingAclsStr = implode(',', $conflictResult);
                            $this->debugText("isConflictingAcl(): Conflict FOUND!!! ACL_IDS: ($conflictingAclsStr)");
                            return true;
                        }
                    }
                }
            }
        }

        $this->debugText('isConflictingAcl(): No conflicting ACL found.');
        return false;
    }

    /**
     * Add's an ACL. ACO_IDS, ARO_IDS, GROUP_IDS must all be arrays.
     *
     * @param array   $acoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $aroArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $aroGroupIds Array of Group IDs
     * @param array   $axoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $axoGroupIds Array of Group IDs
     * @param integer $allow Allow flag
     * @param integer $enabled Enabled flag
     * @param string  $returnValue Return Value
     * @param string  $note Note
     * @param string  $sectionValue ACL Section Value
     * @param integer $aclId ACL ID # Specific Request
     * @return boolean Return ACL ID of new ACL if successful, false otherewise.
     */
    public function addAcl(
        $acoArray,
        $aroArray,
        $aroGroupIds = null,
        $axoArray = null,
        $axoGroupIds = null,
        $allow = 1,
        $enabled = 1,
        $returnValue = null,
        $note = null,
        $sectionValue = null,
        $aclId = false
    ) {
        $this->debugText("addAcl():");

        if (count($acoArray) == 0) {
            $this->debugText("Must select at least one Access Control Object");
            return false;
        }

        if ((is_null($aroArray) || count($aroArray) == 0) && (is_null($aroGroupIds) || count($aroGroupIds) == 0)) {
            $this->debugText("Must select at least one Access Request Object or Group");
            return false;
        }

        if (empty($allow)) {
            $allow = 0;
        }

        if (empty($enabled)) {
            $enabled = 0;
        }

        if (!empty($sectionValue) and !$this->getObjectSectionSectionId(null, $sectionValue, 'ACL')) {
            $this->debugText("addAcl(): Section Value: $sectionValue DOES NOT exist in the database.");
            return false;
        }

        // Unique the group arrays. Later one we unique ACO/ARO/AXO arrays.
        if (is_array($aroGroupIds)) {
            $aroGroupIds = array_unique($aroGroupIds);
        }
        if (is_array($axoGroupIds)) {
            $axoGroupIds = array_unique($axoGroupIds);
        }

        // Check for conflicting ACLs.
        if ($this->isConflictingAcl($acoArray, $aroArray, $aroGroupIds, $axoArray, $axoGroupIds, [
            $aclId
        ])) {
            $this->debugText("addAcl(): Detected possible ACL conflict, not adding ACL!");
            return false;
        }

        // Edit ACL if acl_id is set. This is simply if we're being called by editAcl().
        if ($this->getAcl($aclId) == false) {
            if (empty($sectionValue)) {
                $sectionValue = 'system';
                if (!$this->getObjectSectionSectionId(null, $sectionValue, 'ACL')) {
                    // Use the acl section with the lowest order value.
                    $aclSectionsTable = $this->dbTablePrefix . 'acl_sections';
                    $aclSectionOrderValue = $this->db->GetOne("SELECT min(order_value) from $aclSectionsTable");

                    $query = "
            SELECT value
            FROM $aclSectionsTable
            WHERE order_value = $aclSectionOrderValue
          ";
                    $sectionValue = $this->db->GetOne($query);

                    if (empty($sectionValue)) {
                        $this->debugText("addAcl(): No valid acl section found.");
                        return false;
                    } else {
                        $this->debugText("addAcl(): Using default section value: $sectionValue.");
                    }
                }
            }

            // ACL not specified, so create acl_id
            if (empty($aclId)) {
                // Create ACL row first, so we have the acl_id
                $aclId = $this->db->GenID($this->dbTablePrefix . 'acl_seq', 10);
                // Double check the ACL ID was generated.
                if (empty($aclId)) {
                    $this->debugText("addAcl(): ACL_ID generation failed!");
                    return false;
                }
            }

            // Begin transaction _after_ GenID. Because on the first run, if GenID has to create the sequence,
            // the transaction will fail.
            $this->db->BeginTrans();

            $query = 'INSERT INTO ' . $this->dbTablePrefix . 'acl (id,section_value,allow,enabled,return_value,note,updated_date) VALUES(' . $aclId . ',' . $this->db->quote($sectionValue) . ',' . $allow . ',' . $enabled . ',' . $this->db->quote($returnValue) . ', ' . $this->db->quote($note) . ',' . time() . ')';
            $result = $this->db->Execute($query);
        } else {
            $sectionSql = '';
            if (!empty($sectionValue)) {
                $sectionSql = 'section_value=' . $this->db->quote($sectionValue) . ',';
            }

            $this->db->BeginTrans();

            // Update ACL row, and remove all mappings so they can be re-inserted.
            $query = '
        UPDATE	' . $this->dbTablePrefix . 'acl
        SET             ' . $sectionSql . '
            allow=' . $allow . ',
            enabled=' . $enabled . ',
            return_value=' . $this->db->quote($returnValue) . ',
            note=' . $this->db->quote($note) . ',
            updated_date=' . time() . '
        WHERE	id=' . $aclId;
            $result = $this->db->Execute($query);

            if ($result) {
                $this->debugText("Update completed without error, delete mappings...");
                // Delete all mappings so they can be re-inserted.
                foreach (['aco_map', 'aro_map', 'axo_map', 'aro_groups_map', 'axo_groups_map'] as $map) {
                    $query = 'DELETE FROM ' . $this->dbTablePrefix . $map . ' WHERE acl_id=' . $aclId;
                    $rs = $this->db->Execute($query);

                    if (!is_object($rs)) {
                        $this->debugDb('addAcl');
                        $this->db->RollBackTrans();
                        return false;
                    }
                }
            }
        }

        if (!is_object($result)) {
            $this->debugDb('addAcl');
            $this->db->RollBackTrans();
            return false;
        }

        $this->debugText("Insert or Update completed without error, insert new mappings.");
        // Insert ACO/ARO/AXO mappings
        foreach (['aco', 'aro', 'axo'] as $map) {
            $mapArray = ${$map . 'Array'};

            if (!is_array($mapArray)) {
                continue;
            }

            foreach ($mapArray as $sectionValue => $valueArray) {
                $this->debugText('Insert: ' . strtoupper($map) . ' Section Value: ' . $sectionValue . ' ' . strtoupper($map) . ' VALUE: ' . $valueArray);
                // $this->showarray ($acoValueArray);

                if (!is_array($valueArray)) {
                    $this->debugText('addAcl (): Invalid Format for ' . strtoupper($map) . ' Array item. Skipping...');
                    continue;
                    // return true;
                }

                $valueArray = array_unique($valueArray);

                foreach ($valueArray as $value) {
                    $objectId = $this->getObjectId($sectionValue, $value, $map);

                    if (empty($objectId)) {
                        $this->debugText('addAcl(): ' . strtoupper($map) . " Object Section Value: $sectionValue Value: $value DOES NOT exist in the database. Skipping...");
                        $this->db->RollBackTrans();
                        return false;
                    }

                    $query = 'INSERT INTO ' . $this->dbTablePrefix . $map . '_map (acl_id,section_value,value) VALUES (' . $aclId . ', ' . $this->db->quote($sectionValue) . ', ' . $this->db->quote($value) . ')';
                    $rs = $this->db->Execute($query);

                    if (!is_object($rs)) {
                        $this->debugDb('addAcl');
                        $this->db->RollBackTrans();
                        return false;
                    }
                }
            }
        }

        // Insert ARO/AXO GROUP mappings
        foreach (['aro', 'axo'] as $map) {
            $mapGroupIds = ${$map . 'GroupIds'};

            if (!is_array($mapGroupIds)) {
                continue;
            }

            foreach ($mapGroupIds as $groupId) {
                $this->debugText('Insert: ' . strtoupper($map) . ' GROUP ID: ' . $groupId);

                $groupData = $this->getGroupData($groupId, $map);

                if (empty($groupData)) {
                    $this->debugText('addAcl(): ' . strtoupper($map) . " Group: $groupId DOES NOT exist in the database. Skipping...");
                    $this->db->RollBackTrans();
                    return false;
                }

                $query = 'INSERT INTO ' . $this->dbTablePrefix . $map . '_groups_map (acl_id,group_id) VALUES (' . $aclId . ', ' . $groupId . ')';
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    $this->debugDb('addAcl');
                    $this->db->RollBackTrans();
                    return false;
                }
            }
        }

        $this->db->CommitTrans();

        if ($this->caching == true and $this->forceCacheExpire == true) {
            // Expire all cache.
            $this->Cache_Lite->clean('default');
        }

        // Return only the ID in the first row.
        return $aclId;
    }

    /**
     * Edit's an ACL, ACO_IDS, ARO_IDS, GROUP_IDS must all be arrays.
     *
     * @param integer $aclId ACL ID # to edit
     * @param array   $acoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $aroArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $aroGroupIds Array of Group IDs
     * @param array   $axoArray Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     * @param array   $axoGroupIds Array of Group IDs
     * @param integer $allow Allow flag
     * @param integer $enabled Enabled flag
     * @param string  $returnValue Return Value
     * @param string  $note Note
     * @param string  $sectionValue ACL Section Value
     * @return boolean Return true if successful, false otherewise.
     */
    public function editAcl(
        $aclId,
        $acoArray,
        $aroArray,
        $aroGroupIds = null,
        $axoArray = null,
        $axoGroupIds = null,
        $allow = 1,
        $enabled = 1,
        $returnValue = null,
        $note = null,
        $sectionValue = null
    ) {
        $this->debugText("editAcl():");

        if (empty($aclId)) {
            $this->debugText("editAcl(): Must specify a single ACL_ID to edit");
            return false;
        }
        if (count($acoArray) == 0) {
            $this->debugText("editAcl(): Must select at least one Access Control Object");
            return false;
        }

        if (count($aroArray) == 0 and count($aroGroupIds) == 0) {
            $this->debugText("editAcl(): Must select at least one Access Request Object or Group");
            return false;
        }

        if (empty($allow)) {
            $allow = 0;
        }

        if (empty($enabled)) {
            $enabled = 0;
        }

        // if ($this->addAcl($acoArray, $aroArray, $groupIds, $allow, $enabled, $aclId)) {
        if ($this->addAcl($acoArray, $aroArray, $aroGroupIds, $axoArray, $axoGroupIds, $allow, $enabled, $returnValue, $note, $sectionValue, $aclId)) {
            return true;
        } else {
            $this->debugText("editAcl(): error in addAcl()");
            return false;
        }
    }

    /**
     * Deletes a given ACL
     *
     * @param integer $aclId ACL ID # to delete
     * @return boolean Returns true if successful, false otherwise.
     */
    public function delAcl($aclId)
    {
        $this->debugText("delAcl(): ID: $aclId");

        if (empty($aclId)) {
            $this->debugText("delAcl(): ACL_ID ($aclId) is empty, this is required");
            return false;
        }

        $this->db->BeginTrans();

        // Delete all mappings to the ACL first
        foreach (['aco_map', 'aro_map', 'axo_map', 'aro_groups_map', 'axo_groups_map'] as $map) {
            $query = 'DELETE FROM ' . $this->dbTablePrefix . $map . ' WHERE acl_id=' . $aclId;
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('delAcl');
                $this->db->RollBackTrans();
                return false;
            }
        }

        // Delete the ACL
        $query = 'DELETE FROM ' . $this->dbTablePrefix . 'acl WHERE id=' . $aclId;
        $this->debugText('delete query: ' . $query);
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('delAcl');
            $this->db->RollBackTrans();
            return false;
        }

        $this->debugText("delAcl(): deleted ACL ID: $aclId");
        $this->db->CommitTrans();

        if ($this->caching == true and $this->forceCacheExpire == true) {
            // Expire all cache.
            $this->Cache_Lite->clean('default');
        }

        return true;
    }

    /*
     *
     * Groups
     *
     */

    /**
     * Grabs all the groups from the database doing preliminary grouping by parent
     *
     * @param string $groupType Group Type, either 'ARO' or 'AXO'
     * @return array Returns 2-Dimensional array: $array[<parent_id>][<group_id>] = <group_name>
     */
    public function sortGroups($groupType = self::TYPE_ARO)
    {
        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $table = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $table = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        // Grab all groups from the database.
        $query = 'SELECT id, parent_id, name FROM ' . $table . ' ORDER BY parent_id, name';
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('sortGroups');
            return false;
        }

        /*
         * Save groups in an array sorted by parent. Should be make it easier for later on.
         */
        $sortedGroups = [];

        while ($row = $rs->FetchRow()) {
            $id       = $row[0];
            $parentId = $row[1];
            $name     = $row[2];

            $sortedGroups[$parentId][$id] = $name;
        }

        return $sortedGroups;
    }

    /**
     * Takes the array returned by sortGroups() and formats for human consumption.
     *
     * Recursively calls itself to produce the desired output.
     *
     * @param array   $sortedGroups Output from gacl_api->sorted_groups($groupType)
     * @param array   $type Output type desired, either 'TEXT', 'HTML', or 'ARRAY'
     * @param integer $rootId Root of tree to produce
     * @param integer $level Current level of depth
     * @param array   $formattedGroups Pass the current formatted groups object for appending via recursion.
     * @return array Array of formatted text, ordered by group id, formatted according to $type
     */
    public function formatGroups($sortedGroups, $type = self::FORMAT_TYPE_TEXT, $rootId = 0, $level = 0, $formattedGroups = null)
    {
        if (!is_array($sortedGroups)) {
            return false;
        }

        if (!is_array($formattedGroups)) {
            $formattedGroups = array();
        }

        // $this->showarray($formattedGroups);

        // while (list($id,$name) = @each($sortedGroups[$rootId])) {
        if (isset($sortedGroups[$rootId])) {
            // $lastId = end( array_keys($sortedGroups[$rootId]));
            // PHP5 compatibility
            $keys = array_keys($sortedGroups[$rootId]);
            $lastId = end($keys);
            unset($keys);

            foreach ($sortedGroups[$rootId] as $id => $name) {
                switch (strtoupper($type)) {
                    case self::FORMAT_TYPE_TEXT:
                        /*
                         * Formatting optimized for TEXT (combo box) output.
                         */

                        if (is_numeric($level)) {
                            $level = str_repeat('&nbsp;&nbsp; ', $level);
                        }

                        if (strlen($level) >= 8) {
                            if ($id == $lastId) {
                                $spacing = substr($level, 0, - 8) . '\'- ';
                                $level = substr($level, 0, - 8) . '&nbsp;&nbsp; ';
                            } else {
                                $spacing = substr($level, 0, - 8) . '|- ';
                            }
                        } else {
                            $spacing = $level;
                        }

                        $next = $level . '|&nbsp; ';
                        $text = $spacing . $name;
                        break;
                    case self::FORMAT_TYPE_HTML:
                        /*
                         * Formatting optimized for HTML (tables) output.
                         */
                        $width = $level * 20;
                        $spacing = "<img src=\"s.gif\" width=\"$width\">";
                        $next = $level + 1;
                        $text = $spacing . " " . $name;
                        break;
                    case self::FORMAT_TYPE_ARRAY:
                        $next = $level;
                        $text = $name;
                        break;
                    default:
                        return false;
                }

                $formattedGroups[$id] = $text;
                /*
                 * Recurse if we can.
                 */

                // if (isset($sortedGroups[$id]) AND count($sortedGroups[$id]) > 0) {
                if (isset($sortedGroups[$id])) {
                    // $this->debugText("formatGroups(): Recursing! Level: $level");
                    $formattedGroups = $this->formatGroups($sortedGroups, $type, $id, $next, $formattedGroups);
                } else {
                    // $this->debugText("formatGroups(): Found last branch!");
                }
            }
        }

        // $this->debugText("formatGroups(): Returning final array.");

        return $formattedGroups;
    }

    /**
     * Gets the group_id given the name or value.
     *
     * Will only return one group id, so if there are duplicate names, it will return false.
     *
     * @param string $value Group Value
     * @param string $name  Group Name
     * @param string $groupType Group Type, either 'ARO' or 'AXO'
     * @return integer Returns Group ID if found and Group ID is unique in database, otherwise, returns false
     */
    public function getGroupId($value = null, $name = null, $groupType = self::TYPE_ARO)
    {
        $this->debugText("getGroupId(): Value: $value, Name: $name, Type: $groupType");

        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $table = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $table = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        $name = trim($name);
        $value = trim($value);

        if (empty($name) and empty($value)) {
            $this->debugText("getGroupId(): name and value, at least one is required");
            return false;
        }

        $query = 'SELECT id FROM ' . $table . ' WHERE ';
        if (!empty($value)) {
            $query .= ' value=' . $this->db->quote($value);
        } else {
            $query .= ' name=' . $this->db->quote($name);
        }
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getGroupId');
            return false;
        }

        $rowCount = $rs->RecordCount();

        if ($rowCount > 1) {
            $this->debugText("getGroupId(): Returned $rowCount rows, can only return one. Please make your names unique.");
            return false;
        }

        if ($rowCount == 0) {
            $this->debugText("getGroupId(): Returned $rowCount rows");
            return false;
        }

        $row = $rs->FetchRow();

        // Return the ID.
        return $row[0];
    }

    /**
     * Gets a groups child IDs
     *
     * @param integer $groupId   Group ID #
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @param string  $recurse    Either 'RECURSE' or 'NO_RECURSE', to recurse while fetching group children.
     * @return array Array of Child ID's of the referenced group
     */
    public function getGroupChildren($groupId, $groupType = self::TYPE_ARO, $recurse = self::FETCH_NO_RECURSE)
    {
        $this->debugText("getGroupChildren(): Group_ID: $groupId Group Type: $groupType Recurse: $recurse");

        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType = self::TYPE_AXO;
                $table = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $groupType = 'aro';
                $table = $this->dbTablePrefix . 'aro_groups';
        }

        if (empty($groupId)) {
            $this->debugText("getGroupChildren(): ID ($groupId) is empty, this is required");
            return false;
        }

        $query = '
        SELECT		g1.id
        FROM		' . $table . ' g1';

        // FIXME-mikeb: Why is group_id in quotes?
        switch (strtoupper($recurse)) {
            case self::FETCH_RECURSE:
                $query .= '
        LEFT JOIN 	' . $table . ' g2 ON g2.lft<g1.lft AND g2.rgt>g1.rgt
        WHERE		g2.id=' . $groupId;
                break;
            default:
                $query .= '
        WHERE		g1.parent_id=' . $groupId;
        }

        $query .= '
        ORDER BY	g1.value';

        return $this->db->GetCol($query);
    }

    /**
     * Gets the group data given the GROUP_ID.
     *
     * @param integer $groupId Group ID #
     * @param string $groupType Group Type, either 'ARO' or 'AXO'
     * @return array Returns numerically indexed array with the following columns:
     * - array[0] = (int) Group ID #
     * - array[1] = (int) Parent Group ID #
     * - array[2] = (string) Group Value
     * - array[3] = (string) Group Name
     * - array[4] = (int) lft MPTT Value
     * - array[5] = (int) rgt MPTT Value
     */
    public function getGroupData($groupId, $groupType = self::TYPE_ARO)
    {
        $this->debugText("getGroupData(): Group_ID: $groupId Group Type: $groupType");

        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType = self::TYPE_AXO;
                $table = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $groupType = self::TYPE_ARO;
                $table = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        if (empty($groupId)) {
            $this->debugText("getGroupData(): ID ($groupId) is empty, this is required");
            return false;
        }

        $query = 'SELECT id, parent_id, value, name, lft, rgt FROM ' . $table . ' WHERE id=' . $groupId;
        // $rs = $this->db->Execute($query);
        $row = $this->db->GetRow($query);

        if ($row) {
            return $row;
        }

        $this->debugText("getObjectData(): Group does not exist.");
        return false;
    }

    /**
     * Grabs the parent_id of a given group
     *
     * @param integer $id Group ID #
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @return integer Parent ID of the Group
     */
    public function getGroupParentId($id, $groupType = self::TYPE_ARO)
    {
        $this->debugText("getGroupParentId(): ID: $id Group Type: $groupType");

        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $table = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $table = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        if (empty($id)) {
            $this->debugText("getGroupParentId(): ID ($id) is empty, this is required");
            return false;
        }

        $query = 'SELECT parent_id FROM ' . $table . ' WHERE id=' . $id;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getGroupParentId');
            return false;
        }

        $rowCount = $rs->RecordCount();

        if ($rowCount > 1) {
            $this->debugText("getGroupParentId(): Returned $rowCount rows, can only return one. Please make your names unique.");
            return false;
        }

        if ($rowCount == 0) {
            $this->debugText("getGroupParentId(): Returned $rowCount rows");
            return false;
        }

        $row = $rs->FetchRow();

        // Return the ID.
        return $row[0];
    }

    /**
     * Grabs the id of the root group for the specified tree
     *
     * @param string $groupType Group Type, either 'ARO' or 'AXO'
     * @return integer Root Group ID #
     */
    public function getRootGroupId($groupType = self::TYPE_ARO)
    {
        $this->debugText('getRootGroupId(): Group Type: ' . $groupType);

        switch (strtolower($groupType)) {
            case self::TYPE_AXO:
                $table = $this->dbTablePrefix . 'axo_groups';
                break;
            case self::TYPE_ARO:
                $table = $this->dbTablePrefix . 'aro_groups';
                break;
            default:
                $this->debugText('getRootGroupId(): Invalid Group Type: ' . $groupType);
                return false;
        }

        $query = 'SELECT id FROM ' . $table . ' WHERE parent_id=0';
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getRootGroupId');
            return false;
        }

        $rowCount = $rs->RecordCount();

        switch ($rowCount) {
            case 1:
                $row = $rs->FetchRow();
                // Return the ID.
                return $row[0];
            case 0:
                $this->debugText('getRootGroupId(): Returned 0 rows, you do not have a root group defined yet.');
                return false;
        }

        $this->debugText('getRootGroupId(): Returned ' . $rowCount . ' rows, can only return one. Your tree is very broken.');
        return false;
    }

    /*
     * ======================================================================*\
     * Function: map_path_to_root()
     * Purpose: Maps a unique path to root to a specific group. Each group can only have
     * one path to root.
     * \*======================================================================
     */
    /**
     * REMOVED *
     */
    /*
     * ======================================================================*\
     * Function: put_path_to_root()
     * Purpose: Writes the unique path to root to the database. There should really only be
     * one path to root for each level "deep" the groups go. If the groups are branched
     * 10 levels deep, there should only be 10 unique path to roots. These of course
     * overlap each other more and more the closer to the root/trunk they get.
     * \*======================================================================
     */
    /**
     * REMOVED *
     */
    /*
     * ======================================================================*\
     * Function: clean_path_to_root()
     * Purpose: Cleans up any paths that are not being used.
     * \*======================================================================
     */
    /**
     * REMOVED *
     */
    /*
     * ======================================================================*\
     * Function: get_path_to_root()
     * Purpose: Generates the path to root for a given group.
     * \*======================================================================
     */
    /**
     * REMOVED *
     */

    /**
     * Inserts a group, defaults to be on the "root" branch.
     *
     * Since v3.3.x you can only create one group with Parent_ID=0
     * So, its a good idea to create a "Virtual Root" group with Parent_ID=0
     * Then assign other groups to that.
     *
     * @param string  $value      Group Value
     * @param string  $name       Group Name
     * @param integer $parentId  Parent Group ID #
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @return integer New Group ID # if successful, false if otherwise.
     */
    public function addGroup($value, $name, $parentId = 0, $groupType = self::TYPE_ARO)
    {
        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType = self::TYPE_AXO;
                $table     = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $groupType = self::TYPE_ARO;
                $table     = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        $this->debugText("addGroup(): Name: $name Value: $value Parent ID: $parentId Group Type: $groupType");

        $name = trim($name);
        $value = trim($value);

        if ($name == '') {
            $this->debugText("addGroup(): name ($name) OR parent id ($parentId) is empty, this is required");
            return false;
        }

        // This has to be outside the transaction, because the first time it is run, it will say the sequence
        // doesn't exist. Then try to create it, but the transaction will already by aborted by then.
        $insertId = $this->db->GenID($this->dbTablePrefix . $groupType . '_groups_id_seq', 10);
        if ($value === '') {
            $value = $insertId;
        }

        $this->db->BeginTrans();

        // special case for root group
        if ($parentId == 0) {
            // check a root group is not already defined
            $query = 'SELECT id FROM ' . $table . ' WHERE parent_id=0';
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('addGroup');
                $this->db->RollBackTrans();
                return false;
            }

            if ($rs->RowCount() > 0) {
                $this->debugText('addGroup (): A root group already exists.');
                $this->db->RollBackTrans();
                return false;
            }

            $parentLft = 0;
            $parentRgt = 1;
        } else {
            if (empty($parentId)) {
                $this->debugText("addGroup (): parent id ($parentId) is empty, this is required");
                $this->db->RollbackTrans();
                return false;
            }

            // grab parent details from database
            $query = 'SELECT id, lft, rgt FROM ' . $table . ' WHERE id=' . $parentId;
            $row = $this->db->GetRow($query);

            if (!is_array($row)) {
                $this->debugDb('addGroup');
                $this->db->RollBackTrans();
                return false;
            }

            if (empty($row)) {
                $this->debugText('addGroup (): Parent ID: ' . $parentId . ' not found.');
                $this->db->RollBackTrans();
                return false;
            }

            $parentLft = $row[1];
            $parentRgt = $row[2];

            // make room for the new group
            $query = 'UPDATE ' . $table . ' SET rgt=rgt+2 WHERE rgt>=' . $parentRgt;
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('addGroup');
                $this->db->RollBackTrans();
                return false;
            }

            $query = 'UPDATE ' . $table . ' SET lft=lft+2 WHERE lft>' . $parentRgt;
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('addGroup');
                $this->db->RollBackTrans();
                return false;
            }
        }

        $query = 'INSERT INTO ' . $table . ' (id,parent_id,name,value,lft,rgt) VALUES (' . $insertId . ',' . $parentId . ',' . $this->db->quote($name) . ',' . $this->db->quote($value) . ',' . $parentRgt . ',' . ($parentRgt + 1) . ')';
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('addGroup');
            $this->db->RollBackTrans();
            return false;
        }

        $this->db->CommitTrans();

        $this->debugText('addGroup (): Added group as ID: ' . $insertId);
        return $insertId;
    }

    /**
     * Gets all objects assigned to a group.
     *
     * If $option == 'RECURSE' it will get all objects in child groups as well.
     * defaults to omit child groups.
     *
     * @param integer $groupId   Group ID #
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @param string  $option     Option, either 'RECURSE' or 'NO_RECURSE'
     * @return array Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     */
    public function getGroupObjects($groupId, $groupType = self::TYPE_ARO, $option = self::FETCH_NO_RECURSE)
    {
        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType    = self::TYPE_AXO;
                $objectTable = $this->dbTablePrefix . 'axo';
                $groupTable   = $this->dbTablePrefix . 'axo_groups';
                $mapTable     = $this->dbTablePrefix . 'groups_axo_map';
                break;
            default:
                $groupType    = self::TYPE_ARO;
                $objectTable = $this->dbTablePrefix . 'aro';
                $groupTable   = $this->dbTablePrefix . 'aro_groups';
                $mapTable     = $this->dbTablePrefix . 'groups_aro_map';
                break;
        }

        $this->debugText("getGroupObjects(): Group ID: $groupId");

        if (empty($groupId)) {
            $this->debugText("getGroupObjects(): Group ID:  ($groupId) is empty, this is required");
            return false;
        }

        $query = '
        SELECT		o.section_value,o.value';

        if ($option == self::FETCH_RECURSE) {
            $query .= '
        FROM		' . $groupTable . ' g2
        JOIN		' . $groupTable . ' g1 ON g1.lft>=g2.lft AND g1.rgt<=g2.rgt
        JOIN		' . $mapTable . ' gm ON gm.group_id=g1.id
        JOIN		' . $objectTable . ' o ON o.id=gm.' . $groupType . '_id
        WHERE		g2.id=' . $groupId;
        } else {
            $query .= '
        FROM		' . $mapTable . ' gm
        JOIN		' . $objectTable . ' o ON o.id=gm.' . $groupType . '_id
        WHERE		gm.group_id=' . $groupId;
        }

        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getGroupObjects');
            return false;
        }

        $this->debugText("getGroupObjects(): Got group objects, formatting array.");

        $retarr = [];

        // format return array.
        while ($row = $rs->FetchRow()) {
            $section = $row[0];
            $value   = $row[1];

            $retarr[$section][] = $value;
        }

        return $retarr;
    }

    /**
     * Assigns an Object to a group
     *
     * @param integer $groupId Group ID #
     * @param string  $objectSectionValue Object Section Value
     * @param string  $objectValue Object Value
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @return boolean Returns true if successful, false otherwise.
     */
    public function addGroupObject($groupId, $objectSectionValue, $objectValue, $groupType = self::TYPE_ARO)
    {
        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType   = self::TYPE_AXO;
                $table       = $this->dbTablePrefix . 'groups_axo_map';
                $objectTable = $this->dbTablePrefix . 'axo';
                $groupTable  = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $groupType   = self::TYPE_ARO;
                $table       = $this->dbTablePrefix . 'groups_aro_map';
                $objectTable = $this->dbTablePrefix . 'aro';
                $groupTable  = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        $this->debugText("addGroupObject(): Group ID: $groupId Section Value: $objectSectionValue Value: $objectValue Group Type: $groupType");

        $objectSectionValue = trim($objectSectionValue);
        $objectValue        = trim($objectValue);

        if (empty($groupId) or empty($objectValue) or empty($objectSectionValue)) {
            $this->debugText("addGroupObject(): Group ID: ($groupId) OR Value ($objectValue) OR Section value ($objectSectionValue) is empty, this is required");
            return false;
        }

        // test to see if object & group exist and if object is already a member
        $query = '
        SELECT		o.id AS id,g.id AS group_id,gm.group_id AS member
        FROM		' . $objectTable . ' o
        LEFT JOIN	' . $groupTable . ' g ON g.id=' . $groupId . '
        LEFT JOIN	' . $table . ' gm ON (gm.' . $groupType . '_id=o.id AND gm.group_id=g.id)
        WHERE		(o.section_value=' . $this->db->quote($objectSectionValue) . ' AND o.value=' . $this->db->quote($objectValue) . ')';
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('addGroupObject');
            return false;
        }

        if ($rs->RecordCount() != 1) {
            $this->debugText('addGroupObject(): Value (' . $objectValue . ') OR Section value (' . $objectSectionValue . ') is invalid. Does this object exist?');
            return false;
        }

        $row = $rs->FetchRow();

        if ($row[1] != $groupId) {
            $this->debugText('addGroupObject(): Group ID (' . $groupId . ') is invalid. Does this group exist?');
            return false;
        }

        // Group_ID == Member
        if ($row[1] == $row[2]) {
            $this->debugText('addGroupObject(): Object: (' . $objectSectionValue . ' -> ' . $objectValue . ') is already a member of Group: (' . $groupId . ')');
            // Object is already assigned to group. Return true.
            return true;
        }

        $objectId = $row[0];

        $query = 'INSERT INTO ' . $table . ' (group_id,' . $groupType . '_id) VALUES (' . $groupId . ',' . $objectId . ')';
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('addGroupObject');
            return false;
        }

        $this->debugText('addGroupObject(): Added Object: ' . $objectId . ' to Group ID: ' . $groupId);

        if ($this->caching == true and $this->forceCacheExpire == true) {
            // Expire all cache.
            $this->Cache_Lite->clean('default');
        }

        return true;
    }

    /**
     * Removes an Object from a group.
     *
     * @param integer $groupId Group ID #
     * @param string  $objectSectionValue Object Section Value
     * @param string  $objectValue Object Value
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @return boolean Returns true if successful, false otherwise
     */
    public function delGroupObject($groupId, $objectSectionValue, $objectValue, $groupType = self::TYPE_ARO)
    {
        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType = self::TYPE_AXO;
                $table = $this->dbTablePrefix . 'groups_axo_map';
                break;
            default:
                $groupType = self::TYPE_ARO;
                $table = $this->dbTablePrefix . 'groups_aro_map';
                break;
        }

        $this->debugText("delGroupObject(): Group ID: $groupId Section value: $objectSectionValue Value: $objectValue");

        $objectSectionValue = trim($objectSectionValue);
        $objectValue        = trim($objectValue);

        if (empty($groupId) or empty($objectValue) or empty($objectSectionValue)) {
            $this->debugText("delGroupObject(): Group ID:  ($groupId) OR Section value: $objectSectionValue OR Value ($objectValue) is empty, this is required");
            return false;
        }

        if (!$objectId = $this->getObjectId($objectSectionValue, $objectValue, $groupType)) {
            $this->debugText("delGroupObject (): Group ID ($groupId) OR Value ($objectValue) OR Section value ($objectSectionValue) is invalid. Does this object exist?");
            return false;
        }

        $query = 'DELETE FROM ' . $table . ' WHERE group_id=' . $groupId . ' AND ' . $groupType . '_id=' . $objectId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('delGroupObject');
            return false;
        }

        $this->debugText("delGroupObject(): Deleted Value: $objectValue to Group ID: $groupId assignment");

        if ($this->caching == true and $this->forceCacheExpire == true) {
            // Expire all cache.
            $this->Cache_Lite->clean('default');
        }

        return true;
    }

    /**
     * Edits a group
     *
     * @param integer $groupId Group ID #
     * @param string  $groupType Group Value
     * @param string  $name Group Name
     * @param integer $parentId Parent ID #
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @returns boolean Returns true if successful, false otherwise
     */
    public function editGroup($groupId, $value = null, $name = null, $parentId = null, $groupType = self::TYPE_ARO)
    {
        $this->debugText("editGroup(): ID: $groupId Name: $name Value: $value Parent ID: $parentId Group Type: $groupType");

        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType = self::TYPE_AXO;
                $table     = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $groupType = self::TYPE_ARO;
                $table     = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        if (empty($groupId)) {
            $this->debugText('editGroup(): Group ID (' . $groupId . ') is empty, this is required');
            return false;
        }

        if (!is_array($curr = $this->getGroupData($groupId, $groupType))) {
            $this->debugText('editGroup(): Invalid Group ID: ' . $groupId);
            return false;
        }

        $name = trim($name);

        // don't set name if it is unchanged
        if ($name == $curr[3]) {
            unset($name);
        }

        // don't set parent_id if it is unchanged
        if ($parentId == $curr[1]) {
            unset($parentId);
        }

        if (!empty($parentId)) {
            if ($groupId == $parentId) {
                $this->debugText('editGroup(): Groups can\'t be a parent to themselves. Incest is bad. ;)');
                return false;
            }

            // Make sure we don't re-parent to our own children.
            // Grab all children of this group_id.
            $childrenIds = $this->getGroupChildren($groupId, $groupType, self::FETCH_RECURSE);
            if (is_array($childrenIds)) {
                if (@in_array($parentId, $childrenIds)) {
                    $this->debugText('editGroup(): Groups can\'t be re-parented to their own children, this would be incestuous!');
                    return false;
                }
            }
            unset($childrenIds);

            // make sure parent exists
            if (!$this->getGroupData($parentId, $groupType)) {
                $this->debugText('editGroup(): Parent Group (' . $parentId . ') doesn\'t exist');
                return false;
            }
        }

        $set = [];

        // update name if it is specified.
        if (!empty($name)) {
            $set[] = 'name=' . $this->db->quote($name);
        }

        // update parent_id if it is specified.
        if (!empty($parentId)) {
            $set[] = 'parent_id=' . $parentId;
        }

        // update value if it is specified.
        if (!empty($value)) {
            $set[] = 'value=' . $this->db->quote($value);
        }

        if (empty($set)) {
            $this->debugText('editGroup(): Nothing to update.');
            return false;
        }

        $this->db->BeginTrans();

        $query = 'UPDATE ' . $table . ' SET ' . implode(',', $set) . ' WHERE id=' . $groupId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('editGroup');
            $this->db->RollbackTrans();
            return false;
        }

        $this->debugText('editGroup(): Modified group ID: ' . $groupId);

        // rebuild group tree if parent_id has changed
        if (!empty($parentId)) {
            if (!$this->internalRebuildTree($table, $this->getRootGroupId($groupType))) {
                $this->db->RollbackTrans();
                return false;
            }
        }

        $this->db->CommitTrans();

        if ($this->caching == true and $this->forceCacheExpire == true) {
            // Expire all cache.
            $this->Cache_Lite->clean('default');
        }

        return true;
    }

    /**
     * rebuilds the group tree for the given type
     *
     * @param string  $groupType Group Type, either 'ARO' or 'AXO'
     * @param integer $groupId Group ID #
     * @param integer $left Left value of Group
     * @return boolean Returns true if successful, false otherwise
     */
    public function rebuildTree($groupType = self::TYPE_ARO, $groupId = null, $left = 1)
    {
        $this->debugText("rebuild_tree(): Group Type: $groupType Group ID: $groupId Left: $left");

        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType = self::TYPE_AXO;
                $table     = $this->dbTablePrefix . 'axo_groups';
                break;
            default:
                $groupType = self::TYPE_ARO;
                $table     = $this->dbTablePrefix . 'aro_groups';
                break;
        }

        if (!isset($groupId)) {
            if ($groupId = $this->getRootGroupId($groupType)) {
                $left    = 1;

                $this->debugText('rebuild_tree(): No Group ID Specified, using Root Group ID: ' . $groupId);
            } else {
                $this->debugText('rebuild_tree(): A Root group could not be found, are there any groups defined?');
                return false;
            }
        }

        $this->db->BeginTrans();
        $rebuilt = $this->internalRebuildTree($table, $groupId, $left);

        if ($rebuilt === false) {
            $this->debugText('rebuild_tree(): Error rebuilding tree!');
            $this->db->RollBackTrans();
            return false;
        }

        $this->db->CommitTrans();
        $this->debugText('rebuild_tree(): Tree rebuilt.');
        return true;
    }

    /**
     * Utility recursive function called by rebuild_tree()
     *
     * @param string $table Table name of group type
     * @param integer $groupId Group ID #
     * @param integer $left Left value of Group
     * @return integer Returns right value of this node + 1
     */
    protected function internalRebuildTree($table, $groupId, $left = 1)
    {
        $this->debugText("internalRebuildTree(): Table: $table Group ID: $groupId Left: $left");

        // get all children of this node
        $query = 'SELECT id FROM ' . $table . ' WHERE parent_id=' . $groupId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('internalRebuildTree');
            return false;
        }

        // the right value of this node is the left value + 1
        $right = $left + 1;

        while ($row = $rs->FetchRow()) {
            // recursive execution of this function for each
            // child of this node
            // $right is the current right value, which is
            // incremented by the rebuild_tree function
            $right = $this->internalRebuildTree($table, $row[0], $right);

            if ($right === false) {
                return false;
            }
        }

        // we've got the left value, and now that we've processed
        // the children of this node we also know the right value
        $query = 'UPDATE ' . $table . ' SET lft=' . $left . ', rgt=' . $right . ' WHERE id=' . $groupId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('internalRebuildTree');
            return false;
        }

        // return the right value of this node + 1
        return $right + 1;
    }

    /**
     * deletes a given group
     *
     * @param integer $groupId Group ID #
     * @param boolean $reparentChildren If true, child groups of this group will be reparented to the current group's parent.
     * @param string $groupType Group Type, either 'ARO' or 'AXO'
     * @return boolean Returns true if successful, false otherwise.
     */
    public function delGroup($groupId, $reparentChildren = true, $groupType = self::TYPE_ARO)
    {
        switch (strtolower(trim($groupType))) {
            case self::TYPE_AXO:
                $groupType            = self::TYPE_AXO;
                $table                = $this->dbTablePrefix . 'axo_groups';
                $groupsMapTable       = $this->dbTablePrefix . 'axo_groups_map';
                $groupsObjectMapTable = $this->dbTablePrefix . 'groups_axo_map';
                break;
            default:
                $groupType            = self::TYPE_ARO;
                $table                = $this->dbTablePrefix . 'aro_groups';
                $groupsMapTable       = $this->dbTablePrefix . 'aro_groups_map';
                $groupsObjectMapTable = $this->dbTablePrefix . 'groups_aro_map';
                break;
        }

        $this->debugText("delGroup(): ID: $groupId Reparent Children: $reparentChildren Group Type: $groupType");

        if (empty($groupId)) {
            $this->debugText("delGroup(): Group ID ($groupId) is empty, this is required");
            return false;
        }

        // Get details of this group
        $query = 'SELECT id, parent_id, name, lft, rgt FROM ' . $table . ' WHERE id=' . $groupId;
        $groupDetails = $this->db->GetRow($query);

        if (!is_array($groupDetails)) {
            $this->debugDb('delGroup');
            return false;
        }

        $parentId = $groupDetails[1];

        $left  = $groupDetails[3];
        $right = $groupDetails[4];

        $this->db->BeginTrans();

        // grab list of all children
        $childrenIds = $this->getGroupChildren($groupId, $groupType, self::FETCH_RECURSE);

        // prevent deletion of root group & reparent of children if it has more than one immediate child
        if ($parentId == 0) {
            $query = 'SELECT count(*) FROM ' . $table . ' WHERE parent_id=' . $groupId;
            $childCount = $this->db->GetOne($query);

            if (($childCount > 1) and $reparentChildren) {
                $this->debugText('delGroup (): You cannot delete the root group and reparent children, this would create multiple root groups.');
                $this->db->RollbackTrans();
                return false;
            }
        }

        $success = false;

        /*
         * Handle children here.
         */
        switch (true) {
            // there are no child groups, just delete group
            case !is_array($childrenIds):
            case count($childrenIds) == 0:
                // remove acl maps
                $query = 'DELETE FROM ' . $groupsMapTable . ' WHERE group_id=' . $groupId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // remove group object maps
                $query = 'DELETE FROM ' . $groupsObjectMapTable . ' WHERE group_id=' . $groupId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // remove group
                $query = 'DELETE FROM ' . $table . ' WHERE id=' . $groupId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // move all groups right of deleted group left by width of deleted group
                $query = 'UPDATE ' . $table . ' SET lft=lft-' . ($right - $left + 1) . ' WHERE lft>' . $right;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                $query = 'UPDATE ' . $table . ' SET rgt=rgt-' . ($right - $left + 1) . ' WHERE rgt>' . $right;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                $success = true;
                break;
            case $reparentChildren == true:
                // remove acl maps
                $query = 'DELETE FROM ' . $groupsMapTable . ' WHERE group_id=' . $groupId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // remove group object maps
                $query = 'DELETE FROM ' . $groupsObjectMapTable . ' WHERE group_id=' . $groupId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // remove group
                $query = 'DELETE FROM ' . $table . ' WHERE id=' . $groupId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // set parent of immediate children to parent group
                $query = 'UPDATE ' . $table . ' SET parent_id=' . $parentId . ' WHERE parent_id=' . $groupId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // move all children left by 1
                $query = 'UPDATE ' . $table . ' SET lft=lft-1, rgt=rgt-1 WHERE lft>' . $left . ' AND rgt<' . $right;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // move all groups right of deleted group left by 2
                $query = 'UPDATE ' . $table . ' SET lft=lft-2 WHERE lft>' . $right;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                $query = 'UPDATE ' . $table . ' SET rgt=rgt-2 WHERE rgt>' . $right;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                $success = true;
                break;
            default:
                // make list of group and all children
                $groupIds   = $childrenIds;
                $groupIds[] = $groupId;

                // remove acl maps
                $query = 'DELETE FROM ' . $groupsMapTable . ' WHERE group_id IN (' . implode(',', $groupIds) . ')';
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // remove group object maps
                $query = 'DELETE FROM ' . $groupsObjectMapTable . ' WHERE group_id IN (' . implode(',', $groupIds) . ')';
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // remove groups
                $query = 'DELETE FROM ' . $table . ' WHERE id IN (' . implode(',', $groupIds) . ')';
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                // move all groups right of deleted group left by width of deleted group
                $query = 'UPDATE ' . $table . ' SET lft=lft-' . ($right - $left + 1) . ' WHERE lft>' . $right;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                $query = 'UPDATE ' . $table . ' SET rgt=rgt-' . ($right - $left + 1) . ' WHERE rgt>' . $right;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    break;
                }

                $success = true;
        }

        // if the delete failed, rollback the trans and return false
        if (!$success) {
            $this->debugDb('delGroup');
            $this->db->RollBackTrans();
            return false;
        }

        $this->debugText("delGroup(): deleted group ID: $groupId");
        $this->db->CommitTrans();

        if ($this->caching == true and $this->forceCacheExpire == true) {
            // Expire all cache.
            $this->Cache_Lite->clean('default');
        }

        return true;
    }

    /*
     *
     * Objects (ACO/ARO/AXO)
     *
     */

    /**
     * Grabs all Objects's in the database, or specific to a section_value
     *
     * @param string  $sectionValue Filter to this section value
     * @param integer $returnHidden Returns hidden objects if 1, leaves them out otherwise.
     * @param string  $objectType   Object Type, either 'ACO', 'ARO', 'AXO', or 'ACL'
     * @return ADORecordSet Returns recordset directly, with object ID only selected:
     */
    public function getObject($sectionValue = null, $returnHidden = 1, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType = self::TYPE_ACO;
                $table = $this->dbTablePrefix . 'aco';
                break;
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $table = $this->dbTablePrefix . 'aro';
                break;
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $table = $this->dbTablePrefix . 'axo';
                break;
            case self::TYPE_ACL:
                $objectType = self::TYPE_ACL;
                $table = $this->dbTablePrefix . 'acl';
                break;
            default:
                $this->debugText('getObject(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("getObject(): Section Value: $sectionValue Object Type: $objectType");

        $query = 'SELECT id FROM ' . $table;

        $where = [];

        if (!empty($sectionValue)) {
            $where[] = 'section_value=' . $this->db->quote($sectionValue);
        }

        if ($returnHidden == 0 and $objectType != self::TYPE_ACL) {
            $where[] = 'hidden=0';
        }

        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $rs = $this->db->GetCol($query);

        if (!is_array($rs)) {
            $this->debugDb('getObject');
            return false;
        }

        // Return Object IDs
        return $rs;
    }

    /**
     * Grabs ID's of all Objects (ARO's and AXO's only) in the database not assigned to a Group.
     *
     * This function is useful for applications that synchronize user databases with an outside source.
     * If syncrhonization doesn't automatically place users in an appropriate group, this function can
     * quickly identify them so that they can be assigned to the correct group.
     *
     * @param integer $returnHidden Returns hidden objects if 1, does not if 0.
     * @param string  $objectType   Object Type, either 'ARO' or 'AXO' (groupable types)
     * @return array Returns an array of object ID's
     */
    public function getUngroupedObjects($returnHidden = 1, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $table      = $this->dbTablePrefix . 'aro';
                break;
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $table      = $this->dbTablePrefix . 'axo';
                break;
            default:
                $this->debugText('getUngroupedObjects(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("getUngroupedObjects(): Object Type: $objectType");

        $query = 'SELECT id FROM ' . $table . ' a
              LEFT JOIN ' . $this->dbTablePrefix . 'groups_' . $objectType . '_map b ON a.id = b.' . $objectType . '_id';

        $where = [];
        $where[] = 'b.group_id IS NULL';

        if ($returnHidden == 0) {
            $where[] = 'a.hidden=0';
        }

        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getUngroupedObjects');
            return false;
        }

        $retarr = [];

        while (!$rs->EOF) {
            $retarr[] = $rs->fields[0];
            $rs->MoveNext();
        }

        // Return Array of object IDS
        return $retarr;
    }

    /**
     * Grabs all Objects in the database, or specific to a section_value
     *
     * @param string  $sectionValue Filter for section value
     * @param integer $returnHidden Returns hidden objects if 1, does not if 0
     * @param string  $objectType   Object Type, either 'ACO', 'ARO', 'AXO'
     * @return array Returns objects in format suitable for addAcl and isConflictingAcl
     * - i.e. Associative array, item={Section Value}, key={Array of Object Values} i.e. ["<Section Value>" => ["<Value 1>", "<Value 2>", "<Value 3>"], ...]
     */
    public function getObjects($sectionValue = null, $returnHidden = 1, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType = self::TYPE_ACO;
                $table      = $this->dbTablePrefix . 'aco';
                break;
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $table      = $this->dbTablePrefix . 'aro';
                break;
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $table      = $this->dbTablePrefix . 'axo';
                break;
            default:
                $this->debugText('getObjects(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("getObjects(): Section Value: $sectionValue Object Type: $objectType");

        $query = 'SELECT section_value,value FROM ' . $table;

        $where = [];

        if (!empty($sectionValue)) {
            $where[] = 'section_value=' . $this->db->quote($sectionValue);
        }

        if ($returnHidden == 0) {
            $where[] = 'hidden=0';
        }

        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getObjects');
            return false;
        }

        $retarr = [];

        while ($row = $rs->FetchRow()) {
            $retarr[$row[0]][] = $row[1];
        }

        // Return objects
        return $retarr;
    }

    /**
     * Gets all data pertaining to a specific Object.
     *
     * @param integer $objectId   Object ID #
     * @param string  $objectType Object Type, either 'ACO', 'ARO', 'AXO'
     * @return array Returns 2-Dimensional array of rows with columns = ( section_value, value, order_value, name, hidden )
     */
    public function getObjectData($objectId, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType = self::TYPE_ACO;
                $table      = $this->dbTablePrefix . 'aco';
                break;
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $table      = $this->dbTablePrefix . 'aro';
                break;
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $table      = $this->dbTablePrefix . 'axo';
                break;
            default:
                $this->debugText('getObjectData(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("getObjectData(): Object ID: $objectId Object Type: $objectType");

        if (empty($objectId)) {
            $this->debugText("getObjectData(): Object ID ($objectId) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("getObjectData(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $query = 'SELECT section_value,value,order_value,name,hidden FROM ' . $table . ' WHERE id=' . $objectId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getObjectData');
            return false;
        }

        if ($rs->RecordCount() < 1) {
            $this->debugText('getObjectData(): Returned  ' . $rs->RecordCount() . ' rows');
            return false;
        }

        // Return all objects
        return $rs->GetRows();
    }

    /**
     * Gets the object_id given the section_value AND value of the object.
     *
     * @param string $sectionValue Object Section Value
     * @param string $value Object Value
     * @param string $objectType Object Type, either 'ACO', 'ARO', 'AXO'
     * @return integer Object ID #
     */
    public function getObjectId($sectionValue, $value, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType = self::TYPE_ACO;
                $table      = $this->dbTablePrefix . 'aco';
                break;
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $table      = $this->dbTablePrefix . 'aro';
                break;
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $table      = $this->dbTablePrefix . 'axo';
                break;
            default:
                $this->debugText('getObjectId(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("getObjectId(): Section Value: $sectionValue Value: $value Object Type: $objectType");

        $sectionValue = trim($sectionValue);
        $value        = trim($value);

        if (empty($sectionValue) and empty($value)) {
            $this->debugText("getObjectId(): Section Value ($value) AND value ($value) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("getObjectId(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $query = 'SELECT id FROM ' . $table . ' WHERE section_value=' . $this->db->quote($sectionValue) . ' AND value=' . $this->db->quote($value);
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getObjectId');
            return false;
        }

        $rowCount = $rs->RecordCount();

        if ($rowCount > 1) {
            $this->debugText("getObjectId(): Returned $rowCount rows, can only return one. This should never happen, the database may be missing a unique key.");
            return false;
        }

        if ($rowCount == 0) {
            $this->debugText("getObjectId(): Returned $rowCount rows");
            return false;
        }

        $row = $rs->FetchRow();

        // Return the ID.
        return $row[0];
    }

    /**
     * Gets the object_section_value given object id
     *
     * @param integer $objectId   Object ID #
     * @param string  $objectType Object Type, either 'ACO', 'ARO', or 'AXO'
     * @return string Object Section Value
     */
    public function getObjectSectionValue($objectId, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType = self::TYPE_ACO;
                $table      = $this->dbTablePrefix . 'aco';
                break;
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $table      = $this->dbTablePrefix . 'aro';
                break;
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $table      = $this->dbTablePrefix . 'axo';
                break;
            default:
                $this->debugText('getObjectSectionValue(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("getObjectSectionValue(): Object ID: $objectId Object Type: $objectType");

        if (empty($objectId)) {
            $this->debugText("getObjectSectionValue(): Object ID ($objectId) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("getObjectSectionValue(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $query = 'SELECT section_value FROM ' . $table . ' WHERE id=' . $objectId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getObjectSectionValue');
            return false;
        }

        $rowCount = $rs->RecordCount();

        if ($rowCount > 1) {
            $this->debugText("getObjectSectionValue(): Returned $rowCount rows, can only return one.");
            return false;
        }

        if ($rowCount == 0) {
            $this->debugText("getObjectSectionValue(): Returned $rowCount rows");
            return false;
        }

        $row = $rs->FetchRow();

        // Return the ID.
        return $row[0];
    }

    /**
     * Gets all groups an object is a member of.
     *
     * If $option == 'RECURSE' it will get all ancestor groups. Defaults to only get direct parents.
     *
     * @param integer $objectId Object ID #
     * @param string  $objectType Object Type, either 'ARO' or 'AXO'
     * @param string  $option Option, either 'RECURSE', or 'NO_RECURSE'
     * @return array Array of Group ID #'s, or false if Failed
     */
    public function getObjectGroups($objectId, $objectType = self::TYPE_ARO, $option = self::FETCH_NO_RECURSE)
    {
        $this->debugText('getObjectGroups(): Object ID: ' . $objectId . ' Object Type: ' . $objectType . ' Option: ' . $option);

        switch (strtolower(trim($objectType))) {
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $groupTable = $this->dbTablePrefix . 'axo_groups';
                $mapTable   = $this->dbTablePrefix . 'groups_axo_map';
                break;
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $groupTable = $this->dbTablePrefix . 'aro_groups';
                $mapTable   = $this->dbTablePrefix . 'groups_aro_map';
                break;
            default:
                $this->debugText('getObjectGroups(): Invalid Object Type: ' . $objectType);
                return false;
        }

        if (empty($objectId)) {
            $this->debugText('getObjectGroups(): Object ID: (' . $objectId . ') is empty, this is required');
            return false;
        }

        if (strtoupper($option) == self::FETCH_RECURSE) {
            $query = '
        SELECT		DISTINCT g.id AS group_id
        FROM		' . $mapTable . ' gm
        LEFT JOIN	' . $groupTable . ' g1 ON g1.id=gm.group_id
        LEFT JOIN	' . $groupTable . ' g ON g.lft<=g1.lft AND g.rgt>=g1.rgt';
        } else {
            $query = '
          SELECT		gm.group_id
          FROM		' . $mapTable . ' gm';
        }

        $query .= '
        WHERE		gm.' . $objectType . '_id=' . $objectId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getObjectGroups');
            return false;
        }

        $retarr = [];

        while ($row = $rs->FetchRow()) {
            $retarr[] = $row[0];
        }

        return $retarr;
    }

    /**
     * Inserts a new object
     *
     * @param string $sectionValue Object Section Value
     * @param string $name Object Name
     * @param string $value Object Value
     * @param integer $order Display Order
     * @param integer $hidden Hidden Flag, either 1 to hide, or 0 to show.
     * @param string $objectType Object Type, either 'ACO', 'ARO', or 'AXO'
     * @return integer Returns the ID # of the new object if successful, false otherwise
     */
    public function addObject($sectionValue, $name, $value = 0, $order = 0, $hidden = 0, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType          = self::TYPE_ACO;
                $table               = $this->dbTablePrefix . 'aco';
                $objectSectionsTable = $this->dbTablePrefix . 'aco_sections';
                break;
            case self::TYPE_ARO:
                $objectType          = self::TYPE_ARO;
                $table               = $this->dbTablePrefix . 'aro';
                $objectSectionsTable = $this->dbTablePrefix . 'aro_sections';
                break;
            case self::TYPE_AXO:
                $objectType          = self::TYPE_AXO;
                $table               = $this->dbTablePrefix . 'axo';
                $objectSectionsTable = $this->dbTablePrefix . 'axo_sections';
                break;
            default:
                $this->debugText('addObject(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("addObject(): Section Value: $sectionValue Value: $value Order: $order Name: $name Object Type: $objectType");

        $sectionValue = trim($sectionValue);
        $name         = trim($name);
        $value        = trim($value);
        $order        = trim($order);
        $hidden       = intval($hidden);

        if ($order == null or $order == '') {
            $order = 0;
        }

        if (empty($name) or empty($sectionValue)) {
            $this->debugText("addObject(): name ($name) OR section value ($sectionValue) is empty, this is required");
            return false;
        }

        if (strlen($name) >= 255 or strlen($value) >= 230) {
            $this->debugText("addObject(): name ($name) OR value ($value) is too long.");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("addObject(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        // Test to see if the section is invalid or object already exists.
        $query = '
      SELECT		CASE WHEN o.id IS NULL THEN 0 ELSE 1 END AS object_exists
      FROM		' . $objectSectionsTable . ' s
      LEFT JOIN	' . $table . ' o ON (s.value=o.section_value AND o.value=' . $this->db->quote($value) . ')
      WHERE		s.value=' . $this->db->quote($sectionValue);
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('addObject');
            return false;
        }

        if ($rs->RecordCount() != 1) {
            // Section is invalid
            $this->debugText("addObject(): Section Value: $sectionValue Object Type ($objectType) does not exist, this is required");
            return false;
        }

        $row = $rs->FetchRow();

        if ($row[0] == 1) {
            // Object is already created.
            return true;
        }

        $insertId = $this->db->GenID($this->dbTablePrefix . $objectType . '_seq', 10);
        $query = 'INSERT INTO ' . $table . ' (id,section_value,value,order_value,name,hidden) VALUES(' . $insertId . ',' . $this->db->quote($sectionValue) . ',' . $this->db->quote($value) . ',' . $order . ',' . $this->db->quote($name) . ',' . $hidden . ')';
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('addObject');
            return false;
        }

        $this->debugText("addObject(): Added object as ID: $insertId");
        return $insertId;
    }

    /**
     * Edits a given Object
     *
     * @param integer $objectId Object ID #
     * @param string  $sectionValue Object Section Value
     * @param string  $name Object Name
     * @param string  $value Object Value
     * @param integer $order Display Order
     * @param integer $hidden Hidden Flag, either 1 to hide, or 0 to show
     * @param string  $objectType Object Type, either 'ACO', 'ARO', or 'AXO'
     * @return boolean Returns true if successful, false otherwise
     */
    public function editObject(
        $objectId,
        $sectionValue,
        $name,
        $value = 0,
        $order = 0,
        $hidden = 0,
        $objectType = null
    ) {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType     = self::TYPE_ACO;
                $table          = $this->dbTablePrefix . 'aco';
                $objectMapTable = $this->dbTablePrefix . 'aco_map';
                break;
            case self::TYPE_ARO:
                $objectType     = self::TYPE_ARO;
                $table          = $this->dbTablePrefix . 'aro';
                $objectMapTable = $this->dbTablePrefix . 'aro_map';
                break;
            case self::TYPE_AXO:
                $objectType     = self::TYPE_AXO;
                $table          = $this->dbTablePrefix . 'axo';
                $objectMapTable = $this->dbTablePrefix . 'axo_map';
                break;
        }

        $this->debugText("editObject(): ID: $objectId Section Value: $sectionValue Value: $value Order: $order Name: $name Object Type: $objectType");

        $sectionValue = trim($sectionValue);
        $name         = trim($name);
        $value        = trim($value);
        $order        = trim($order);
        $hidden       = intval($hidden);

        if (empty($objectId) or empty($sectionValue)) {
            $this->debugText("editObject(): Object ID ($objectId) OR Section Value ($sectionValue) is empty, this is required");
            return false;
        }

        if (empty($name)) {
            $this->debugText("editObject(): name ($name) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("editObject(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $this->db->BeginTrans();

        // Get old value incase it changed, before we do the update.
        $query = 'SELECT value, section_value FROM ' . $table . ' WHERE id=' . $objectId;
        $old = $this->db->GetRow($query);

        $query = '
      UPDATE	' . $table . '
      SET		section_value=' . $this->db->quote($sectionValue) . ',
          value=' . $this->db->quote($value) . ',
          order_value=' . $this->db->quote($order) . ',
          name=' . $this->db->quote($name) . ',
          hidden=' . $hidden . '
      WHERE	id=' . $objectId;
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('editObject');
            $this->db->RollbackTrans();
            return false;
        }

        $this->debugText('editObject(): Modified ' . strtoupper($objectType) . ' ID: ' . $objectId);

        if ($old[0] != $value or $old[1] != $sectionValue) {
            $this->debugText("editObject(): Value OR Section Value Changed, update other tables.");

            $query = '
        UPDATE	' . $objectMapTable . '
        SET		value=' . $this->db->quote($value) . ',
            section_value=' . $this->db->quote($sectionValue) . '
        WHERE	section_value=' . $this->db->quote($old[1]) . '
          AND	value=' . $this->db->quote($old[0]);
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('editObject');
                $this->db->RollbackTrans();
                return false;
            }

            $this->debugText('editObject(): Modified Map Value: ' . $value . ' Section Value: ' . $sectionValue);
        }

        $this->db->CommitTrans();

        return true;
    }

    /**
     * Deletes a given Object and, if instructed to do so, erase all referencing objects
     *
     * ERASE feature by: Martino Piccinato
     *
     * @param integer $objectId   Object ID #
     * @param string  $objectType Object Type, either 'ACO', 'ARO', or 'AXO'
     * @param boolean $erase       Erases all referencing objects if true, leaves them alone otherwise.
     * @return boolean Returns true if successful, false otherwise.
     */
    public function delObject($objectId, $objectType = null, $erase = false)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType     = self::TYPE_ACO;
                $table          = $this->dbTablePrefix . 'aco';
                $objectMapTable = $this->dbTablePrefix . 'aco_map';
                break;
            case self::TYPE_ARO:
                $objectType       = self::TYPE_ARO;
                $table            = $this->dbTablePrefix . 'aro';
                $objectMapTable   = $this->dbTablePrefix . 'aro_map';
                $groupsMapTable   = $this->dbTablePrefix . 'aro_groups_map';
                $objectGroupTable = $this->dbTablePrefix . 'groups_aro_map';
                break;
            case self::TYPE_AXO:
                $objectType       = self::TYPE_AXO;
                $table            = $this->dbTablePrefix . 'axo';
                $objectMapTable   = $this->dbTablePrefix . 'axo_map';
                $groupsMapTable   = $this->dbTablePrefix . 'axo_groups_map';
                $objectGroupTable = $this->dbTablePrefix . 'groups_axo_map';
                break;
            default:
                $this->debugText('delObject(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("delObject(): ID: $objectId Object Type: $objectType, Erase all referencing objects: $erase");

        if (empty($objectId)) {
            $this->debugText("delObject(): Object ID ($objectId) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("delObject(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $this->db->BeginTrans();

        // Get Object section_value/value (needed to look for referencing objects)
        $query = 'SELECT section_value,value FROM ' . $table . ' WHERE id=' . $objectId;
        $object = $this->db->GetRow($query);

        if (empty($object)) {
            $this->debugText('delObject(): The specified object (' . strtoupper($objectType) . ' ID: ' . $objectId . ') could not be found.');
            $this->db->RollbackTrans();
            return false;
        }

        $sectionValue = $object[0];
        $value        = $object[1];

        // Get ids of acl referencing the Object (if any)
        $query = "SELECT acl_id FROM $objectMapTable WHERE value='$value' AND section_value='$sectionValue'";
        $aclIds = $this->db->GetCol($query);

        if ($erase) {
            // We were asked to erase all acl referencing it

            $this->debugText("delObject(): Erase was set to true, delete all referencing objects");

            if ($objectType == self::TYPE_ARO or $objectType == self::TYPE_AXO) {
                // The object can be referenced in groups_X_map tables
                // in the future this branching may become useless because
                // ACO might me "groupable" too

                // Get rid of groups_map referencing the Object
                $query = 'DELETE FROM ' . $objectGroupTable . ' WHERE ' . $objectType . '_id=' . $objectId;
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    $this->debugDb('editObject');
                    $this->db->RollBackTrans();
                    return false;
                }
            }

            if (!empty($aclIds)) {
                // There are acls actually referencing the object

                if ($objectType == self::TYPE_ACO) {
                    // I know it's extremely dangerous but
                    // if asked to really erase an ACO
                    // we should delete all acl referencing it
                    // (and relative maps)

                    // Do this below this branching
                    // where it uses $orphanAclIds as
                    // the array of the "orphaned" acl
                    // in this case all referenced acl are
                    // orhpaned acl

                    $orphanAclIds = $aclIds;
                } else {
                    // The object is not an ACO and might be referenced
                    // in still valid acls regarding also other object.
                    // In these cases the acl MUST NOT be deleted

                    // Get rid of $objectId map referencing erased objects
                    $query = "DELETE FROM $objectMapTable WHERE section_value='$sectionValue' AND value='$value'";
                    $this->db->Execute($query);

                    if (!is_object($rs)) {
                        $this->debugDb('editObject');
                        $this->db->RollBackTrans();
                        return false;
                    }

                    // Find the "orphaned" acl. I mean acl referencing the erased Object (map)
                    // not referenced anymore by other objects

                    $sqlAclIds = implode(",", $aclIds);

                    $query = '
            SELECT		a.id
            FROM		' . $this->dbTablePrefix . 'acl a
            LEFT JOIN	' . $objectMapTable . ' b ON a.id=b.acl_id
            LEFT JOIN	' . $groupsMapTable . ' c ON a.id=c.acl_id
            WHERE		b.value IS NULL
              AND		b.section_value IS NULL
              AND		c.group_id IS NULL
              AND		a.id in (' . $sqlAclIds . ')';
                    $orphanAclIds = $this->db->GetCol($query);
                } // End of else section of "if ($objectType == "aco")"

                if ($orphanAclIds) {
                    // If there are orphaned acls get rid of them

                    foreach ($orphanAclIds as $acl) {
                        $this->delAcl($acl);
                    }
                }
            } // End of if ($aclIds)

            // Finally delete the Object itself
            $query = "DELETE FROM $table WHERE id='$objectId'";
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('editObject');
                $this->db->RollBackTrans();
                return false;
            }

            $this->db->CommitTrans();
            return true;
        } // End of "if ($erase)"

        $groupsIds = false;

        if ($objectType == self::TYPE_AXO or $objectType == self::TYPE_ARO) {
            // If the object is "groupable" (may become unnecessary,
            // see above

            // Get id of groups where the object is assigned:
            // you must explicitly remove the object from its groups before
            // deleting it (don't know if this is really needed, anyway it's safer ;-)

            $query = 'SELECT group_id FROM ' . $objectGroupTable . ' WHERE ' . $objectType . '_id=' . $objectId;
            $groupsIds = $this->db->GetCol($query);
        }

        if ((isset($aclIds) and !empty($aclIds)) or (isset($groupsIds) and !empty($groupsIds))) {
            // The Object is referenced somewhere (group or acl), can't delete it

            $this->debugText("delObject(): Can't delete the object as it is being referenced by GROUPs (" . @implode($groupsIds) . ") or ACLs (" . @implode($aclIds, ",") . ")");
            $this->db->RollBackTrans();
            return false;
        } else {
            // The Object is NOT referenced anywhere, delete it

            $query = "DELETE FROM $table WHERE id='$objectId'";
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('editObject');
                $this->db->RollBackTrans();
                return false;
            }

            $this->db->CommitTrans();
            return true;
        }

        $this->db->RollbackTrans();
        return false;
    }

    /*
     *
     * Object Sections
     *
     */

    /**
     * Gets the object_section_id given the name AND/OR value of the section.
     *
     * Will only return one section id, so if there are duplicate names it will return false.
     *
     * @param string $name        Object Name
     * @param string $value       Object Value
     * @param string $objectType Object Type, either 'ACO', 'ARO', 'AXO', or 'ACL'
     * @return integer Object Section ID if the object section is found AND is unique, or false otherwise.
     */
    public function getObjectSectionSectionId($name = null, $value = null, $objectType = null)
    {
        $this->debugText("getObjectSectionSectionId(): Value: $value Name: $name Object Type: $objectType");

        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
            case self::TYPE_ARO:
            case self::TYPE_AXO:
            case self::TYPE_ACL:
                $objectType          = strtolower(trim($objectType));
                $table               = $this->dbTablePrefix . $objectType;
                $objectSectionsTable = $this->dbTablePrefix . $objectType . '_sections';
                break;
            default:
                $this->debugText('getObjectSectionSectionId(): Invalid Object Type (' . $objectType . ')');
                return false;
        }

        $name  = trim($name);
        $value = trim($value);

        if (empty($name) and empty($value)) {
            $this->debugText('getObjectSectionSectionId(): Both Name (' . $name . ') and Value (' . $value . ') are empty, you must specify at least one.');
            return false;
        }

        $query = 'SELECT id FROM ' . $objectSectionsTable;
        $where = ' WHERE ';

        // limit by value if specified
        if (!empty($value)) {
            $query .= $where . 'value=' . $this->db->quote($value);
            $where = ' AND ';
        }

        // only use name if asked, this is SLOW
        if (!empty($name)) {
            $query .= $where . 'name=' . $this->db->quote($name);
        }

        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('getObjectSectionSectionId');
            return false;
        }

        $rowCount = $rs->RecordCount();

        // If only one row is returned
        if ($rowCount == 1) {
            // Return only the ID in the first row.
            $row = $rs->FetchRow();
            return $row[0];
        }

        // If more than one row is returned
        // should only ever occur when using name as values are unique.
        if ($rowCount > 1) {
            $this->debugText('getObjectSectionSectionId(): Returned ' . $rowCount . ' rows, can only return one. Please search by value not name, or make your names unique.');
            return false;
        }

        // No rows returned, no matching section found
        $this->debugText('getObjectSectionSectionId(): Returned ' . $rowCount . ' rows, no matching section found.');
        return false;
    }

    /**
     * Inserts an object Section
     *
     * @param string  $name       Object Name
     * @param string  $value      Object Value
     * @param integer $order      Display Order
     * @param integer $hidden     Hidden flag, hides section if 1, shows section if 0
     * @param string $objectType Object Type, either 'ACO', 'ARO', 'AXO', or 'ACL'
     * @return integer Object Section ID of new section
     */
    public function addObjectSection($name, $value = 0, $order = 0, $hidden = 0, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType          = self::TYPE_ACO;
                $objectSectionsTable = $this->dbTablePrefix . 'aco_sections';
                break;
            case self::TYPE_ARO:
                $objectType          = self::TYPE_ARO;
                $objectSectionsTable = $this->dbTablePrefix . 'aro_sections';
                break;
            case self::TYPE_AXO:
                $objectType          = self::TYPE_AXO;
                $objectSectionsTable = $this->dbTablePrefix . 'axo_sections';
                break;
            case self::TYPE_ACL:
                $objectType           = self::TYPE_ACL;
                $objectSectionsTable = $this->dbTablePrefix . 'acl_sections';
                break;
        }

        $this->debugText("addObjectSection(): Value: $value Order: $order Name: $name Object Type: $objectType");

        $name   = trim($name);
        $value  = trim($value);
        $order  = trim($order);
        $hidden = intval($hidden);

        if ($order == null or $order == '') {
            $order = 0;
        }

        if (empty($name)) {
            $this->debugText("addObjectSection(): name ($name) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("addObjectSection(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $insertId = $this->db->GenID($this->dbTablePrefix . $objectType . '_sections_seq', 10);
        $query = 'insert into ' . $objectSectionsTable . ' (id,value,order_value,name,hidden) VALUES( ' . $insertId . ', ' . $this->db->quote($value) . ', ' . $order . ', ' . $this->db->quote($name) . ', ' . $hidden . ')';
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('addObjectSection');
            return false;
        } else {
            $this->debugText("addObjectSection(): Added object_section as ID: $insertId");
            return $insertId;
        }
    }

    /**
     * Edits a given Object Section
     *
     * @param integer $objectSectionId Object Section ID #
     * @param string  $name              Object Section Name
     * @param string  $value             Object Section Value
     * @param integer $order             Display Order
     * @param integer $hidden            Hidden Flag, hide object section if 1, show if 0
     * @param string  $objectType       Object Type, either 'ACO', 'ARO', 'AXO', or 'ACL'
     * @return boolean Returns true if successful, false otherwise
     */
    public function editObjectSection($objectSectionId, $name, $value = 0, $order = 0, $hidden = 0, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType          = self::TYPE_ACO;
                $table               = $this->dbTablePrefix . 'aco';
                $objectSectionsTable = $this->dbTablePrefix . 'aco_sections';
                $objectMapTable      = $this->dbTablePrefix . 'aco_map';
                break;
            case self::TYPE_ARO:
                $objectType          = self::TYPE_ARO;
                $table               = $this->dbTablePrefix . 'aro';
                $objectSectionsTable = $this->dbTablePrefix . 'aro_sections';
                $objectMapTable      = $this->dbTablePrefix . 'aro_map';
                break;
            case self::TYPE_AXO:
                $objectType          = self::TYPE_AXO;
                $table               = $this->dbTablePrefix . 'axo';
                $objectSectionsTable = $this->dbTablePrefix . 'axo_sections';
                $objectMapTable      = $this->dbTablePrefix . 'axo_map';
                break;
            case self::TYPE_ACL:
                $objectType          = self::TYPE_ACL;
                $table               = $this->dbTablePrefix . 'acl';
                $objectSectionsTable = $this->dbTablePrefix . 'acl_sections';
                break;
            default:
                $this->debugText('editObjectSection(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("editObjectSection(): ID: $objectSectionId Value: $value Order: $order Name: $name Object Type: $objectType");

        $name   = trim($name);
        $value  = trim($value);
        $order  = trim($order);
        $hidden = intval($hidden);

        if (empty($objectSectionId)) {
            $this->debugText("editObjectSection(): Section ID ($objectSectionId) is empty, this is required");
            return false;
        }

        if (empty($name)) {
            $this->debugText("editObjectSection(): name ($name) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("editObjectSection(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $this->db->BeginTrans();

        // Get old value incase it changed, before we do the update.
        $query = "select value from $objectSectionsTable where id=$objectSectionId";
        $oldValue = $this->db->GetOne($query);

        $query = "update $objectSectionsTable set
                                value='$value',
                                order_value='$order',
                                name='$name',
                                hidden=$hidden
                          where   id=$objectSectionId";
        $rs = $this->db->Execute($query);

        if (!is_object($rs)) {
            $this->debugDb('editObjectSection');

            $this->db->RollbackTrans();

            return false;
        } else {
            $this->debugText("editObjectSection(): Modified aco_section ID: $objectSectionId");

            if ($oldValue != $value) {
                $this->debugText("editObjectSection(): Value Changed, update other tables.");

                $query = "update $table set
                                section_value='$value'
                          where section_value = '$oldValue'";
                $rs = $this->db->Execute($query);

                if (!is_object($rs)) {
                    $this->debugDb('editObjectSection');

                    $this->db->RollbackTrans();

                    return false;
                } else {
                    if (!empty($objectMapTable)) {
                        $query = "update $objectMapTable set
                                    section_value='$value'
                              where section_value = '$oldValue'";
                        $rs = $this->db->Execute($query);

                        if (!is_object($rs)) {
                            $this->debugDb('editObjectSection');

                            $this->db->RollbackTrans();

                            return false;
                        } else {
                            $this->debugText("editObjectSection(): Modified ojbect_map value: $value");

                            $this->db->CommitTrans();
                            return true;
                        }
                    } else {
                        // ACL sections, have no mapping table. Return true.

                        $this->db->CommitTrans();

                        return true;
                    }
                }
            }

            $this->db->CommitTrans();
            return true;
        }
    }

    /**
     * Deletes a given Object Section and, if explicitly asked, all the section objects
     *
     * ERASE feature by: Martino Piccinato
     *
     * @param integer $objectSectionId Object Section ID # to delete
     * @param string  $objectType Object Type, either 'ACO', 'ARO', 'AXO', or 'ACL'
     * @param boolean $erase Erases all section objects assigned to the section
     * @return boolean Returns true if successful, false otherwise
     */
    public function delObjectSection($objectSectionId, $objectType = null, $erase = false)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType          = self::TYPE_ACO;
                $objectSectionsTable = $this->dbTablePrefix . 'aco_sections';
                break;
            case self::TYPE_ARO:
                $objectType          = self::TYPE_ARO;
                $objectSectionsTable = $this->dbTablePrefix . 'aro_sections';
                break;
            case self::TYPE_AXO:
                $objectType          = self::TYPE_AXO;
                $objectSectionsTable = $this->dbTablePrefix . 'axo_sections';
                break;
            case self::TYPE_ACL:
                $objectType          = self::TYPE_ACL;
                $objectSectionsTable = $this->dbTablePrefix . 'acl_sections';
                break;
        }

        $this->debugText("delObjectSection(): ID: $objectSectionId Object Type: $objectType, Erase all: $erase");

        if (empty($objectSectionId)) {
            $this->debugText("delObjectSection(): Section ID ($objectSectionId) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("delObjectSection(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        // Get the value of the section
        $query = "SELECT value FROM $objectSectionsTable WHERE id='$objectSectionId'";
        $sectionValue = $this->db->GetOne($query);

        // Get all objects ids in the section
        $objectIds = $this->getObject($sectionValue, 1, $objectType);

        if ($erase) {
            // Delete all objects in the section and for
            // each object delete the referencing object
            // (see delObject method)
            if (is_array($objectIds)) {
                foreach ($objectIds as $id) {
                    if ($objectType === self::TYPE_ACL) {
                        $this->delAcl($id);
                    } else {
                        $this->delObject($id, $objectType, true);
                    }
                }
            }
        }

        if ($objectIds and !$erase) {
            // There are objects in the section and we
            // were not asked to erase them: don't delete it

            $this->debugText("delObjectSection(): Could not delete the section ($sectionValue) as it is not empty.");

            return false;
        } else {
            // The section is empty (or emptied by this method)

            $query = "DELETE FROM $objectSectionsTable where id='$objectSectionId'";
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('delObjectSection');
                return false;
            } else {
                $this->debugText("delObjectSection(): deleted section ID: $objectSectionId Value: $sectionValue");
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the section data given the Section Value
     *
     * @param string $sectionValue Section Value
     * @param string $objectType   Object Type, either 'ACO', 'ARO', or 'AXO'
     * @return array Returns numerically indexed array with the following columns:
     * - array[0] = (int) Section ID #
     * - array[1] = (string) Section Value
     * - array[2] = (int) Section Order
     * - array[3] = (string) Section Name
     * - array[4] = (int) Section Hidden?
     */
    public function getSectionData($sectionValue, $objectType = null)
    {
        switch (strtolower(trim($objectType))) {
            case self::TYPE_ACO:
                $objectType = self::TYPE_ACO;
                $table      = $this->dbTablePrefix . 'aco_sections';
                break;
            case self::TYPE_ARO:
                $objectType = self::TYPE_ARO;
                $table      = $this->dbTablePrefix . 'aro_sections';
                break;
            case self::TYPE_AXO:
                $objectType = self::TYPE_AXO;
                $table      = $this->dbTablePrefix . 'axo_sections';
                break;
            default:
                $this->debugText('getSectionData(): Invalid Object Type: ' . $objectType);
                return false;
        }

        $this->debugText("getSectionData(): Section Value: $sectionValue Object Type: $objectType");

        if (empty($sectionValue)) {
            $this->debugText("getSectionData(): Section Value ($sectionValue) is empty, this is required");
            return false;
        }

        if (empty($objectType)) {
            $this->debugText("getSectionData(): Object Type ($objectType) is empty, this is required");
            return false;
        }

        $query = "SELECT id, value, order_value, name, hidden FROM '. $table .' WHERE value='$sectionValue'";
        $row = $this->db->GetRow($query);

        if ($row) {
            return $row;
        }

        $this->debugText("getSectionData(): Section does not exist.");
        return false;
    }

    /**
     * Deletes all data from the phpGACL tables. USE WITH CAUTION.
     *
     * @return boolean Returns true if successful, false otherwise
     */
    public function clearDatabase()
    {
        $tablesToClear = [
            $this->dbTablePrefix . 'acl',
            $this->dbTablePrefix . 'aco',
            $this->dbTablePrefix . 'aco_map',
            $this->dbTablePrefix . 'aco_sections',
            $this->dbTablePrefix . 'aro',
            $this->dbTablePrefix . 'aro_groups',
            $this->dbTablePrefix . 'aro_groups_map',
            $this->dbTablePrefix . 'aro_map',
            $this->dbTablePrefix . 'aro_sections',
            $this->dbTablePrefix . 'axo',
            $this->dbTablePrefix . 'axo_groups',
            $this->dbTablePrefix . 'axo_groups_map',
            $this->dbTablePrefix . 'axo_map',
            $this->dbTablePrefix . 'axo_sections',
            $this->dbTablePrefix . 'groups_aro_map',
            $this->dbTablePrefix . 'groups_axo_map'
        ];

        // Get all the table names and loop
        $tableNames = $this->db->MetaTables('TABLES');
        $query = [];
        foreach ($tableNames as $key => $value) {
            if (in_array($value, $tablesToClear)) {
                $query[] = 'TRUNCATE TABLE ' . $value . ';';
            }
        }

        // Loop the queries and return.
        foreach ($query as $key => $value) {
            $result = $this->db->Execute($value);
        }

        return true;
    }
}
