<?php
/*
 * Plugin Name: OA Unit Election Statistics
 * Plugin URI: https://github.com/mishigamilodge/oa-unit-election-statistics
 * Description: Upload data from your Inductions module and see pretty graphs
 * Version: 1.2
 * Requires at least: 5.2
 * Tested up to: 6.3.2
 * Requires PHP: 7.4
 * Author: Mishigami Lodge
 * Author URI: https://mishigami.org/
 * Author Email: codemonkeys@mishigami.org
 * */

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

// All of the meat is in the includes directory, to keep it organized.
// Just pull it all in from here.
require_once("includes/initial-setup.php");
require_once("includes/admin-settings.php");
require_once("includes/status-chart.php");
require_once("includes/completion-chart.php");
require_once("includes/unit-totals.php");
