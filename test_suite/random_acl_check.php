<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <style>
    <?php echo file_get_contents(__DIR__ . '/../admin/bootstrap.min.css'); ?>
    </style>
    <title>Random ACL Check</title>
</head>
<body>
<div class="container">
<pre>
<?php

set_time_limit(6000);

/*! function
 get time accurate to the nearest microsecond, used for script timing
 !*/
function getmicrotime()
{
    list ($usec, $sec) = explode (' ', microtime ());
    return (float)$usec + (float)$sec;
}

/*! function
 a better array_rand, this one actualluy works on windows
 !*/
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

/*! function
 grab random objects from the database
 !*/
function randomObjects($type, $limit = null)
{
    $sql = 'SELECT id, section_value, value FROM ' . $GLOBALS['gaclApi']->dbTablePrefix . $type . ' ORDER BY RAND()';

    if (is_scalar($limit)) {
        $rs = $GLOBALS['gaclApi']->db->SelectLimit($sql, $limit);
    } else {
        $rs = $GLOBALS['gaclApi']->db->Execute($sql);
    }

    if (!is_object($rs)) {
        return false;
    }

    $retarr = [];

    while ($row = $rs->FetchRow()) {
        $retarr[$row[0]] = [$row[1], $row[2]];
    }

    return $retarr;
}

// require gacl
require_once dirname(__FILE__) . '/../admin/gacl_admin.inc.php';

/*
 * Let's get ready to RUMBLE!!!
 */
$scale = 100;

echo '<strong>Random ACL Check</strong>' . PHP_EOL;
echo '    Scale: ' . $scale . PHP_EOL;

$overallStart = getmicrotime();

mt_srand((double)microtime() * 10000);

echo "<strong>Generating Test Data Set</strong>" . PHP_EOL;
flush();

$startTime = getmicrotime();

$start = 1;
$max = 5 * $scale;
// $max = 1;

$check = [];

$aco = randomObjects('aco', $max);
$aro = randomObjects('aro', $max);
$axo = randomObjects('axo', $max);

for ($i = $start; $i <= $max; $i++) {
    $randAcoId = arrayMtRand($aco, 1);
    $randAroId = arrayMtRand($aro, 1);
    $randAxoId = arrayMtRand($axo, 1);

    echo '    Rand ACO: '. $randAcoId .' ARO: '. $randAroId . ' AXO: ' . $randAxoId . PHP_EOL;

    $check[$i] = [
        'aco' => $aco[$randAcoId],
        'aro' => $aro[$randAroId],
        'axo' => $axo[$randAxoId]
    ];
}

$elapsed = getmicrotime() - $startTime;

echo "Done" . PHP_EOL . PHP_EOL;
echo '    Count:   ' . $max . PHP_EOL;
echo '    Time:    ' . $elapsed . " s" . PHP_EOL;
echo '    Average: ' . $elapsed / $max . " s" . PHP_EOL . PHP_EOL;

echo "<strong>Testing...</strong>" . PHP_EOL;
flush();

$best = 99999;
$worst = 0;
$total = 0;

$allowed = 0;
$denied = 0;

$allowedTime = 0;
$deniedTime = 0;

foreach ($check as $i => $data) {
    echo '    Trying: ACO Section: '. $data['aco'][0] .' Value: '. $data['aco'][1] .' ARO Section: '. $data['aro'][0] .' Value: '. $data['aro'][1] . ' ARO Section: '. $data['axo'][0] .' Value: '. $data['axo'][1] . PHP_EOL;

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
        echo '<span class="text-success">    ' . $i . ". Access Granted</span>" . PHP_EOL;
        $allowed++;
        $allowedTime += $checkTime;
    } else {
        echo '<span class="text-danger">    ' . $i . ". Access Denied</span>" . PHP_EOL;
        $denied++;
        $deniedTime += $checkTime;
    }

    echo ' - ' . $checkTime . " s\n";

    $best  = min($best, $checkTime);
    $worst = max($worst, $checkTime);
    $total = $total + $checkTime;
}

echo "Done" . PHP_EOL;
echo '    Count:   ' . $max . PHP_EOL;
echo '    Total:   ' . $total . " s" . PHP_EOL;
echo '    Average: ' . $total / $max . " s" . PHP_EOL . PHP_EOL;

echo '    Allowed: ' . $allowed . PHP_EOL;
echo '    Total:   ' . $allowedTime . " s" . PHP_EOL;
echo '    Average: ' . $allowedTime / $allowed . " s" . PHP_EOL . PHP_EOL;

echo '    Denied:  ' . $denied . PHP_EOL;
echo '    Total:   ' . $deniedTime . " s" . PHP_EOL;
echo '    Average: ' . $deniedTime / $denied . " s" . PHP_EOL . PHP_EOL;

echo '    Best:    ' . $best . " s" . PHP_EOL;
echo '    Worst:   ' . $worst . " s" . PHP_EOL . PHP_EOL;

// print_r ($gaclApi->db);


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
