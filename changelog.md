
## Changelog ##

### 2.4 ###

* Support for custom post type permastructs.
* Automatic integration with the [Rewrite Rule Testing](https://wordpress.org/plugins/rewrite-testing/) plugin.
* Query variables for front-end filtering via the `site_filters` argument.
* Query variables for front-end sorting via the `site_sortables` argument.
* Filters for the post type arguments and names (`ext-cpts/{$post_type}/args` and `ext-cpts/{$post_type}/names`).
* Support for outputting multiple values per key in post meta admin columns.
* The default value of the `link` argument for admin columns is now `list`.
* The `filters` argument has been renamed `admin_filters`.
* The `cols` argument has been renamed `admin_cols`.
* The `right_now` argument has been renamed `dashboard_glance`.
* Remove the backwards compatibility with pre-2.3 plural, slug, and singular arguments.

### 2.3.3 ###

* Added `type` of `wordpress-plugin` to composer.json.

### 2.3.2 ###

* Remove the `autoload` section from composer.json.

### 2.3.1 ###

* Update the bulk post update messages code for WordPress 3.7.
* Added composer.json.

### 2.3 ###

* Code overhaul to split the admin area functionality into its own class.

### 2.2.3 ###

* Remove some notices on AJAX requests.

### 2.2.2 ###

* New `post_cap` column argument for controlling output based on a user's capabilities for each post.
* Improve column output for non-public/draft/scheduled/etc posts and terms.
* Raise an error if post type query var clashes with an existing taxonomy.

### 2.2.1 ###

* Fix columns on Page listing screen.
* Don't give featured image column a default title.
* Don't suppress notices for invalid post field parameters.

### 2.2 ###

* New 'link' argument for specifying the link type (view/edit/list) on taxonomy term and P2P columns.
* More automatic column title generation.

### 2.1.8 ###

* Allow an array of options to be passed to a post meta filter dropdown.
* New post type listing screen filter: 'meta_search_key' for searching on a given post meta field.

### 2.1.7 ###

* New 'enter_title_here' and 'featured_image' arguments for overriding the respective labels.
* New 'cap' argument for only displaying post type listing screen filters to users with the corresponding capability.
* Display a checkbox instead of a dropdown for the 'meta_exists' filter when appropriate.
* Prevent a column from being sortable with the 'sortable' boolean argument.
* Allow checkbox and title columns to eb removed with boolean false.
* Automatic support for the Co-Authors plus plugin (preserves your chosen column order).
* More automatic column title generation.

### 2.1.6 ###

* Allow displaying the Author column even if the post type doesn't support 'author'.
* Improved plural, singular and slug generation.
* Allow extending an existing post type simply by calling register_extended_post_type() on it.

### 2.1.5 ###

* New 'quick_edit' argument for disabling Quick Edit for the given post type.
* Fix post type feeds when the 'show_in_feed' argument is used.
* New 'add_taxonomy' class method for registering and adding a taxonomy to the post type.

### 2.1.4 ###

* New 'date_format' arg for formatting a post meta value as a date field (supports MySQL and Unix-style date formats).

### 2.1.3 ###

* Improved singular name generation.
* Avoid potential fatal errors with P2P and post thumbnails.

### 2.1.1 ###

* Avoid overwriting custom columns from other plugins.

### 2.1 ###

* Add a 'cap' argument for restricting column display to users with the corresponding capability.
* Improved formatting when displaying various post fields such as date, status and author.

### 2.0 ###

* Support for displaying columns showing connections from the Posts 2 Posts plugin.
* More automatically generated column titles.

### 1.9.4 ###

* New 'meta_exists' post type listing screen filter. Filters posts which have a meta field of the given key and the value is considered non-empty.
* New 'featured_image' column type for displaying the featured image at the given size.

### 1.9.3 ###

* Allow register_extended_post_type() to be called on or before the init hook.

### 1.9.2 ###

* Add new 'archive_in_menu' arg for adding a link to the post type archive to the nav menu management screen.

### 1.9.1 ###

* Add support for sorting columns by post field.

### 1.9 ###

* New post type listing screen filter: filter by available values for a given post meta key.
* Improved query for ordering by taxonomy terms.

### 1.8 ###

* New 'archive' argument for overriding any query variable on post type archive pages.

### 1.7.9 ###

* Improved plural, singular and slug name generation.

### 1.7.8 ###

* Add support for showing the post type in the Right Now dashboard widget.

### 1.7.7 ###

* Automatic post type listing screen column support for meta fields, taxonomy terms and post fields.
* Preemptive support for [bulk updated messages](https://core.trac.wordpress.org/ticket/18710).

### 1.7.4 ###

* Our first post type listing screen filter: a dropdown for filtering by taxonomy.
* Improved defaults for rewrites.
* Improved query for ordering by taxo terms

### 1.7.3 ###

* Add a 'show_in_feed' parameter for controlling post type visibility in the main site feed.

### 1.7.2 ###

* Add the admin bar label name.

### 1.7.1 ###

* Register post types with an earlier priority on init.

### 1.7 ###

* Oldest version that isn't lost to the confines of old client repos.
