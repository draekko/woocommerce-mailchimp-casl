<?php 
$db = new SQLite3('timezone.db');
$sql = "SELECT zone_name FROM zone;";
$query = $db->query($sql);
if (!$query) {
    $db->close();
    echo "ERRORS";
} else {
    $rows = array();
    $i = 1;
    while ( $res = $query->fetchArray(SQLITE3_ASSOC) ) {
	//echo "[".$i."] ".print_r($res);
	$rows[$i][$res['zone_name']] = $res['zone_name'];
	$i++;
    }
    $db->close();
    print_r($rows);
}
?>

