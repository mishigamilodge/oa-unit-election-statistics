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


