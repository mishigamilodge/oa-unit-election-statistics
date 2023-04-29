# OA Unit Election Statistics

WordPress plugin to generate pretty charts and graphs using unit election statistics exported from OA LodgeMaster.

## See it in action

You can see a live version of this at https://mishigami.org/ue-dashboard/

## Installation

This is NOT ready for a general release yet. If you use it before it's ready, you'll need to manually update from Git periodically. My goal is to eventually have this auto-update like any other plugin.

1. `cd` into the `wp-content/plugins` directory within your WordPress installation.
1. Use `git clone` to check out the source
1. `cd` into the new `oa-unit-election-statistics` folder
1. Run `composer install` to install the dependencies.
1. Go to **Plugins** in the WordPress admin, then Activate the plugin
1. Go to **OA Tools > Import Inductions Data** in the WordPress admin, and follow the instructions there to import data from LodgeMaster.
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

## TODO before this can be considered production quality:

* Update it to use a modern version of Chart.js. I had already working code using an older version so used it in the rush to get it working. The newer version of Chart.js has breaking changes in a few of the APIs I'm using, so it'll need some of the code re-written to use it properly.
* Outfit the plugin with the tooling for WordPress to be able to detect its version and auto-update it.
