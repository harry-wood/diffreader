<?php
include('chart.php.inc');
include('display.php.inc');

$head_content = chart_script_tags();
$onLoad_function = 'init';
include('header.php.inc');
?>

<center>

<?php
$results = $db->query('SELECT count(*) FROM edits ' . $where_filter);
$res_array = $results->fetchArray();
$edit_count = $res_array[0];

$results = $db->query('SELECT count(*) FROM ( SELECT * FROM edits ' . $where_filter . ' GROUP BY user_name);');
$res_array = $results->fetchArray();
$user_count = $res_array[0];

print "<h3><span style=\"font-size:1.2em; background:YELLOW;\">$user_count people</span> have done $edit_count edits</h3>\n";
print "<br>\n";
?>
</center>

<h3>Edits over time</h3>
<?php
$results = $db->query(
  '  SELECT date(timestamp) AS date, COUNT(*) ' .
  "  FROM edits $where_filter " .
  '  GROUP BY date ORDER BY date'
  );

$chart_data = array(); //hash indexed by date
// Loop through SQL result
while ($row = $results->fetchArray()) {
    $date = $row[0];
    $count = $row[1];
    $chart_data[$date] = $count;
}
?>
<script language="javascript" type="text/javascript">
function init() {
    <?php print chart_javascript('chart', $chart_data); ?>
}
</script>
<div id="chart" style="width:100%; height:200px;"></div>


<?php
$table_size_limit = 200;
?>
<h3>Edits coming in</h3>
<p>The most recent <?php echo $table_size_limit ?> edits (latest first):</p>

<center>
<table border="0" id="list">
<tr>
  <th>time</th>
  <th>optype</th>
  <th>element</th>
  <th>user</th>
  <th>changeset</th>
  <th>location</th>
</tr>

<?php
$results = $db->query('SELECT timestamp, op_type, element_type, osm_id, user_name, changeset, lat, lon FROM edits ' . $where_filter . ' ORDER BY timestamp DESC LIMIT ' . $table_size_limit );
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
   print "<td>" . user_link($user) . "</td>";
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
include('footer.php.inc');
?>
