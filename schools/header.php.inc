<?php
include('countdown.php.inc');

// Filter parameter
$filter = (isset($_GET['filter']) ? $_GET['filter'] : "uk");

$where_filter = "";
$where_filter_and = "";

$cond = '';
if ($filter == 'uk') {
  $cond = 'lon>-10.811 AND lon<1.780 AND lat>49.838 AND lat<61.016';
} elseif ($filter == 'belgium') {
  $cond = 'lon>2.516 AND lon<6.449 AND lat>49.482 AND lat<51.516';
} elseif ($filter == 'uganda') {
  $cond = 'lon>29.564 AND lon<35.024 AND lat>-1.450 AND lat<4.259';
} elseif ($filter == 'none') {
  $cond = '';
} else {
  die("Sorry. We don't have that filter");
}

$where_filter = ($cond=='' ? '' : 'WHERE ' . $cond);
$where_filter_and = ($cond=='' ? '' : 'AND ' . $cond);

// Database connection
$dbFile = "./edits.db";

$last_update = filemtime("./on_seq.txt");


if (!file_exists($dbFile)) die('Missing db file ' . $dbFile);
$db = new SQLite3($dbFile);

// body onLoad (if $onLoad_function is set before including this)
$body_tag_attributes = '';
if (isset($onLoad_function)) {
  $body_tag_attributes .= ' onLoad="javascript:' . $onLoad_function .'();"';
}

// http headers. No cache
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0

?>
<html>
<head>

<title>School edits 2016</title>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?php if (isset($head_content)) {print $head_content;}?>

<style>
body, p, h1, h2, h3, h4, h5, h6, li, td, th, dd, dt {
    font-family: Verdana, Helvetica, Arial, sans-serif;
}

TABLE {
   border-style:solid;
   border-width:1px;
   border-color:#aaa;
   background: #f9f9f9;
   border-collapse: collapse;
   font-size: 95%;
}

TABLE TD {
   padding:4px;
   border-style:solid;
   border-width:1px;
   border-color:#aaa;
}
TABLE TH {
   padding:4px;
   background-color:#E9E9E9;
   border-style:solid;
   border-width:1px;
   border-color:#aaa;
}
#list {
   align:center;

}
#logo {
   float:left;
   margin:10px;
   margin-bottom:40px;
   border:none;
}
#toptext {
   clear:both;
}
#menu {
   background:#EEE;
   border: 1px solid;
   padding:3px;
   font-weight:bold;
}
#filter-links .selected {
   color:black;
   text-decoration:none;
   background:#EEE;
}
#filter-linkes .deselected {
}
</style>
</head>

<body<?php print $body_tag_attributes;?>>


<div id="toptext">
  <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4a/School.svg/240px-School.svg.png" title="School illustration. Vladsinger on wikimedia commons" id="logo">
  <h1>School edits 2016 <?php if ($filter!="none") { print '(' . $filter . ')'; } ?></h1>
  <p>
  Welcome to the OpenStreetMap School edits tracker. In the UK we're busy mapping schools for the <a href="http://wiki.openstreetmap.org/wiki/UK_Quarterly_Projects#Current_project_-_Schools">OpenStreetMap UK quarterly project</a>.
  </p>
  <p><?php echo countdown(2016, 3, 31, 23, 59); /* $year, $month, $day, $hour, $minute */ ?></p>


  <span id="menu">
  <a href="./<?php echo "?filter=$filter"; ?>" title="list of edits coming in">Edits</a> |
  <a href="./rankings.php<?php echo "?filter=$filter"; ?>" title="which users are making most edits">Rankings</a> |
  <a href="./new-starters.php<?php echo "?filter=$filter"; ?>" title="how many users are joining in">New Starters</a>
  </span>
  &nbsp;
  <br><br>
  Area filter:
  <span id='filter-links'>
<?php
  $other_params = '';
  if (isset($_GET['user'])) $other_params = '&user=' .urlencode($_GET['user']);
  print "<a href='" . basename($_SERVER['PHP_SELF']) . "?filter=uk$other_params' class='" . ($filter=='uk' ? 'selected' : 'deselected') . "' title='Show edits and stats just for edits UK & Ireland'>[UK & Ireland]</a> | ";
  print "<a href='" . basename($_SERVER['PHP_SELF']) . "?filter=belgium$other_params' class='" . ($filter=='belgium' ? 'selected' : 'deselected') . "' title='Show edits and stats within a Belgium bounding box'>[Belgium]</a> | ";
  print "<a href='" . basename($_SERVER['PHP_SELF']) . "?filter=uganda$other_params' class='" . ($filter=='uganda' ? 'selected' : 'deselected') . "' title='Show edits and stats Uganda'>[Uganda]</a> | ";
  print "<a href='" . basename($_SERVER['PHP_SELF']) . "?filter=none$other_params' class='" . ($filter=='none' ? 'selected' : 'deselected') . "' title='Show edits and stats worldwide'>[Worldwide]</a>";
?>
  </span>
</div>

