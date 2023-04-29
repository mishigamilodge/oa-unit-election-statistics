<?php
/*
 * Copyright (C) 2021 Mishigami Lodge, Order of the Arrow, BSA
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

## BEGIN OA TOOLS MENU CODE

# This code is designed to be used in any OA-related plugin. It conditionally
# Adds an "OA Tools" top-level menu in the WP Admin if it doesn't already
# exist. Any OA-related plugins can then add submenus to it.
# NOTE: if you copy this to another plugin, you also need to copy the
# referenced SVG file.

if (!function_exists('oa_tools_add_menu')) {
    add_action( 'admin_menu', 'oa_tools_add_menu', 9 );
    function oa_tools_add_menu() {
        $oa_tools_icon = file_get_contents("img/oa_trademark.svg", true);
        global $menu;
        $menu_exists = false;
        foreach($menu as $k => $item) {
            if ($item[2] == 'oa_tools') {
                $menu_exists = true;
            }
        }
        if (!$menu_exists) {
            add_menu_page( "OA Tools", "OA Tools", 'none', 'oa_tools', 'oa_tools_menu', 'data:image/svg+xml;base64,' . base64_encode($oa_tools_icon), 3 );
        }
    }
    function oa_tools_menu() {
        # this is a no-op, the page can be blank. It's going to go to the first
        # submenu anyway when it's picked.
    }
}

## END OA TOOLS MENU CODE

add_action('admin_menu', 'oauestats_config_menu', 9);
function oauestats_config_menu() {
    add_submenu_page( "oa_tools", "Upload Induction Data", "Upload Induction Data", 'manage_options', 'oauestats_upload_data', 'oauestats_upload_data');
}
function oauestats_upload_data() {
    global $wpdb;
    $dbprefix = $wpdb->prefix . "oauestats_";

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    // =========================
    // form processing code here
    // =========================

    if (isset($_FILES['oauestats_inductions_file'])) {
        if (preg_match('/\.xlsx$/', $_FILES['oauestats_inductions_file']['name'])) {
            require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

            $objReader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $objReader->setReadDataOnly(true);
            $objReader->setLoadSheetsOnly(array("Data"));
            $objInductSpreadsheet = $objReader->load($_FILES["oauestats_inductions_file"]["tmp_name"]);
            $objInductWorksheet = $objInductSpreadsheet->getActiveSheet();
            $objAdultSpreadsheet = $objReader->load($_FILES["oauestats_nominations_file"]["tmp_name"]);
            $objAdultWorksheet = $objAdultSpreadsheet->getActiveSheet();
            $inductColumnMap = array(
                'Status' => 'Status',
                'District' => 'District',
                'Chapter' => 'Chapter',
                'Unit Location' => 'Unit_Location',
                'Unit Type' => 'Unit_Type',
                'Unit Number' => 'Unit_Number',
                'Unit Designation' => 'Unit_Designation',
                'Unit City' => 'Unit_City',
                'Unit State' => 'Unit_State',
                'Unit County' => 'Unit_County',
                'Visit Type' => 'Visit_Type',
                'Visit Date' => 'Visit_Date',
                'Visit Time' => 'Visit_Time',
                'Virtual Visit' => 'Virtual_Visit',
                'Unit Leader' => 'Unit_Leader',
                'Unit Leader Phone' => 'Unit_Leader_Phone',
                'Unit Leader Email' => 'Unit_Leader_Email',
                'Requester Name' => 'Requester_Name',
                'Requester Phone' => 'Requester_Phone',
                'Requester Email' => 'Requester_Email',
                'Requested Dates' => 'Requested_Dates',
                'Elected Count' => 'Elected_Count',
                'Announcement Status' => 'Announcement_Status',
                'Announcement Date' => 'Announcement_Date',
                'Posted Date' => 'Posted_Date',
                'Approved Date' => 'Approved_Date',
                'Callout Event' => 'Callout_Event',
                'Welcome Event' => 'Welcome_Event',
                'Decline Reason' => 'Decline_Reason',
            );
            $inductColumnSizes = array();
            foreach ($inductColumnMap as $row => $val) {
                $type = $wpdb->get_col($wpdb->prepare("SHOW COLUMNS FROM `${dbprefix}inductions_data` WHERE `Field` = %s", $val), 1);
                $size = 0;
                if (substr($type[0], 0, 7) == "varchar") {
                    preg_match('/\((\d+)\)/', $type[0], $matches);
                    $size = $matches[1];
                }
                $inductColumnSizes[$row] = $size;
            }
            $adultColumnMap = array(
                'Nomination Type' => 'Nomination_Type',
                'Nominated By' => 'Nominated_By',
                'Nominee Full Name' => 'Nominee_Full_Name',
                'Nomination Status' => 'Nomination_Status',
                'BSA Person ID' => 'BSA_Person_ID',
                'Position' => 'Position',
            );
            $adultColumnSizes = array();
            foreach ($adultColumnMap as $row => $val) {
                $type = $wpdb->get_col($wpdb->prepare("SHOW COLUMNS FROM `${dbprefix}nominations_data` WHERE `Field` = %s", $val), 1);
                $size = 0;
                if (substr($type[0], 0, 7) == "varchar") {
                    preg_match('/\((\d+)\)/', $type[0], $matches);
                    $size = $matches[1];
                }
                $adultColumnSizes[$row] = $size;
            }
            $complete = 1;
            $inductrecordcount = 0;
            $adultrecordcount = 0;
            $error_output = "";
            $oauestats_last_import = $wpdb->get_var("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d')");

            foreach ($objInductWorksheet->getRowIterator() as $row) {
                $rowData = array();
                if ($row->getRowIndex() == 1) {
                    # this is the header row, grab the headings
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $cellValue = $cell->getValue();
                        if (isset($inductColumnMap[$cellValue])) {
                            $rowData[$inductColumnMap[$cellValue]] = 1;
                            #echo "Found column " . esc_html($cell->getColumn()) . " with title '" . esc_html($cellValue) . "'<br>" . PHP_EOL;
                        } else {
                            #echo "Discarding unknown column " . esc_html($cell->getColumn()) . " with title '" . esc_html($cellValue) . "'<br>" . PHP_EOL;
                        }
                    }
                    $missingColumns = array();
                    foreach ($inductColumnMap as $key => $value) {
                        if (!isset($rowData[$value])) {
                            $missingColumns[] = $key;
                        }
                    }
                    if ($missingColumns) {
                        error_log("Visit data has missing columns!");
                        ?><div class="error"><p><strong>Visit &amp; Election Management data import failed.</strong></p><p>Missing required columns: <?php esc_html_e(implode(", ", $missingColumns)) ?></div><?php
                        $complete = 0; # Don't show "may have failed" box at the bottom
                        break;
                    } else {
                        #echo "<strong>Data format validated:</strong> Importing new data...<br>" . PHP_EOL;
                        # we just validated that we have a good data file, start handling data
                        $wpdb->show_errors();
                        ob_start();
                        # Make an empty temporary table based on the inductions_data table
                        $wpdb->query("CREATE TEMPORARY TABLE ${dbprefix}inductions_data_temp SELECT * FROM ${dbprefix}inductions_data LIMIT 0");
                        $wpdb->query("ALTER TABLE ${dbprefix}inductions_data_temp CHANGE COLUMN `id` `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT");
                        # now we're ready for the incoming from the rest of the file.
                    }
                } else {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $columnName = $objInductWorksheet->getCell($cell->getColumn() . "1")->getValue();
                        $value = "";
                        if (in_array($columnName, ["Visit Date", "Announcement Date", "Posted Date", "Approved Date"])) {
                            # this is a date field, but can be empty
                            $date = $cell->getValue();
                            if (!$date) {
                                $value = null;
                            } else {
                                $dateint = intval($date);
                                $dateintVal = (int) $dateint;
                                $value = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($dateintVal, "YYYY-MM-DD");
                            }
                        } else {
                            $value = $cell->getValue();
                        }
                        if (isset($inductColumnMap[$columnName])) {
                            $size = $inductColumnSizes[$columnName];
                            if ($size > 0 && $value !== null) {
                                $value = substr($value, 0, $size);
                            }
                            $rowData[$inductColumnMap[$columnName]] = $value;
                        }
                    }
                    if ($wpdb->insert($dbprefix . "inductions_data_temp", $rowData, array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'))) {
                        $inductrecordcount++;
                    }
                }
            }
            foreach ($objAdultWorksheet->getRowIterator() as $row) {
                $rowData = array();
                if ($row->getRowIndex() == 1) {
                    # this is the header row, grab the headings
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $cellValue = $cell->getValue();
                        if (isset($adultColumnMap[$cellValue])) {
                            $rowData[$adultColumnMap[$cellValue]] = 1;
                            #echo "Found column " . esc_html($cell->getColumn()) . " with title '" . esc_html($cellValue) . "'<br>" . PHP_EOL;
                        } else {
                            #echo "Discarding unknown column " . esc_html($cell->getColumn()) . " with title '" . esc_html($cellValue) . "'<br>" . PHP_EOL;
                        }
                    }
                    $missingColumns = array();
                    foreach ($adultColumnMap as $key => $value) {
                        if (!isset($rowData[$value])) {
                            $missingColumns[] = $key;
                        }
                    }
                    if ($missingColumns) {
                        error_log("Adult data has missing columns!");
                        ?><div class="error"><p><strong>Adult Nomination data import failed.</strong></p><p>Missing required columns: <?php esc_html_e(implode(", ", $missingColumns)) ?></div><?php
                        $complete = 0; # Don't show "may have failed" box at the bottom
                        break;
                    } else {
                        #echo "<strong>Adult Data format validated:</strong> Importing new data...<br>" . PHP_EOL;
                        # we just validated that we have a good data file, start handling data
                        $wpdb->show_errors();
                        ob_start();
                        # Make an empty temporary table based on the nominations_data table
                        $wpdb->query("CREATE TEMPORARY TABLE ${dbprefix}nominations_data_temp SELECT * FROM ${dbprefix}nominations_data LIMIT 0");
                        $wpdb->query("ALTER TABLE ${dbprefix}nominations_data_temp CHANGE COLUMN `id` `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT");
                        # now we're ready for the incoming from the rest of the file.
                    }
                } else {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $columnName = $objAdultWorksheet->getCell($cell->getColumn() . "1")->getValue();
                        $value = "";
                        if ($columnName == "Nominated By" && $rowData[$adultColumnMap["Nomination Type"]] === "Unit") {
                            # this is a combined field and we need it separated. What a hack!
                            $unitString = $cell->getValue();
                            # Troop 538-GT - S7 - Tecumseh - Ida
                            preg_match('/^(\S+) (\d+)(?:-(\S\S))? - (.*) - (.*?)$/', $unitString, $unitMatches);
                            $value = $unitString;
                            $rowData["Nominating_Unit_Type"] = $unitMatches[1];
                            $rowData["Nominating_Unit_Number"] = $unitMatches[2];
                            $rowData["Nominating_Unit_Designation"] = $unitMatches[3];
                            $rowData["Nominating_Unit_Chapter"] = $unitMatches[4];
                            $rowData["Nominating_Unit_City"] = $unitMatches[5];
                        } else {
                            $value = $cell->getValue();
                        }
                        if (isset($adultColumnMap[$columnName])) {
                            $size = $adultColumnSizes[$columnName];
                            if ($size > 0 && $value !== null) {
                                $value = substr($value, 0, $size);
                            }
                            $rowData[$adultColumnMap[$columnName]] = $value;
                        }
                    }
                    $wpdb->show_errors();
                    if ($wpdb->insert($dbprefix . "nominations_data_temp", $rowData, array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'))) {
                        $adultrecordcount++;
                    } else {
                        error_log(print_r($rowData, true));
                        esc_html_e($wpdb->print_error());
                    }
                }
            }

            $error_output = ob_get_clean();
            ob_start();
            if (!$error_output) {
                # delete the contents of the live table and copy the contents of the temp table to it
                $wpdb->query("TRUNCATE TABLE ${dbprefix}inductions_data");
                $wpdb->query("INSERT INTO ${dbprefix}inductions_data SELECT * FROM ${dbprefix}inductions_data_temp");
                $wpdb->query("TRUNCATE TABLE ${dbprefix}nominations_data");
                $wpdb->query("INSERT INTO ${dbprefix}nominations_data SELECT * FROM ${dbprefix}nominations_data_temp");
            }
            $error_output .= ob_get_clean();
            if (!$error_output) {
                update_option('oauestats_last_import', $oauestats_last_import);
            }
            if (!$error_output) {
                ?><div class="updated"><p><strong>Import successful. Imported <?php esc_html_e($inductrecordcount) ?> visit records and <?php esc_html_e($adultrecordcount) ?> adult nominations.</strong></p></div><?php
            } else {
                ?><div class="error"><p><strong>Import failed? Imported <?php esc_html_e($inductrecordcount) ?> visit records and <?php esc_html_e($adultrecordcount) ?> adult nominations.</strong></p>
                <p>Errors follow:</p>
                <?php echo $error_output # this is already HTML ?>
                </div><?php
            }
        } else {
            ?><div class="error"><p><strong>Invalid file upload.</strong> Not an XLSX file.</p></div><?php
        }
    }

    // ============================
    // screens and forms start here
    // ============================

    ?>
<div class="wrap">
<h2>Update Inductions Data</h2>
<form action="" method="post" enctype="multipart/form-data">
<p>This requires two files exported from OALM:</p>
<ol>
    <li><label for="oauestats_inductions_file">Inductions &gt; Visit &amp; Election Management &gt; Exports &gt; Unit Visit Data Export
        <input type="file" name="oauestats_inductions_file" id="oauestats_inductions_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"></label></li>
    <li><label for="oauestats_nominations_file">Inductions &gt; Adult Nominations &gt; Exports &gt; Adult Nominations Export
        <input type="file" name="oauestats_nominations_file" id="oauestats_nominations_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"></label></li>
</ol>
<input type="submit" class="button button-primary" name="submit" value="Upload"><br>
</form>
</div>
<p>Data was last updated on <?php esc_html_e(get_option('oauestats_last_import')); ?>.</p>
<h3>Usage</h3>
<p>Each chart or table is a shortcode. You can place the shortcode wherever you want that chart or table to show up, in pages or in posts.  Note that the data will update as you load newer data from LodgeMaster, even if put in a news post with a post date on it.</p>
<dl>
<dt><code>[oauestats_completion_chart]</code></dt>
<dd>Generates a horizontal bar chart of all of your chapters with a bar at the bottom for the entire lodge, which shows the percentage of units which are considered to have completed their election within each chapter and the lodge.</dd>
<dt><code>[oauestats_status_chart]</code></dt>
<dd>Generates a horizontal bar chart of all of your chapters with a bar at the bottom for the entire lodge, which shows the percentage of units in each election status within each chapter and the lodge.</dd>
<dt><code>[oauestats_unit_totals]</code></dt>
<dd>Generates a table separated by chapter listing all of the units who have submitted election reports, listing how many youth were elected, how many adults they're eligible for, how many adults have been nominated so far, and how many adults they can still nominate, with a subtotal for each chapter.</dd>
</dl>

    <?php

}
