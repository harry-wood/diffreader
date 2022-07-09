<?php
if (!isset($_GET['user'])) die('Missing user param');
$user = $_GET['user'];

include('chart.php.inc');
include('display.php.inc');

$head_content = chart_script_tags();
$onLoad_function = 'init';
include('header.php.inc');


$statement = $db->prepare('SELECT count(*) FROM edits '.
                          'WHERE user_name=:user '.
                          $where_filter_and.';');
$statement->bindValue(':user', $user);

$results = $statement->execute();
$res_array = $results->fetchArray();
$edit_count = $res_array[0];

if ($edit_count==0) {
    // Check if there's any edits at all by this user (without filter)
    $statement = $db->prepare('SELECT count(*) FROM edits '.
                              'WHERE user_name=:user;');
    $statement->bindValue(':user', $user);
    $results = $statement->execute();
    $res_array = $results->fetchArray();
    $edit_count_unfiltered = $res_array[0];

    if ($edit_count_unfiltered==0) {
       $filter_note = "even without the filter. Maybe this user doesn't exist";
    } else {
       $filter_note = "for this filter";
    }

    print "<h3>There are no edits by " . htmlspecialchars($user) . "<br>($filter_note)</h3>\n";
    print "<br style=\"clear:both;\">\n";

} else {
    print "<h3>" . htmlspecialchars($user) . " has done $edit_count edits</h3>\n";
    print "<br style=\"clear:both;\">\n";


    print "<h3>Edits over time</h3>";

    $results = $db->query('SELECT MIN(date(timestamp)) AS start_date, '.
                          '       MAX(date(timestamp)) AS end_date '.
                          'FROM edits');
    $res_array = $results->fetchArray();
    $start_date = $res_array[0];
    $end_date = $res_array[1];

    $statement = $db->prepare(
      'SELECT date(timestamp) AS date, COUNT(*) ' .
      "FROM edits " .
      "WHERE user_name=:user $where_filter_and ".
      'GROUP BY date ORDER BY date;');

    $statement->bindValue(':user', $user);
    $results = $statement->execute();

    $chart_data = array(); //hash indexed by date
    // Loop through SQL result
    while ($row = $results->fetchArray()) {
        $date = $row[0];
        $count = $row[1];
        $chart_data[$date] = $count;
    }

    $chart_data = fill_out_with_zeros($chart_data, $start_date, $end_date);

    if (!array_key_exists($start_date, $chart_data)) $chart_data[$start_date] = 0;
    if (!array_key_exists($end_date, $chart_data)) $chart_data[$end_date] = 0;
    ?>

    <script language="javascript" type="text/javascript">
    function init() {
        <?php print chart_javascript('chart', $chart_data); ?>
    }
    </script>

    <div id="chart" style="width:100%; height:200px;"></div>



    <h3>Edits</h3>
    <?php
    $table_size_limit = 200;

    if ($edit_count > $table_size_limit) {
       print "<p>Most recent edits by " . htmlspecialchars($user) . " (latest first):</p>\n";
    } else {
       print "<p>Edits by " . htmlspecialchars($user) . "</p>\n";
    }
    ?>
    <center>
    <table border="0" id="list">
    <tr>
      <th>time</th>
      <th>optype</th>
      <th>element</th>
      <th>changeset</th>
      <th>location</th>
    </tr>

    <?php
    $statement = $db->prepare(
      'SELECT timestamp, op_type, element_type, osm_id, user_name, changeset, lat, lon '.
      'FROM edits '.
      "WHERE user_name=:user $where_filter_and ".
      'ORDER BY timestamp DESC LIMIT ' . $table_size_limit . ';');
    $statement->bindValue(':user', $user);
    $results = $statement->execute();

    while ($data = $results->fetchArray()) {
       $timestamp    = $data[0];
       $optype       = $data[1];
       $element_type = $data[2];
       $osm_id       = $data[3];
       $user         = $data[4];
       $changeset    = $data[5];
       $lat          = $data[6];
       $lon          = $data[7];

       $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $timestamp, new DateTimeZone('UTC'));
       $datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
       $formatted_datetime = $datetime->format("Y/m/d H:i:s");

       print "<tr>";
       print "<td>".$formatted_datetime."</td>";
       print "<td>".$optype."</td>";
       print "<td><a href=\"http://www.openstreetmap.org/browse/$element_type/$osm_id\" title=\"browse the OpenStreetMap element\">$element_type:$osm_id</a></td>";
       print "<td><a href=\"http://www.openstreetmap.org/browse/changeset/".$changeset."\">$changeset</a></td>\n";
       print "<td><small><a href=\"http://www.openstreetmap.org/?mlat=$lat&mlon=$lon\" title=\"location\">($lat,$lon)</a></small></td>\n";
       print "</tr>\n";

    }
    ?>

    </td></tr>
    </table>

    <?php
    if ($edit_count > $table_size_limit) {
       print "<p>...and " . ($edit_count - $table_size_limit) . " more edits since we started</p>\n";
    }
    ?>

    </center>


    <?php
}
?>

<h3>More about this user:</h3>
<?php
$user_url = urlencode($user);
$user_url = str_replace("+", "%20", $user_url);
print "<ul>";
print "<li><a href=\"http://www.openstreetmap.org/user/$user_url\" title=\"A place where they can describe themselves + normal edit tracking features\">OpenStreetMap user profile for '" . htmlspecialchars($user) . "'</a></li>";
print "<li><a href=\"http://hdyc.neis-one.org/?$user_url\" title=\"A place where they can describe themselves + normal edit tracking features\">neis-one.org \"How did you contribute\" for '" . htmlspecialchars($user) . "'</a></li>";
print "</ul>";
print "<br><br>";
include('footer.php.inc');
?>
