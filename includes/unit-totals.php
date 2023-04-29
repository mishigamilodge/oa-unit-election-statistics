<?php
/*
 * Copyright (C) 2023 Mishigami Lodge, Order of the Arrow, BSA
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

add_shortcode( 'oauestats_unit_totals', 'oauestats_unit_totals' );
function oauestats_unit_totals() {
    global $wpdb;
    $dbprefix = $wpdb->prefix . "oauestats_";
    wp_enqueue_style('oauestats_unit_totals', plugins_url('css/unit-totals.css', dirname(__FILE__)));

    # so each chart can be independent if used more than once on the same page
    $unique_token = bin2hex(openssl_random_pseudo_bytes(3));

    $chapters = [];
    ob_start();
    $results = $wpdb->get_results("
SELECT `District`,
`Chapter`,
`Unit_Type`,
`Unit_Number`,
`Unit_Designation`,
`Unit_City`,
`Elected_Count`,
COUNT(`Nomination_Status` <> 'Declined') AS `Nominated_Count`
FROM `${dbprefix}inductions_data`
LEFT JOIN `${dbprefix}nominations_data`
       ON `Chapter` = `Nominating_Unit_Chapter`
	  AND `Unit_Type` = `Nominating_Unit_Type`
      AND `Unit_Number` = `Nominating_Unit_Number`
      AND `Unit_Designation` = `Nominating_Unit_Designation`
WHERE `Chapter` <> 'ScoutReach'
  AND `Status` IN('Approved', 'Posted')
  AND `Visit_Type` = 'Election'
GROUP BY `Chapter`, `Unit_Type`, `Unit_Number`, `Unit_Designation`
ORDER BY `Chapter`, `Unit_Type`, `Unit_Number`, `Unit_Designation` ASC
", OBJECT);
?>
<h5>Unit Adult Nomination Eligibility</h5>
<p>If your unit is not listed here, it means either you did not hold an election or we have not yet received your election report as of <?php esc_html_e(get_option("oauestats_last_import")); ?>. Green <b>Adults Remaining</b> column means you can still nominate more adults!</p>
<?php
    $curchapter = '';
    $tablestarted = 0;
    $youthcount = 0;
    $adultslotcount = 0;
    $adultcount = 0;
    $adultremaincount = 0;
    foreach ($results AS $obj) {
        if ($curchapter !== $obj->Chapter) {
            if ($tablestarted) {
                ?>
<tr>
<th>Chapter Totals:</th>
<th><?php esc_html_e($youthcount) ?></th>
<th><?php esc_html_e($adultslotcount) ?></th>
<th><?php esc_html_e($adultcount) ?></th>
<th><?php esc_html_e($adultremaincount) ?></th>
</tr>
</tbody></table><?php
            }
            $tablestarted = 1;
            $curchapter = $obj->Chapter;
            $youthcount = 0;
            $adultslotcount = 0;
            $adultcount = 0;
            $adultremaincount = 0;
?><table class="oauestats_table">
<thead>
<tr><th colspan="5">Chapter: <?php esc_html_e($obj->Chapter) ?></th></tr>
<tr><th>Unit</th><th>Youth Elected</th><th>Adults Slots Available</th><th>Adults Nominated</th><th>Adults Remaining</th></tr>
</thead><?php
        }
?>
<tbody><?php
        $unit = $obj->Unit_Type . " " . $obj->Unit_Number;
        if ($obj->Unit_Designation) {
            $unit .= "-" . $obj->Unit_Designation;
        }
        $unit .= " (" . $obj->Unit_City . ")";
        $adultslots = ceil(($obj->Elected_Count *2)/3);
        $adultsremain = $adultslots - $obj->Nominated_Count;
        $adultclass = "";
        if ($adultsremain > 0) {
            $adultclass="oauestats_moreadults";
        }
        $youthcount += $obj->Elected_Count;
        $adultslotcount += $adultslots;
        $adultcount += $obj->Nominated_Count;
        $adultremaincount += $adultsremain;
        ?><tr>
<th><?php esc_html_e($unit) ?></th>
<td><?php esc_html_e($obj->Elected_Count) ?></td>
<td><?php esc_html_e($adultslots) ?></td>
<td><?php esc_html_e($obj->Nominated_Count) ?></td>
<td class="<?php esc_attr_e($adultclass) ?>"><?php esc_html_e($adultsremain) ?></td>
</tr><?php
    }
    ?></tbody></table><?php

?><p>Data last updated: <?php esc_html_e(get_option("oauestats_last_import")); ?></p>
    <?php
    return ob_get_clean();
}
