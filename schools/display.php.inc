<?php 
// Display functions

function user_link($user_name) {
   global $filter;
   $user_url = urlencode($user_name);
   //$user_url = str_replace("+", "%20", $user_url);
   
   //return "<a href=\"http://www.openstreetmap.org/user/".$user_url."\" title=\"osm user page\">$user_name</a>";
   return "<a href=\"./user.php?user=$user_url&filter=$filter\" title=\"User edit stats page\">$user_name</a>";
}
