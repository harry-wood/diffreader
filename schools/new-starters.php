<?php
include('header.php.inc');
include('display.php.inc');
?>
<?php

function start_dates_and_users() {
	global $db, $where_filter;
	
	$results = $db->query(
      '  SELECT MIN(date(timestamp)) AS start_date, user_name ' .
      "  FROM edits $where_filter " .
      '  GROUP BY user_name ORDER BY start_date'
      );
    
    $output = array();
    $on_date = null;
    while ($row = $results->fetchArray()) {
        $start_date = $row[0];
        $user_name = $row[1];
        
        if (!array_key_exists($start_date,$output)) $output[$start_date] = array();
        array_push($output[$start_date], $user_name);
    }
    return $output;
}
?>


<h3 class="tablelabel">New Starters</h3>
<p>Showing when users first got involved, and how many new users are starting</p>

<table border="0">
<tr>
  <th>date</th>
  <th>new starters</th>
  <th>user links</th>
</tr>

<?php
$start_dates_and_users = start_dates_and_users();

$on_date = new DateTime(min(array_keys($start_dates_and_users))); //first start date
$today = new DateTime();
while ($on_date < $today) {
	$on_date_str = $on_date->format('Y-m-d');
	
	print "<tr>";
	print "<td style='white-space: nowrap;'>$on_date_str</td>";
	
	$user_names = array();
	if (array_key_exists($on_date_str, $start_dates_and_users)) {
	    $user_names = $start_dates_and_users[$on_date_str];
	}
	$count = sizeof($user_names);
	
	print "<td style='white-space: nowrap;'>$count people started:</td>";
	print "<td>";
	
    foreach ($user_names as $user_name) {
    	print user_link($user_name) . " ";
    }
	print "</td>";
	print "</tr>\n";
	
	$on_date->add(new DateInterval('P1D'));
}
?>

</td></tr>
</table>

<?php

include('footer.php.inc');
?>
