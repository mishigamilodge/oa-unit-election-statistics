<?php
/*
 * Copyright (C) 2022 David D. Miller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

add_shortcode( 'oauestats_completion_chart', 'oauestats_completion_chart' );
function oauestats_completion_chart() {
    wp_enqueue_script( 'Chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.bundle.min.js');
    wp_enqueue_style('oauestats_chart_colors', plugins_url('css/chart-colors.css', dirname(__FILE__)));
    global $wpdb;
    $dbprefix = $wpdb->prefix . "oauestats_";

    # so each chart can be independent if used more than once on the same page
    $unique_token = bin2hex(openssl_random_pseudo_bytes(3));

    $chapters = [];
    ob_start();
    $results = $wpdb->get_results("SELECT DISTINCT `Chapter` FROM `${dbprefix}inductions_data` WHERE `Chapter` <> 'ScoutReach'", OBJECT_K);
    $totalunits = $wpdb->get_var("SELECT COUNT(*) FROM `${dbprefix}inductions_data` WHERE `Chapter` <> 'ScoutReach'");
    foreach ($results AS $obj) {
       $obj->notsched = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data`  WHERE `Status` = 'Not Scheduled' AND `Chapter` = %s", array($obj->Chapter)));
       $obj->declined = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data`  WHERE `Status` = 'Declined' AND `Chapter` = %s", array($obj->Chapter)));
       $obj->requested = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data`  WHERE `Status` = 'Requested' AND `Chapter` = %s", array($obj->Chapter)));
       $obj->sched = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data`  WHERE `Status` = 'Scheduled' AND `Chapter` = %s AND `Visit_Date` > NOW()", array($obj->Chapter)));
       $obj->pastdue = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data`  WHERE `Status` = 'Scheduled' AND `Chapter` = %s AND `Visit_Date` < NOW()", array($obj->Chapter)));
       $obj->posted = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data`  WHERE `Status` = 'Posted' AND `Chapter` = %s", array($obj->Chapter)));
       $obj->approved = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data`  WHERE `Status` = 'Approved' AND `Chapter` = %s", array($obj->Chapter)));
       $obj->total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `${dbprefix}inductions_data` WHERE `Chapter` = %s", array($obj->Chapter)));
       $chapters[$obj->Chapter] = $obj;
    }

?>
<h5>Unit Election Completion Chart</h5>
<canvas id="oauestats_<?php esc_html_e($unique_token) ?>_ElectionChart" width="200" height="100"></canvas>
<p>Data last updated: <?php esc_html_e(get_option("oauestats_last_import")); ?></p>
<div style="display:none;">
<!-- this hidden div is to ensure we have something on the page with these
     classes so we can pull the colors out of them to use in the charts, and
     then the colors can be changed in the CSS and they're consistent
     everywhere we use them -->
<div class="oauestats_notscheduled"></div>
<div class="oauestats_declined"></div>
<div class="oauestats_requested"></div>
<div class="oauestats_scheduled"></div>
<div class="oauestats_pastdue"></div>
<div class="oauestats_posted"></div>
<div class="oauestats_approved"></div>
</div>
<script type="text/javascript">
var $j = jQuery.noConflict();
function oauestats_<?php esc_html_e($unique_token) ?>_fixalpha(color, newalpha) {
    var pat = /^rgba?\((\d+),\s*(\d+),\s*(\d+)/;
    var m = pat.exec(color);
    console.log("fixAlpha passed color = " + color);
    return "rgba(" + m[1] + ", " + m[2] + ", " + m[3] + ", " + newalpha + ")";
}
<?php
    $chapterlist = array_keys($chapters);
    sort($chapterlist);
    $labellist = $chapterlist;
    $labellist[] = 'Entire Lodge';
?>
$j(document).ready(function(){
var oauestats_<?php esc_html_e($unique_token) ?>_chartconfig = {
    type: 'horizontalBar',
    data: {
        labels: <?php echo wp_json_encode($labellist); ?>,
        datasets: [
        {
            label: 'Completed',
            data: [<?php
            $count = 0;
            $total = 0;
            foreach ($chapterlist AS $chapter) {
                $obj = $chapters[$chapter];
                if ($count > 0) { echo ","; };
                $num = $obj->declined + $obj->posted + $obj->approved;
                echo esc_html(($num / $obj->total) * 100);
                $count++;
                $total = $total + $num;
            }
            echo "," . esc_html(($total / $totalunits) * 100);
            ?>],
            backgroundColor: oauestats_<?php esc_html_e($unique_token) ?>_fixalpha($j(".oauestats_approved").css("background-color"), 0.2),
            borderColor: oauestats_<?php esc_html_e($unique_token) ?>_fixalpha($j(".oauestats_approved").css("background-color"), 1),
            borderWidth: 1
        },
        {
            label: 'Not Completed',
            data: [<?php
            $count = 0;
            $total = 0;
            foreach ($chapterlist AS $chapter) {
                $obj = $chapters[$chapter];
                if ($count > 0) { echo ","; };
                $num = $obj->pastdue + $obj->sched + $obj->requested + $obj->notsched;
                echo esc_html(($num / $obj->total) * 100);
                $count++;
                $total = $total + $num;
            }
            echo "," . esc_html(($total / $totalunits) * 100);
            ?>],
            backgroundColor: oauestats_<?php esc_html_e($unique_token) ?>_fixalpha($j(".oauestats_notscheduled").css("background-color"), 0.2),
            borderColor: oauestats_<?php esc_html_e($unique_token) ?>_fixalpha($j(".oauestats_notscheduled").css("background-color"), 1),
            borderWidth: 1
        }
        ]
    },
    options: {
        tooltips: {
            callbacks: {
                label: function(tooltipitem, data) {
                    return data.datasets[tooltipitem.datasetIndex].label + ": " + (Math.round(tooltipitem.xLabel * 10) / 10) + "%";
                }
            }
        },
        legend: {
            labels: {
                boxWidth: 20
            }
        },
        scales: {
            xAxes: [{
                stacked: true,
                ticks: {
                    beginAtZero:true,
                    suggestedMax:100
                }
            }],
            yAxes: [{
                stacked: true
            }]
        }
    }
};

var oauestats_<?php esc_html_e($unique_token) ?>_chart = new Chart($j("#oauestats_<?php esc_html_e($unique_token) ?>_ElectionChart"), oauestats_<?php esc_html_e($unique_token) ?>_chartconfig);
});
</script>
    <?php
    return ob_get_clean();
}