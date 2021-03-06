<?php
include('header.php.inc');
include('display.php.inc');
?>
<center>

<h3 class="tablelabel">Rankings:</h3>

<?php
print "<table>\n";
print "<tr>\n";
print "<th><h4>Rankings overall:</h4></th>\n";
print "<th><h4>Past day:</h4></th>\n";
print "<th><h4>Past hour:</h4></th>\n";
print "</tr>\n";
print "<tr>\n";
print "<td valign=\"top\">\n";

print "<ol>\n";
$results = $db->query('SELECT count(*) AS count, user_name, min(timestamp) AS start_timestamp FROM edits '.$where_filter.' GROUP BY user_name ORDER BY count DESC, start_timestamp ASC');
while ($data = $results->fetchArray()) {
   $count           = $data[0];
   $user            = $data[1];
   $start_timestamp = $data[2];
   print '<li>' . user_link($user) . " - $count edits</li>";
}
print "</ol>\n";
print "</td>\n";

print "<td valign=\"top\">\n";
print "<ol>\n";
$results = $db->query("SELECT count(*) AS count, user_name FROM edits WHERE timestamp > strftime('%Y-%m-%dT%H:%M', datetime('now','-1 day')) ".$where_filter_and." GROUP BY user_name ORDER BY count DESC");
while ($data = $results->fetchArray()) {
   $count    = $data[0];
   $user     = $data[1];
   print '<li>' . user_link($user) . " - $count edits</li>";
}
print "</td>\n";


print "<td valign=\"top\">\n";
print "<ol>\n";
$results = $db->query("SELECT count(*) AS count, user_name FROM edits WHERE timestamp > strftime('%Y-%m-%dT%H:%M', datetime('now','-1 hour')) ".$where_filter_and." GROUP BY user_name ORDER BY count DESC");
while ($data = $results->fetchArray()) {
   $count    = $data[0];
   $user     = $data[1];
   print '<li>' . user_link($user) . " - $count edits</li>";
}
print "</td>\n";


print "</tr>\n";
print "</table>\n";
?>

</center>
<?php
include('footer.php.inc');
?>
