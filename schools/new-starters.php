<?php
include('display.php.inc');
include('chart.php.inc');

$head_content = chart_script_tags();
$onLoad_function = 'init';
include('header.php.inc');

// Returns an hash of dates => [ array of user names]
function start_dates_and_users() {
    global $db, $where_filter;

    $results = $db->query(
      '  SELECT MIN(date(timestamp)) AS start_date, user_name ' .
      "  FROM edits $where_filter " .
      '  GROUP BY user_name ORDER BY start_date'
      );

    $output = array(); //hash indexed by date
    $on_date = null;
    // Loop through SQL result
    while ($row = $results->fetchArray()) {
        $start_date = $row[0];
        $user_name = $row[1];

        if (!array_key_exists($start_date,$output)) $output[$start_date] = array();
        array_push($output[$start_date], $user_name);
    }
    return $output;
}

function convert_to_counts($start_dates_and_users) {
    $output = array(); //hash indexed by date
    foreach ($start_dates_and_users as $date => $user_array) {
        $output[$date] = count($user_array);
    }
    return $output;
}

$start_dates_and_users = start_dates_and_users();

$chart_data = convert_to_counts($start_dates_and_users);

?>
<script language="javascript" type="text/javascript">
function init() {
    <?php print chart_javascript('chart', $chart_data); ?>
}
</script>

<h3 class="tablelabel">New Starters</h3>
<p>Number of new people getting involved over time</p>
<div id="chart" style="width:100%; height:200px;"></div>

<p>Who got started when?</p>

<table border="0">
<tr>
  <th>date</th>
  <th>new starters</th>
  <th>user links</th>
</tr>

<?php

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
