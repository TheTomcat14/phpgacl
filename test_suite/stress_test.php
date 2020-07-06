<?php
/**
 * Short description for file
 *
 * Long description (if any) ...
 *
 * PHP version unknown
 *
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * + Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * + Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 * + Neither the name of the <ORGANIZATION> nor the names of its contributors
 * may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  CategoryName
 * @package   PackageName
 * @author    Author's name <author@mail.com>
 * @copyright 2020 Author's name
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   CVS: $Id:$
 * @link      http://pear.php.net/package/PackageName
 * @see       References to other sections (if any)...
 */
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <style>
    <?php echo file_get_contents(__DIR__ . '/../admin/bootstrap.min.css'); ?>
    </style>
    <title>Stress Test</title>
</head>
<body>
<div class="container">
<pre>
<?php
/*! var
 test scale

 This script will create:
 $scale * 10   ACOs
 $scale * 10   ARO groups
 $scale * 1000 AROs
 $scale * 10   AXO groups
 $scale * 1000 AXOs
 $scale * 10   ACLs

 1        normal    ~5 seconds
 10        heavy    ~1 minute
 100    crazy    ~1 hour
 !*/
$scale = 10;

set_time_limit(6000);

/**
 * Get time accurate to the nearest microsecond, used for script timing
 *
 * @return float Return description (if any) ...
 */
function getmicrotime()
{
    list ($usec, $sec) = explode(' ', microtime());
    return (float)$usec + (float)$sec;
}

/**
 * A better array_rand, this one actually works on windows
 *
 * @param array   $array Parameter description (if any) ...
 * @param integer $items Parameter description (if any) ...
 *
 * @return mixed   Return description (if any) ...
 */
function arrayMtRand($array, $items)
{
    $keys = array_keys($array);
    $max = count($keys) - 1;

    if ($items == 1) {
        return $keys[mt_rand(0, $max)];
    }

    $return = [];

    for ($i = 1; $i <= $items; $i++) {
        $return[] = $keys[mt_rand(0, $max)];
    }

    return $return;
}

// require gacl

/**
 * Description for require_once
 */
require_once dirname(__FILE__) . '/../admin/gacl_admin.inc.php';

/*
 * Let's get ready to RUMBLE!!!
 */

echo '<strong>Stress Test</strong>' . PHP_EOL;
echo '    Scale: ' . $scale . PHP_EOL . PHP_EOL;

$overallStart = getmicrotime();

mt_srand((double)microtime() * 10000);

$gaclApi->addObjectSection('System', 'system', 0, 0, 'ACO');

echo "<strong>Create ACOs</strong>\n";
flush();

$startTime = getmicrotime();

$start = 1;
$max = 10 * $scale;
for ($i = $start; $i <= $max; $i++) {
    if ($gaclApi->addObject('system', 'ACO: ' . $i, $i, 10, 0, 'ACO') == false) {
        echo "    Error creating ACO: $i.\n";
        echo '    ' . $gaclApi->_debug_msg . "\n";
    }
}

$elapsed = getmicrotime() - $startTime;

echo "Done\n";
echo '    Count:   ' . $max . "\n";
echo '    Time:    ' . $elapsed . " s\n";
echo '    Average: ' . $elapsed / $max . " s\n\n";



$gaclApi->addObjectSection('Users', 'users', 0, 0, 'ARO');

echo "<strong>Create many ARO Groups.</strong>\n";
flush();

$startTime = getmicrotime();

$query = 'SELECT id FROM ' . $gaclApi->dbTablePrefix . 'aro_groups';
$ids = $gaclApi->db->GetCol($query);

// print_r ($ids);

$start = 1;
$max = 10 * $scale;

// function addGroup ($name, $parentId=0, $groupType='ARO') {
for ($i = $start; $i <= $max; $i++) {
    // Find a random parent
    if (!empty($ids)) {
        $parentId = $ids[arrayMtRand($ids, 1)];
    } else {
        $parentId = 0;
    }

    $result = $gaclApi->addGroup('aro_group'.$i, 'ARO Group: '. $i, $parentId, 'ARO');

    if ($result == false) {
        echo "    Error creating ARO Group: $i.\n";
        echo '    ' . $gaclApi->_debug_msg . "\n";
    } else {
        $ids[] = $result;
    }
}

$elapsed = getmicrotime() - $startTime;

echo "Done\n";
echo '    Count:   ' . $max . PHP_EOL;
echo '    Time:    ' . $elapsed . PHP_EOL;
echo '    Average: ' . $elapsed/$max . " s" . PHP_EOL . PHP_EOL;



echo "<strong>Create AROs & assign to ARO Groups</strong>" . PHP_EOL;
flush();

$startTime = getmicrotime();

$start = 1;
$max = 1000 * $scale;

$groups = array_keys($gaclApi->formatGroups($gaclApi->sortGroups('ARO'), 'ARRAY'));
$randmax = count($groups) - 1;

for ($i = $start; $i <= $max; $i++) {
    if ($gaclApi->addObject('users', 'ARO: '. $i, $i, 10, 0, 'ARO') == false) {
        echo "    Error creating ARO: $i.<br />\n";
        echo '    ' . $gaclApi->_debug_msg . "\n";
    } else {
        // Assign to random groups.
        $randKey = $groups[mt_rand(0, $randmax)];
        $gaclApi->addGroupObject($randKey, 'users', $i, 'ARO');
    }
}

$elapsed = getmicrotime() - $startTime;

echo "Done\n";
echo '    Count:   ' . $max . "\n";
echo '    Time:    ' . $elapsed . " s\n";
echo '    Average: ' . $elapsed / $max . " s\n\n";

$gaclApi->addObjectSection('Users', 'users', 0, 0, 'AXO');

echo "<strong>Create many AXO Groups.</strong>\n";
flush();

$startTime = getmicrotime();

$query = 'SELECT id FROM ' . $gaclApi->dbTablePrefix . 'axo_groups';
$ids = $gaclApi->db->GetCol($query);

$start = 1;
$max = 10 * $scale;

// function addGroup ($name, $parentId=0, $groupType='ARO') {
for ($i = $start; $i <= $max; $i++) {
    // Find a random parent
    if (!empty($ids)) {
        $parentId = $ids[arrayMtRand($ids, 1)];
    } else {
        $parentId = 0;
    }

    $result = $gaclApi->addGroup('axo_group' . $i, 'AXO Group: '. $i, $parentId, 'AXO');
    if ($result == false) {
        echo "    Error creating AXO Group: $i" . PHP_EOL;
        echo '    ' . $gaclApi->_debug_msg . PHP_EOL;
    } else {
        $ids[] = $result;
    }
}

$elapsed = getmicrotime() - $startTime;

echo "Done\n";
echo '    Count:   ' . $max . "\n";
echo '    Time:    ' . $elapsed . " s\n";
echo '    Average: ' . $elapsed/$max . " s\n\n";



echo "<strong>Create AXOs & assign to AXO Groups</strong>\n";
flush();

$startTime = getmicrotime();

$start = 1;
$max = 1000 * $scale;

$groups = array_keys ($gaclApi->formatGroups($gaclApi->sortGroups ('AXO'), 'ARRAY'));
$randMax = count($groups) - 1;

for ($i = $start; $i <= $max; $i++) {
    if ($gaclApi->addObject('users', 'AXO: ' . $i, $i, 10, 0, 'AXO') == false) {
        echo "    Error creating ARO: $i.<br />\n";
        echo '    ' . $gaclApi->_debug_msg . "\n";
    } else {
        // Assign to random groups.
        $randKey = $groups[mt_rand(0, $randMax)];
        $gaclApi->addGroupObject($randKey, 'users', $i, 'AXO');
    }
}

$elapsed = getmicrotime() - $startTime;

echo "Done\n";
echo '    Count:   ' . $max . "\n";
echo '    Time:    ' . $elapsed . " s\n";
echo '    Average: ' . $elapsed / $max . " s\n\n";



echo "<strong>Generate random ACLs now.</strong>\n";
flush();

$startTime = getmicrotime();

$start = 1;
$max = 10 * $scale;

$acoList = $gaclApi->getObject('system', 1, 'ACO');

$query = 'SELECT id, name FROM '.$gaclApi->dbTablePrefix.''. $gacl_.'aro_groups ORDER BY parent_id DESC LIMIT 100';
$rs = $gaclApi->db->Execute($query);
$aroGroups = $rs->GetAssoc();

$query = 'SELECT id, name FROM '.$gaclApi->dbTablePrefix.'axo_groups ORDER BY parent_id DESC LIMIT 100';
$rs = $gaclApi->db->Execute($query);
$axoGroups = $rs->GetAssoc();

// $aroGroups = $gaclApi->formatGroups ($gaclApi->sortGroups ('ARO'), 'ARRAY');

print_r($aroGroups);

// $axoGroups = $gaclApi->formatGroups ($gaclApi->sortGroups ('AXO'), 'ARRAY');

print_r($axoGroups);

for ($i = $start; $i <= $max; $i++) {
    $randAcoKey = arrayMtRand($acoList, mt_rand(2, 10));
    $randAroKey = arrayMtRand($aroGroups, mt_rand(2, 10));
    $randAxoKey = arrayMtRand($axoGroups, mt_rand(2, 10));

    $acoArray = [];

    foreach ($randAcoKey as $acoKey) {
        $acoData = $gaclApi->getObjectData($acoList[$acoKey], 'ACO');
        $acoArray[$acoData[0][0]][] = $acoData[0][1];
    }

    // Randomly create ACLs with AXOs assigned to them.
    // if ($i % 2 == 0) {
    $axoArray = $randAxoKey;
    // }

    if ($gaclApi->addAcl($acoArray, null, $randAroKey, null, $axoArray) == false) {
        echo "    Error creating ACL: $i.\n";
        echo '    ' . $gaclApi->_debug_msg . "\n";
        // print_r (array_slice ($gaclApi->_debug_msg, -2));
    }

    unset($axoArray);
}

$elapsed = getmicrotime() - $startTime;

echo "Done\n";
echo '    Count:   ' . $max . "\n";
echo '    Time:    ' . $elapsed . " s\n";
echo '    Average: ' . $elapsed / $max . " s\n\n";


echo "<strong>Generating Test Data Set</strong>\n";
flush();

$startTime = getmicrotime();

$start = 1;
$max = 5 * $scale;
// $max = 1;

$check = [];

for ($i = $start; $i <= $max; $i++) {
    $randAcoKey = mt_rand(10, 10 * $scale);
    $randAroKey = mt_rand(10, 1000 * $scale);
    $randAxoKey = mt_rand(10, 1000 * $scale);

    echo '    Rand ACO: '. $randAcoKey
    . ' ARO: ' . $randAroKey
    . ' AXO: ' . $randAxoKey . "\n";

    $acoData = &$gaclApi->getObjectData($randAcoKey, 'ACO');
    $aroData = &$gaclApi->getObjectData($randAroKey, 'ARO');
    $axoData = &$gaclApi->getObjectData($randAxoKey, 'AXO');

    $check[$i] = [
        'aco' => $acoData[0],
        'aro' => $aroData[0],
        'axo' => $axoData[0]
    ];
}

$elapsed = getmicrotime() - $startTime;

echo "Done\n\n";
echo '    Count:   ' . $max . PHP_EOL;
echo '    Time:    ' . $elapsed . " s" . PHP_EOL;
echo '    Average: ' . $elapsed / $max . " s" . PHP_EOL . PHP_EOL;

echo "<strong>Testing...</strong>\n";
flush();

$best = 99999;
$worst = 0;
$total = 0;

foreach ($check as $i => $data) {
    echo '    Trying: ACO Section: ' . $data['aco'][0] .' Value: ' . $data['aco'][1]
    . ' ARO Section: ' . $data['aro'][0] . ' Value: '. $data['aro'][1]
    . ' ARO Section: '. $data['axo'][0] .' Value: '. $data['axo'][1] . PHP_EOL;

    $checkStart = getmicrotime();

    $allow = $gaclApi->aclCheck(
        $data['aco'][0],
        $data['aco'][1],
        $data['aro'][0],
        $data['aro'][1],
        $data['axo'][0],
        $data['axo'][1]
    );

    $checkTime = getmicrotime() - $checkStart;

    if ($allow) {
        echo '<span class="text-success">    ' . $i . ". Access Granted!</span>";
    } else {
        echo '<span class="text-danger">    ' . $i . ". Access Denied!</span>";
    }

    echo ' - ' . $checkTime . " s\n";

    $best  = min($best, $checkTime);
    $worst = max($worst, $checkTime);
    $total = $total + $checkTime;
}

echo "Done\n";
echo '    Count:   ' . $max . PHP_EOL . PHP_EOL;

echo '    Total:   ' . $total . " s" . PHP_EOL;
echo '    Average: ' . $total / $max . " s" . PHP_EOL . PHP_EOL;

echo '    Best:    ' . $best . " s" . PHP_EOL;
echo '    Worst:   ' . $worst . " s" . PHP_EOL . PHP_EOL;

$elapsed = getmicrotime() - $overallStart;

echo '<strong>All Finished</strong>' . PHP_EOL;
echo '    Total Time: ' . $elapsed . " s" . PHP_EOL;

/*
 * end of script
 */

?>
</pre>
</div>
</body>
</html>
