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

global $oauestats_db_version;
$oauestats_db_version = 2;

function oauestats_create_table($ddl)
{
    global $wpdb;
    $table = "";
    if (preg_match("/create table\s+`?(\w+)`?\s/i", $ddl, $match)) {
        $table = $match[1];
    } else {
        return false;
    }
    foreach ($wpdb->get_col("SHOW TABLES", 0) as $tbl) {
        if ($tbl == $table) {
            return true;
        }
    }
    // if we get here it doesn't exist yet, so create it
    $wpdb->query($ddl);
    // check if it worked
    foreach ($wpdb->get_col("SHOW TABLES", 0) as $tbl) {
        if ($tbl == $table) {
            return true;
        }
    }
    return false;
}

register_activation_hook(__FILE__, 'oauestats_install');
function oauestats_install()
{
    /* Reference: http://codex.wordpress.org/Creating_Tables_with_Plugins */

    global $wpdb;
    global $oauestats_db_version;

    $dbprefix = $wpdb->prefix . "oauestats_";

    //
    // CREATE THE TABLES IF THEY DON'T EXIST
    //

    // This code checks if each table exists, and creates it if it doesn't.
    // No checks are made that the DDL for the table actually matches,
    // only if it doesn't exist yet. If the columns or indexes need to
    // change it'll need update code (see below).

    $sql = "CREATE TABLE `{$dbprefix}inductions_data` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Status` VARCHAR(20) NOT NULL,
  `District` VARCHAR(30) NOT NULL,
  `Chapter` VARCHAR(80) NOT NULL,
  `Unit_Location` VARCHAR(80),
  `Unit_Type` VARCHAR(10) NOT NULL,
  `Unit_Number` INT(6) NOT NULL,
  `Unit_Designation` VARCHAR(3),
  `Unit_City` VARCHAR(80),
  `Unit_State` VARCHAR(30),
  `Unit_County` VARCHAR(30),
  `Visit_Type` VARCHAR(30),
  `Visit_Date` DATE,
  `Visit_Time` VARCHAR(10),
  `Virtual_Visit` VARCHAR(10),
  `Unit_Leader` VARCHAR(80),
  `Unit_Leader_Phone` VARCHAR(30),
  `Unit_Leader_Email` VARCHAR(80),
  `Requester_Name` VARCHAR(80),
  `Requester_Phone` VARCHAR(30),
  `Requester_Email` VARCHAR(80),
  `Requested_Dates` VARCHAR(30),
  `Elected_Count` INT(11),
  `Announcement_Status` VARCHAR(30),
  `Announcement_Date` DATE,
  `Posted_Date` DATE,
  `Approved_Date` DATE,
  `Callout_Event` VARCHAR(150),
  `Welcome_Event` VARCHAR(150),
  `Decline_Reason` VARCHAR(150)
   );";
    if (!oauestats_create_table($sql)) {
        return false;
    }

    $sql = "CREATE TABLE `{$dbprefix}nominations_data` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Nomination_Type` VARCHAR(30) NOT NULL,
  `Nominated_By` VARCHAR(150) NOT NULL,
  `Nominating_Unit_Type` VARCHAR(10),
  `Nominating_Unit_Number` INT(6),
  `Nominating_Unit_Designation` VARCHAR(3),
  `Nominating_Unit_Chapter` VARCHAR(80),
  `Nominating_Unit_City` VARCHAR(80),
  `Nominee_Full_Name` VARCHAR(80) NOT NULL,
  `Nomination_Status` VARCHAR(10) NOT NULL,
  `BSA_Person_ID` INT(10),
  `Position` VARCHAR(150)
    );";
    if (!oauestats_create_table($sql)) {
        return false;
    }

    //
    // DATABASE UPDATE CODE
    //

    // Check the stored database schema version and compare it to the version
    // required for this version of the plugin.  Run any SQL updates required
    // to bring the DB schema into compliance with the current version.
    // If new tables are created, you don't need to do anything about that
    // here, since the table code above takes care of that.  All that needs
    // to be done here is to make any required changes to existing tables.
    // Don't forget that any changes made here also need to be made to the DDL
    // for the tables above.

    $installed_version = get_option("oauestats_db_version");
    if (empty($installed_version)) {
        // if we get here, it's a new install, and the schema will be correct
        // from the initialization of the tables above, so make it the
        // current version so we don't run any update code.
        $installed_version = $oauestats_db_version;
        add_option("oauestats_db_version", $oauestats_db_version);
    }

    if ($installed_version < 2) {
        # run code for updating from schema version 1 to version 2 here.
        # Make the unit number be an integer instead of a string, so it'll sort in the correct order.
        $wpdb->query("ALTER TABLE `{$dbprefix}inductions_data` CHANGE COLUMN `Unit_Number` `Unit_Number` INT(6) NOT NULL");
        $wpdb->query("ALTER TABLE `{$dbprefix}nominations_data` CHANGE COLUMN `Nominating_Unit_Number` `Nominating_Unit_Number` INT(6) NOT NULL");
    }

    # if ($installed_version < 3) {
    #     # run code for updating from schema version 2 to version 3 here.
    # }

    # insert next database revision update code immediately above this line.
    # don't forget to increment $oauestats_db_version at the top of the file.
    if ($installed_version < $oauestats_db_version) {
        // updates are done, update the schema version to say we did them
        update_option("oauestats_db_version", $oauestats_db_version);
    }
}

add_action('plugins_loaded', 'oauestats_update_db_check');
function oauestats_update_db_check()
{
    # first: if the database schema in the code doesn't match what's in the DB
    # go upgrade it
    global $oauestats_db_version;
    if (get_option("oauestats_db_version") != $oauestats_db_version) {
        oauestats_install();
    }
    # second: set up any defaults for the settings the plugin uses
    # add_option does nothing if the option already exists, sets default value
    # if it does not.
    add_option('oauestats_last_import', '1900-01-01');
}

