<?php
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

$table_size_limit = 300;
?>


<h3 class="tablelabel">Edits coming in</h3>
<p>The most recent <?php echo $table_size_limit ?> edits (latest first):</p>

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
   
   $timestamp = eregi_replace("T", " at ", $timestamp);
   $timestamp = eregi_replace("Z", "", $timestamp);             
   
   $timestamp = eregi_replace("Z", "", $timestamp);
   
   $user_url = urlencode($user);
   $user_url = str_replace("+", "%20", $user_url);
   
   print "<tr>";
   print "<td>".$timestamp."</td>";
   print "<td>".$optype."</td>";
   print "<td><a href=\"http://www.openstreetmap.org/browse/$element_type/$osm_id\" title=\"browse the OpenStreetMap element\">$element_type:$osm_id</a></td>";
   print "<td><a href=\"http://www.openstreetmap.org/user/".$user_url."\" title=\"osm user page\">$user</a></td>";
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
