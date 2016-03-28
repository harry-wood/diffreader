<?php
// Chart functions

function chart_script_tags() {
return <<<HTML
   <link href="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.10/c3.min.css" rel="stylesheet" type="text/css">
   <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.16/d3.min.js" charset="utf-8"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/c3/0.4.10/c3.min.js"></script>
HTML;
}

// Return javascript which initialises a date series bar graph
// This output should be written into a javascript init function
function chart_javascript($chart_div, $chart_data) {
  $js_rows = array();
  foreach ($chart_data as $date => $count) {
    $js_rows[] = "['$date', '$count']";
  }
  $js_rows_string = implode(",\n  ", $js_rows);

return <<<HTML
  var chart = c3.generate({
    bindto: '#{$chart_div}',
    data: {
      rows: [
        ['date', 'count'],
        {$js_rows_string}
      ],
      x: 'date',
      axes: {
        count: 'y'
      },
      types: {
        count: 'bar'
      }
    },
    axis: {
      x: {
          type: 'timeseries',
          tick: {
              format: '%Y-%m-%d'
          }
      },
      y: {
        show: true,
        label: {
          text: 'Count',
          position: 'outer-middle'
        }
      }
    },
    legend: {
        show: false
    }
  });

HTML;
}

function fill_out_with_zeros($chart_data, $start_date, $end_date) {
    $output = $chart_data;
    $on_date = new DateTime($start_date);
    while ($on_date < new DateTime($end_date)) {
        $on_date_str = $on_date->format('Y-m-d');

        if (!array_key_exists($on_date_str, $output)) $output[$on_date_str] = 0;

        $on_date->add(new DateInterval('P1D'));
    }
    return $output;
}
