# OA Unit Election Statistics

WordPress plugin to generate pretty charts and graphs using unit election statistics exported from OA LodgeMaster.

## See it in action

You can see a live version of this at https://mishigami.org/ue-dashboard/

## Installation

This has not yet been uploaded to the WordPress Plugin Directory, which means it will not auto-update. Until that happens, you'll need to check the GitHub page to see if there's a new release. It should update itself once it shows up in the Plugin Directory.

### The easy way

1. Download the zip file from the Releases page on GitHub
1. upload it to the Plugins screen in the WordPress admin section.
1. Go to **Plugins** in the WordPress admin, then Activate the plugin
1. Go to **OA Tools > Upload Inductions Data** in the WordPress admin, and follow the instructions there to import data from LodgeMaster.
1. Create one or more pages for your charts or tables, and put the shortcodes where you want them to show up.

### If you want to work on the code

1. `cd` into the `wp-content/plugins` directory within your WordPress installation.
1. Use `git clone` to check out the source from GitHub
1. `cd` into the new `oa-unit-election-statistics` folder
1. Run `composer install` to install the dependencies.
1. Go to **Plugins** in the WordPress admin, then Activate the plugin
1. Go to **OA Tools > Upload Inductions Data** in the WordPress admin, and follow the instructions there to import data from LodgeMaster.
1. Create one or more pages for your charts or tables, and put the shortcodes where you want them to show up.

## Usage

Each chart or table is a shortcode. You can place the shortcode wherever you want that chart or table to show up, in pages or in posts.  Note that the data will update as you load newer data from LodgeMaster, even if put in a news post with a post date on it.
<dl>
<dt><code>[oauestats_completion_chart]</code></dt>
<dd>Generates a horizontal bar chart of all of your chapters with a bar at the bottom for the entire lodge, which shows the percentage of units which are considered to have completed their election within each chapter and the lodge.</dd>
<dt><code>[oauestats_status_chart]</code></dt>
<dd>Generates a horizontal bar chart of all of your chapters with a bar at the bottom for the entire lodge, which shows the percentage of units in each election status within each chapter and the lodge.</dd>
<dt><code>[oauestats_unit_totals]</code></dt>
<dd>Generates a table separated by chapter listing all of the units who have submitted election reports, listing how many youth were elected, how many adults they're eligible for, how many adults have been nominated so far, and how many adults they can still nominate, with a subtotal for each chapter.</dd>
</dl>

