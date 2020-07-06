<?php
// $Id: gacl.class.php 422 2006-09-03 22:52:20Z ipso $

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
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
 */

namespace Higis\PhpGacl;

/**
 * phpGACL main class
 *
 * Class gacl should be used in applications where only querying the phpGACL
 * database is required.
 *
 * @package phpGACL
 * @author Mike Benoit <ipso@snappymail.ca>
 */
class Gacl
{

    const TYPE_ACO = 'aco';

    const TYPE_AXO = 'axo';

    const TYPE_ARO = 'aro';

    const TYPE_ACL = 'acl';

    /*
     * --- phpGACL Configuration path/file ---
     */
    public $config_file = './gacl.ini.php';

    /*
     * --- Private properties ---
     */
    /** @var boolean Enables Debug output if true */
    public $debug = false;

    /*
     * --- Database configuration. ---
     */
    /** @var string Prefix for all the phpgacl tables in the database */
    public $dbTablePrefix = '';

    /** @var string The database type, based on available ADODB connectors - mysql, postgres7, sybase, oci8po See here for more: http://php.weblogs.com/adodb_manual#driverguide */
    public $dbType = 'mysql';

    /** @var string The database server */
    public $dbHost = 'localhost';

    /** @var string The database user name */
    public $dbUser = 'root';

    /** @var string The database user password */
    public $dbPassword = '';

    /** @var string The database name */
    public $dbName = 'gacl';

    /** @var object An ADODB database connector object */
    public $conn = '';

    /** @var object An ADODB database connector object */
    public $db;

    /*
     * NOTE: This cache must be manually cleaned each time ACL's are modified.
     * Alternatively you could wait for the cache to expire.
     */

    /** @var boolean Caches queries if true */
    public $caching = false;

    /** @var boolean Force cache to expire */
    public $forceCacheExpire = true;

    /** @var string The directory for cache file to eb written (ensure write permission are set) */
    public $cacheDir = '/tmp/phpgacl_cache';

    // NO trailing slash

    /** @var int The time for the cache to expire in seconds - 600 == Ten Minutes */
    public $cacheExpireTime = 600;

    /** @var string A switch to put acl_check into '_group_' mode */
    public $groupSwitch = '_group_';

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger = null;

    /**
     *
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    public $cache = null;

    /**
     * Constructor
     *
     * @param array $options An array of options to oeverride the class defaults
     */
    public function __construct($options = null)
    {
        $available_options = [
            'db',
            'debug',
            'items_per_page',
            'max_select_box_items',
            'max_search_return_items',
            'db_table_prefix',
            'db_type',
            'db_host',
            'db_user',
            'db_password',
            'db_name',
            'caching',
            'force_cache_expire',
            'cache_dir',
            'cache_expire_time',
            'logger',
            'caching'
        ];

        // Values supplied in $options array overwrite those in the config file.
        if (file_exists($this->config_file)) {
            $config = parse_ini_file($this->config_file);

            if (is_array($config)) {
                $gacl_options = array_merge($config, $options);
            }

            unset($config);
        }

        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $this->debugText("Option: $key", \Psr\Log\LogLevel::INFO);

                if (in_array($key, $available_options)) {
                    $this->debugText("Valid Config options: $key", \Psr\Log\LogLevel::INFO);
                    $property = $key;
                    $this->$property = $value;
                } else {
                    $this->debugText("ERROR: Config option: $key is not a valid option", \Psr\Log\LogLevel::ERROR);
                }
            }
        }

        if (is_object($this->conn)) {
            $this->db = &$this->conn;
        } else {
            $this->db = ADONewConnection($this->dbType);
            // Use NUM for slight performance/memory reasons.
            $this->db->SetFetchMode(ADODB_FETCH_NUM);
            $this->db->PConnect($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        }
        $this->db->debug = $this->debug;

        if (!empty($this->logger) && !in_array('Psr\Log\LoggerInterface', class_implements($this->logger))) {
            $this->logger = null;
        }

        if (!empty($this->cache) && !in_array('Psr\Cache\CacheItemPoolInterface', class_implements($this->cache))) {
            $this->cache = null;
        }

        if ($this->caching == true) {
            if (!class_exists('Hashed_Cache_Lite')) {
                require_once(dirname(__FILE__) . '/Cache_Lite/Hashed_Cache_Lite.php');
            }

            /*
             * Cache options. We default to the highest performance. If you run in to cache corruption problems,
             * Change all the 'false' to 'true', this will slow things down slightly however.
             */

            $cache_options = [
                'caching'                => $this->caching,
                'cacheDir'               => $this->cacheDir . '/',
                'lifeTime'               => $this->cacheExpireTime,
                'fileLocking'            => true,
                'writeControl'           => false,
                'readControl'            => false,
                'memoryCaching'          => true,
                'automaticSerialization' => false
            ];
            $this->Cache_Lite = new Hashed_Cache_Lite($cache_options);
        }

        return true;
    }

    public function __call($name, $arguments)
    {
        $camelCase = $this->toCamelCase($name);

        if (method_exists($this, $camelCase)) {
            return call_user_func_array([$this, $camelCase], $arguments);
        }

        throw new \Exception('Method ' . $name . ' does not exist.');
    }

    public function __get($name)
    {
        $camelCase = $this->toCamelCase($name);

        if (isset($this->$camelCase)) {
            return $this->$camelCase;
        }

        throw new \Exception('Variable ' . $name . ' does not exist.');
    }

    public function __set($name, $value)
    {
        $camelCase = $this->toCamelCase($name);
        if (isset($this->$camelCase)) {
            $this->$camelCase = $value;

            return $this;
        }

        throw new \Exception('Variable ' . $name . ' does not exist.');
    }

    /**
     * Prints debug text if debug is enabled.
     *
     * @param string $text THe text to output
     * @return boolean Always returns true
     */
    public function debugText($text, $level = \Psr\Log\LogLevel::INFO)
    {
        if (!empty($this->logger)) {
            $this->logger->log($level, $text);
        } else if ($this->debug) {
            echo "$text<br>\n";
        }

        return true;
    }

    /**
     * Prints database debug text if debug is enabled.
     *
     * @param string $functionName The name of the function calling this method
     * @return string Returns an error message
     */
    public function debugDb($functionName = '')
    {
        if ($functionName != '') {
            $functionName .= ' (): ';
        }

        return $this->debugText(
            $functionName . 'database error: ' . $this->db->ErrorMsg() . ' (' . $this->db->ErrorNo() . ')',
            \Psr\Log\LogLevel::ERROR
        );
    }

    /**
     * Wraps the actual acl_query() function.
     *
     * It is simply here to return true/false accordingly.
     *
     * @param string  $acoSectionValue The ACO section value
     * @param string  $acoValue The ACO value
     * @param string  $aroSectionValue The ARO section value
     * @param string  $aroValue The ARO section
     * @param string  $axoSectionValue The AXO section value (optional)
     * @param string  $axoValue The AXO section value (optional)
     * @param integer $rootAroGroup The group id of the ARO ??Mike?? (optional)
     * @param integer $rootAxoGroup The group id of the AXO ??Mike?? (optional)
     * @return boolean true if the check succeeds, false if not.
     */
    public function aclCheck(
        $acoSectionValue,
        $acoValue,
        $aroSectionValue,
        $aroValue,
        $axoSectionValue = null,
        $axoValue = null,
        $rootAroGroup = null,
        $rootAxoGroup = null
    ) {
        $aclResult = $this->aclQuery(
            $acoSectionValue,
            $acoValue,
            $aroSectionValue,
            $aroValue,
            $axoSectionValue,
            $axoValue,
            $rootAroGroup,
            $rootAxoGroup
        );

        return $aclResult['allow'];
    }

    /**
     * Wraps the actual acl_query() function.
     *
     * Quick access to the return value of an ACL.
     *
     * @param string  $acoSectionValue The ACO section value
     * @param string  $acoValue The ACO value
     * @param string  $aroSectionValue The ARO section value
     * @param string  $aroValue The ARO section
     * @param string  $axoSectionValue The AXO section value (optional)
     * @param string  $axoValue The AXO section value (optional)
     * @param integer $rootAroGroup The group id of the ARO (optional)
     * @param integer $rootAxoGroup The group id of the AXO (optional)
     * @return string The return value of the ACL
     */
    public function aclReturnValue(
        $acoSectionValue,
        $acoValue,
        $aroSectionValue,
        $aroValue,
        $axoSectionValue = null,
        $axoValue = null,
        $rootAroGroup = null,
        $rootAxoGroup = null
    ) {
        $aclResult = $this->aclQuery(
            $acoSectionValue,
            $acoValue,
            $aroSectionValue,
            $aroValue,
            $axoSectionValue,
            $axoValue,
            $rootAroGroup,
            $rootAxoGroup
        );

        return $aclResult['return_value'];
    }

    /**
     * Handles ACL lookups over arrays of AROs
     *
     * @param string $acoSectionValue The ACO section value
     * @param string $acoValue  The ACO value
     * @param array  $aroArray An named array of arrays,
     *                         each element in the format aro_section_value=>array(aro_value1,aro_value1,...)
     * @return mixed The same data format as inputted.
     *         \*======================================================================
     */
    public function aclCheckArray($acoSectionValue, $acoValue, $aroArray)
    {
        /*
         * Input Array:
         * Section => array(Value, Value, Value),
         * Section => array(Value, Value, Value)
         *
         */
        if (!is_array($aroArray)) {
            $this->debugText("acl_query_array(): ARO Array must be passed", \Psr\Log\LogLevel::ERROR);
            return false;
        }

        foreach ($aroArray as $aroSectionValue => $aroValueArray) {
            foreach ($aroValueArray as $aroValue) {
                $this->debugText(
                    "acl_query_array(): ARO Section Value: $aroSectionValue ARO VALUE: $aroValue",
                    \Psr\Log\LogLevel::INFO
                );

                if ($this->aclCheck($acoSectionValue, $acoValue, $aroSectionValue, $aroValue)) {
                    $this->debugText("acl_query_array(): ACL_CHECK True", \Psr\Log\LogLevel::INFO);
                    $retarr[$aroSectionValue][] = $aroValue;
                } else {
                    $this->debugText("acl_query_array(): ACL_CHECK False", \Psr\Log\LogLevel::INFO);
                }
            }
        }

        return $retarr;
    }

    /**
     * The Main function that does the actual ACL lookup.
     *
     * @param string  $acoSectionValue The ACO section value
     * @param string  $acoValue The ACO value
     * @param string  $aroSectionValue The ARO section value
     * @param string  $aroValue The ARO section
     * @param string  $axoSectionValue The AXO section value (optional)
     * @param string  $axoValue The AXO section value (optional)
     * @param string  $rootAroGroup The value of the ARO group (optional)
     * @param string  $rootAxoGroup The value of the AXO group (optional)
     * @param boolean $debug Debug the operation if true (optional)
     * @return array Returns as much information as possible about the ACL
     *               so other functions can trim it down and omit unwanted data.
     */
    public function aclQuery(
        $acoSectionValue,
        $acoValue,
        $aroSectionValue,
        $aroValue,
        $axoSectionValue = null,
        $axoValue = null,
        $rootAroGroup = null,
        $rootAxoGroup = null,
        $debug = null
    ) {
        $cacheId = 'acl_query_'
        . implode(
            '_',
            [
                $acoSectionValue, $acoValue, $aroSectionValue, $aroValue, $axoSectionValue, $axoValue,
                $rootAroGroup, $rootAxoGroup, $debug
            ]
        );

            $retarr = $this->getCache($cacheId);

        if (!$retarr) {
            /*
             * Grab all groups mapped to this ARO/AXO
             */
            $aroGroupIds = $this->aclGetGroups($aroSectionValue, $aroValue, $rootAroGroup, strtoupper(self::TYPE_ARO));

            if (is_array($aroGroupIds) and !empty($aroGroupIds)) {
                $sqlAroGroupIds = implode(',', $aroGroupIds);
            }

            if ($axoSectionValue != '' and $axoValue != '') {
                $axoGroupIds = $this->aclGetGroups($axoSectionValue, $axoValue, $rootAxoGroup, strtoupper(self::TYPE_AXO));

                if (is_array($axoGroupIds) and !empty($axoGroupIds)) {
                    $sqlAxoGroupIds = implode(',', $axoGroupIds);
                }
            }

            /*
             * This query is where all the magic happens.
             * The ordering is very important here, as well very tricky to get correct.
             * Currently there can be duplicate ACLs, or ones that step on each other toes.
             * In this case, the ACL that was last updated/created is used.
             *
             * This is probably where the most optimizations can be made.
             */

            $orderBy = [];

            $query = '
          SELECT		a.id,a.allow,a.return_value
          FROM		' . $this->dbTablePrefix . 'acl a
          LEFT JOIN 	' . $this->dbTablePrefix . 'aco_map ac ON ac.acl_id=a.id';

            if ($aroSectionValue != $this->groupSwitch) {
                $query .= '
          LEFT JOIN	' . $this->dbTablePrefix . 'aro_map ar ON ar.acl_id=a.id';
            }

            if ($axoSectionValue != $this->groupSwitch) {
                $query .= '
          LEFT JOIN	' . $this->dbTablePrefix . 'axo_map ax ON ax.acl_id=a.id';
            }

            /*
             * if there are no aro groups, don't bother doing the join.
             */
            if (isset($sqlAroGroupIds)) {
                $query .= '
          LEFT JOIN	' . $this->dbTablePrefix . 'aro_groups_map arg ON arg.acl_id=a.id
          LEFT JOIN	' . $this->dbTablePrefix . 'aro_groups rg ON rg.id=arg.group_id';
            }

            // this join is necessary to weed out rules associated with axo groups
            $query .= '
          LEFT JOIN	' . $this->dbTablePrefix . 'axo_groups_map axg ON axg.acl_id=a.id';

            /*
             * if there are no axo groups, don't bother doing the join.
             * it is only used to rank by the level of the group.
             */
            if (isset($sqlAxoGroupIds)) {
                $query .= '
          LEFT JOIN	' . $this->dbTablePrefix . 'axo_groups xg ON xg.id=axg.group_id';
            }

            // Move the below line to the LEFT JOIN above for PostgreSQL's sake.
            // AND ac.acl_id=a.id
            $query .= '
            WHERE		a.enabled=1
            AND (ac.section_value = ' . $this->db->quote($acoSectionValue)
            . ' AND ac.value=' . $this->db->quote($acoValue)
            . ')';

            // if we are querying an aro group
            if ($aroSectionValue == $this->groupSwitch) {
                // if aclGetGroups did not return an array
                if (!isset($sqlAroGroupIds)) {
                    $this->debugText('acl_query(): Invalid ARO Group: ' . $aroValue, \Psr\Log\LogLevel::ERROR);
                    return false;
                }

                $query .= '
            AND		rg.id IN (' . $sqlAroGroupIds . ')';

                $orderBy[] = '(rg.rgt-rg.lft) ASC';
            } else {
                $query .= '
                AND	((ar.section_value = ' . $this->db->quote($aroSectionValue)
                . ' AND ar.value = ' . $this->db->quote($aroValue)
                . ')';

                if (isset($sqlAroGroupIds)) {
                    $query .= ' OR rg.id IN (' . $sqlAroGroupIds . ')';

                    $orderBy[] = '(CASE WHEN ar.value IS NULL THEN 0 ELSE 1 END) DESC';
                    $orderBy[] = '(rg.rgt-rg.lft) ASC';
                }

                $query .= ')';
            }

            // if we are querying an axo group
            if ($axoSectionValue == $this->groupSwitch) {
                // if aclGetGroups did not return an array
                if (!isset($sqlAxoGroupIds)) {
                    $this->debugText('acl_query(): Invalid AXO Group: ' . $axoValue, \Psr\Log\LogLevel::ERROR);
                    return false;
                }

                $query .= '
            AND		xg.id IN (' . $sqlAxoGroupIds . ')';

                $orderBy[] = '(xg.rgt-xg.lft) ASC';
            } else {
                $query .= '
            AND		(';

                if ($axoSectionValue == '' and $axoValue == '') {
                    $query .= '(ax.section_value IS NULL AND ax.value IS NULL)';
                } else {
                    $query .= '(ax.section_value = ' . $this->db->quote($axoSectionValue)
                    . ' AND ax.value = ' . $this->db->quote($axoValue)
                    . ')';
                }

                if (isset($sqlAxoGroupIds)) {
                    $query .= ' OR xg.id IN (' . $sqlAxoGroupIds . ')';

                    $orderBy[] = '(CASE WHEN ax.value IS NULL THEN 0 ELSE 1 END) DESC';
                    $orderBy[] = '(xg.rgt-xg.lft) ASC';
                } else {
                    $query .= ' AND axg.group_id IS NULL';
                }

                $query .= ')';
            }

            /*
             * The ordering is always very tricky and makes all the difference in the world.
             * Order (ar.value IS NOT NULL) DESC should put ACLs given to specific AROs
             * ahead of any ACLs given to groups. This works well for exceptions to groups.
             */

            $orderBy[] = 'a.updated_date DESC';

            $query .= '
          ORDER BY	' . implode(',', $orderBy) . '
          ';

            // we are only interested in the first row
            $rs = $this->db->SelectLimit($query, 1);

            if (!is_object($rs)) {
                $this->debugDb('acl_query');
                return false;
            }

            $row = $rs->FetchRow();

            /*
             * Return ACL ID. This is the key to "hooking" extras like pricing assigned to ACLs etc... Very useful.
             */
            if (is_array($row)) {
                // Permission granted?
                // This below oneliner is very confusing.
                // $allow = (isset($row[1]) AND $row[1] == 1);

                // Prefer this.
                if (isset($row[1]) and $row[1] == 1) {
                    $allow = true;
                } else {
                    $allow = false;
                }

                $retarr = [
                    'acl_id'       => &$row[0],
                    'return_value' => &$row[2],
                    'allow'        => $allow
                ];
            } else {
                // Permission denied.
                $retarr = [
                    'acl_id'       => null,
                    'return_value' => null,
                    'allow'        => false
                ];
            }

            /*
             * Return the query that we ran if in debug mode.
             */
            if ($debug == true) {
                $retarr['query'] = &$query;
            }

            // Cache data.
            $this->putCache($retarr, $cacheId);
        }
        $this->debugText(
            '<b>acl_query():</b> ACO Section: ' . $acoSectionValue . ' ACO Value: ' . $acoValue
            . ' ARO Section: ' . $aroSectionValue . ' ARO Value ' . $aroValue
            . ' ACL ID: ' . $retarr['acl_id']
            . ' Result: ' . $retarr['allow'],
            \Psr\Log\LogLevel::INFO
        );
        return $retarr;
    }

    /**
     * Grabs all groups mapped to an ARO.
     * You can also specify a root_group for subtree'ing.
     *
     * @param string $sectionValue The section value or the ARO or ACO
     * @param string $value The value of the ARO or ACO
     * @param integer $rootGroup The group id of the group to start at (optional)
     * @param string $groupType The type of group, either ARO or AXO (optional)
     * @return array
     */
    public function aclGetGroups($sectionValue, $value, $rootGroup = null, $groupType = self::TYPE_ARO)
    {
        switch (strtolower($groupType)) {
            case self::TYPE_AXO:
                $groupType      = self::TYPE_AXO;
                $objectTable    = $this->dbTablePrefix . 'axo';
                $groupTable     = $this->dbTablePrefix . 'axo_groups';
                $groupMapTable = $this->dbTablePrefix . 'groups_axo_map';
                break;
            default:
                $groupType      = self::TYPE_ARO;
                $objectTable    = $this->dbTablePrefix . 'aro';
                $groupTable     = $this->dbTablePrefix . 'aro_groups';
                $groupMapTable = $this->dbTablePrefix . 'groups_aro_map';
                break;
        }

        // $profiler->startTimer( "aclGetGroups()");

        // Generate unique cache id.
        $cacheId = 'aclGetGroups_' . implode('_', [$sectionValue, $value, $rootGroup, $groupType]);

        $retarr = $this->getCache($cacheId);

        if (!$retarr) {
            // Make sure we get the groups
            $query = '
          SELECT 		DISTINCT g2.id';

            if ($sectionValue == $this->groupSwitch) {
                $query .= '
          FROM		' . $groupTable . ' g1,' . $groupTable . ' g2';

                $where = '
          WHERE		g1.value=' . $this->db->quote($value);
            } else {
                $query .= '
          FROM		' . $objectTable . ' o,' . $groupMapTable . ' gm,' . $groupTable . ' g1,' . $groupTable . ' g2';

                $where = '
          WHERE	(o.section_value=' . $this->db->quote($sectionValue) . ' AND o.value=' . $this->db->quote($value) . ')
            AND	gm.' . $groupType . '_id=o.id
            AND	g1.id=gm.group_id';
            }

            /*
             * If root_group_id is specified, we have to narrow this query down
             * to just groups deeper in the tree then what is specified.
             * This essentially creates a virtual "subtree" and ignores all outside groups.
             * Useful for sites like sourceforge where you may seperate groups by "project".
             */
            if ($rootGroup != '') {
                // It is important to note the below line modifies the tables being selected.
                // This is the reason for the WHERE variable.
                $query .= ',' . $groupTable . ' g3';

                $where .= '
            AND		g3.value=' . $this->db->quote($rootGroup) . '
            AND		((g2.lft BETWEEN g3.lft AND g1.lft) AND (g2.rgt BETWEEN g1.rgt AND g3.rgt))';
            } else {
                $where .= '
            AND		(g2.lft <= g1.lft AND g2.rgt >= g1.rgt)';
            }

            $query .= $where;

            // $this->debugText($query);
            $rs = $this->db->Execute($query);

            if (!is_object($rs)) {
                $this->debugDb('aclGetGroups');
                return false;
            }

            $retarr = [];

            // Unbuffered query?
            while (!$rs->EOF) {
                $retarr[] = reset($rs->fields);
                $rs->MoveNext();
            }

            // Cache data.
            $this->putCache($retarr, $cacheId);
        }

        return $retarr;
    }

    /**
     * Uses PEAR's Cache_Lite package to grab cached arrays, objects, variables etc...
     * using unserialize() so it can handle more then just text string.
     *
     * @param string $cacheId The id of the cached object
     * @return mixed The cached object, otherwise false if the object identifier was not found
     */
    public function getCache($cacheId)
    {
        if ($this->caching == true) {
            $this->debugText("getCache(): on ID: $cacheId", \Psr\Log\LogLevel::INFO);

            if (!is_null($this->cache)) {
                $item = $this->cache->getItem($cacheId);

                if ($item->isHit()) {
                    return $item->get();
                } else {
                    return false;
                }
            } else {
                if (is_string($this->Cache_Lite->get($cacheId))) {
                    return unserialize($this->Cache_Lite->get($cacheId));
                }
            }
        }

        return false;
    }

    /**
     * Uses PEAR's Cache_Lite package to write cached arrays, objects, variables etc...
     * using serialize() so it can handle more then just text string.
     *
     * @param mixed  $data A variable to cache
     * @param string $cacheId The id of the cached variable
     * @return boolean
     */
    public function putCache($data, $cacheId)
    {
        if ($this->caching == true) {
            $this->debugText("putCache(): Cache MISS on ID: $cacheId", \Psr\Log\LogLevel::INFO);

            if (!is_null($this->cache)) {
                $item = $this->cache->getItem($cacheId);
                $item->set($data);
                $item->save();
            } else {
                return $this->Cache_Lite->save(serialize($data), $cacheId);
            }
        }

        return false;
    }

    /**
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param    string   $str                     String in underscore format
     * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
     * @return   string                              $str translated into camel caps
     */
    protected function toCamelCase($str, $firstCharUpper = false)
    {

        if ($str[0] == '_') {
            $str = substr($str, 1);
        }

        if ($firstCharUpper) {
            $str[0] = strtoupper($str[0]);
        }
        // $func = create_function('$char', 'return strtoupper($char[1]);');
        $func = function ($char) {
            return strtoupper($char[1]);
        };

        return preg_replace_callback('/_([a-z])/', $func, $str);
    }
}
