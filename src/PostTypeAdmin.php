<?php
declare( strict_types=1 );

namespace ExtCPTs;

use WP_Post;
use WP_Query;
use DateTime;
use Exception;

use function p2p_connection_exists;
use function p2p_type;

class PostTypeAdmin {

	/**
	 * Default arguments for custom post types.
	 *
	 * @var array<string,mixed>
	 */
	protected array $defaults = [
		'quick_edit'         => true, # Custom arg
		'dashboard_glance'   => true, # Custom arg
		'dashboard_activity' => true, # Custom arg
		'admin_cols'         => null, # Custom arg
		'admin_filters'      => null, # Custom arg
		'enter_title_here'   => null, # Custom arg
		'block_editor'       => null, # Custom arg
	];

	public PostType $cpt;

	/**
	 * @var array<string,mixed>
	 */
	public array $args;

	/**
	 * @var array<string,string>
	 */
	protected array $_cols;

	/**
	 * @var array<string,string>
	 */
	protected ?array $the_cols = null;

	/**
	 * @var array<string,bool>
	 */
	protected array $connection_exists = [];

	/**
	 * Class constructor.
	 *
	 * @param PostType $cpt  An extended post type object.
	 * @param array<string,mixed>   $args Optional. The post type arguments.
	 */
	public function __construct( PostType $cpt, array $args = [] ) {
		$this->cpt = $cpt;
		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );
	}

	/**
	 * Initialise the admin features of the post type by adding the necessary actions and filters.
	 */
	public function init(): void {
		# Admin columns:
		if ( $this->args['admin_cols'] ) {
			add_filter( 'manage_posts_columns',                                 [ $this, '_log_default_cols' ], 0 );
			add_filter( 'manage_pages_columns',                                 [ $this, '_log_default_cols' ], 0 );
			add_filter( 'manage_media_columns',                                 [ $this, '_log_default_cols' ], 0 );
			if ( 'attachment' === $this->cpt->post_type ) {
				add_filter( 'manage_upload_sortable_columns', [ $this, 'sortables' ] );
				add_filter( 'manage_media_columns',         [ $this, 'cols' ] );
				add_action( 'manage_media_custom_column',   [ $this, 'col' ], 10, 2 );
			} else {
				add_filter( "manage_edit-{$this->cpt->post_type}_sortable_columns", [ $this, 'sortables' ] );
				add_filter( "manage_{$this->cpt->post_type}_posts_columns",         [ $this, 'cols' ] );
				add_action( "manage_{$this->cpt->post_type}_posts_custom_column",   [ $this, 'col' ], 10, 2 );
			}
			add_action( 'load-edit.php',                                        [ $this, 'default_sort' ] );
			add_action( 'pre_get_posts',                                        [ $this, 'maybe_sort_by_fields' ] );
			add_filter( 'posts_clauses',                                        [ $this, 'maybe_sort_by_taxonomy' ], 10, 2 );
		}

		# Admin filters:
		if ( $this->args['admin_filters'] ) {
			add_action( 'load-edit.php',         [ $this, 'default_filter' ] );
			add_action( 'pre_get_posts',         [ $this, 'maybe_filter' ] );
			add_filter( 'query_vars',            [ $this, 'add_query_vars' ] );
			add_action( 'restrict_manage_posts', [ $this, 'filters' ] );
		}

		# 'Enter title here' filter:
		if ( $this->args['enter_title_here'] ) {
			add_filter( 'enter_title_here', [ $this, 'enter_title_here' ], 10, 2 );
		}

		# Block editor filter:
		if ( ! is_null( $this->args['block_editor'] ) && is_bool( $this->args['block_editor'] ) ) {
			add_filter( 'use_block_editor_for_post_type', [ $this, 'block_editor' ], 101, 2 );
		}

		# Hide month filter:
		if ( isset( $this->args['admin_filters']['m'] ) && ! $this->args['admin_filters']['m'] ) {
			add_filter( 'disable_months_dropdown', [ $this, 'filter_disable_months_dropdown' ], 10, 2 );
		}

		# Quick Edit:
		if ( ! $this->args['quick_edit'] ) {
			add_filter( 'post_row_actions',                          [ $this, 'remove_quick_edit_action' ], 10, 2 );
			add_filter( 'page_row_actions',                          [ $this, 'remove_quick_edit_action' ], 10, 2 );
			add_filter( "bulk_actions-edit-{$this->cpt->post_type}", [ $this, 'remove_quick_edit_menu' ] );
		}

		# 'At a Glance' dashboard panels:
		if ( $this->args['dashboard_glance'] ) {
			add_filter( 'dashboard_glance_items', [ $this, 'glance_items' ], $this->cpt->args['menu_position'] );
		}

		# 'Recently Published' dashboard section:
		if ( $this->args['dashboard_activity'] ) {
			add_filter( 'dashboard_recent_posts_query_args', [ $this, 'dashboard_activity' ] );
		}

		# Post updated messages:
		add_filter( 'post_updated_messages',      [ $this, 'post_updated_messages' ], 1 );
		add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_post_updated_messages' ], 1, 2 );

		/**
		 * Fired when the extended post type admin instance is set up.
		 *
		 * @since 5.0.0
		 *
		 * @param \ExtCPTs\PostTypeAdmin $instance The extended post type admin instance.
		 */
		do_action( "ext-cpts/{$this->cpt->post_type}/admin-instance", $this );
	}

	/**
	 * Removes the default 'Months' drop-down from the post list table.
	 *
	 * @param bool   $disable   Whether to disable the drop-down.
	 * @param string $post_type The post type.
	 * @return bool Whether to disable the drop-down.
	 */
	public function filter_disable_months_dropdown( bool $disable, string $post_type ): bool {
		if ( $post_type === $this->cpt->post_type ) {
			return true;
		}

		return $disable;
	}

	/**
	 * Sets the default sort field and sort order on our post type admin screen.
	 */
	public function default_sort(): void {
		if ( self::get_current_post_type() !== $this->cpt->post_type ) {
			return;
		}

		# If we've already ordered the screen, bail out:
		if ( isset( $_GET['orderby'] ) ) {
			return;
		}

		# Loop over our columns to find the default sort column (if there is one):
		foreach ( $this->args['admin_cols'] as $id => $col ) {
			if ( is_array( $col ) && isset( $col['default'] ) ) {
				$_GET['orderby'] = $id;
				$_GET['order'] = ( 'desc' === strtolower( $col['default'] ) ? 'desc' : 'asc' );
				break;
			}
		}
	}

	/**
	 * Sets the default sort field and sort order on our post type admin screen.
	 */
	public function default_filter(): void {
		if ( self::get_current_post_type() !== $this->cpt->post_type ) {
			return;
		}

		# Loop over our filters to find the default filter (if there is one):
		foreach ( $this->args['admin_filters'] as $id => $filter ) {
			if ( isset( $_GET[ $id ] ) && '' !== $_GET[ $id ] ) {
				continue;
			}

			if ( is_array( $filter ) && isset( $filter['default'] ) ) {
				$_GET[ $id ] = $filter['default'];
				return;
			}
		}
	}

	/**
	 * Sets the placeholder text for the title field for this post type.
	 *
	 * @param string  $title The placeholder text.
	 * @param WP_Post $post  The current post.
	 * @return string The updated placeholder text.
	 */
	public function enter_title_here( string $title, WP_Post $post ): string {
		if ( $this->cpt->post_type !== $post->post_type ) {
			return $title;
		}

		return $this->args['enter_title_here'];
	}

	/**
	 * Enable or disable the block editor if it matches this custom post type.
	 *
	 * @param bool   $current_status The current status for the given post type.
	 * @param string $post_type      The current post type.
	 * @return bool The updated status.
	 */
	public function block_editor( bool $current_status, string $post_type ): bool {
		if ( $post_type === $this->cpt->post_type ) {
			return $this->args['block_editor'];
		}

		return $current_status;
	}

	/**
	 * Returns the name of the post type for the current request.
	 *
	 * @return string The post type name.
	 */
	protected static function get_current_post_type(): string {
		if ( function_exists( 'get_current_screen' ) && is_object( get_current_screen() ) && in_array( get_current_screen()->base, [ 'edit', 'upload' ], true ) ) {
			return get_current_screen()->post_type;
		} else {
			return '';
		}
	}

	/**
	 * Outputs custom filter controls on the admin screen for this post type.
	 *
	 * @link https://github.com/johnbillion/extended-cpts/wiki/Admin-filters
	 */
	public function filters(): void {
		global $wpdb;

		if ( self::get_current_post_type() !== $this->cpt->post_type ) {
			return;
		}

		/** @var \WP_Post_Type */
		$pto = get_post_type_object( $this->cpt->post_type );

		foreach ( $this->args['admin_filters'] as $filter_key => $filter ) {
			if ( isset( $filter['cap'] ) && ! current_user_can( $filter['cap'] ) ) {
				continue;
			}

			$id = 'filter_' . $filter_key;

			$hook = "ext-cpts/{$this->cpt->post_type}/filter-output/{$filter_key}";

			if ( has_action( $hook ) ) {
				/**
				 * Allows a filter's output to be overridden.
				 *
				 * @since 4.3.0
				 *
				 * @param \ExtCPTs\PostTypeAdmin $controller The post type admin controller instance.
				 * @param array         $filter     The filter arguments.
				 * @param string        $id         The filter's `id` attribute value.
				 */
				do_action( $hook, $this, $filter, $id );
				continue;
			}

			if ( isset( $filter['taxonomy'] ) ) {
				$tax = get_taxonomy( $filter['taxonomy'] );

				if ( empty( $tax ) ) {
					continue;
				}

				$walker = new Walker\Dropdown(
					[
						'field' => 'slug',
					]
				);

				# If we haven't specified a title, use the all_items label from the taxonomy:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = $tax->labels->all_items;
				}

				printf(
					'<label for="%1$s" class="screen-reader-text">%2$s</label>',
					esc_attr( $id ),
					esc_html( $tax->labels->filter_by ?? $tax->labels->singular_name )
				);

				# Output the dropdown:
				wp_dropdown_categories(
					[
						'show_option_all' => $filter['title'],
						'hide_empty'      => false,
						'hide_if_empty'   => true,
						'hierarchical'    => true,
						'show_count'      => false,
						'orderby'         => 'name',
						'selected_cats'   => $tax->query_var ? get_query_var( $tax->query_var ) : [],
						'id'              => $id,
						'name'            => (string) $tax->query_var,
						'taxonomy'        => $filter['taxonomy'],
						'walker'          => $walker,
					]
				);
			} elseif ( isset( $filter['meta_key'] ) ) {
				# If we haven't specified a title, generate one from the meta key:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = str_replace(
						[
							'-',
							'_',
						],
						' ',
						$filter['meta_key']
					);
					$filter['title'] = ucwords( $filter['title'] ) . 's';
					$filter['title'] = sprintf( 'All %s', $filter['title'] );
				}

				# If we haven't specified a label, generate one from the meta key:
				if ( ! isset( $filter['label'] ) ) {
					$filter['label'] = str_replace(
						[
							'-',
							'_',
						],
						' ',
						$filter['meta_key']
					);
					$filter['label'] = ucwords( $filter['label'] );
					$filter['label'] = sprintf( 'Filter by %s', $filter['label'] );
				}

				if ( ! isset( $filter['options'] ) ) {
					# Fetch all the values for our meta key:
					$filter['options'] = $wpdb->get_col(
						$wpdb->prepare(
							"
								SELECT DISTINCT meta_value
								FROM {$wpdb->postmeta} as m
								JOIN {$wpdb->posts} as p ON ( p.ID = m.post_id )
								WHERE m.meta_key = %s
								AND m.meta_value != ''
								AND p.post_type = %s
								ORDER BY m.meta_value ASC
							",
							$filter['meta_key'],
							$this->cpt->post_type
						)
					);
				} elseif ( is_callable( $filter['options'] ) ) {
					$filter['options'] = call_user_func( $filter['options'] );
				}

				if ( empty( $filter['options'] ) ) {
					continue;
				}

				$selected = wp_unslash( get_query_var( $filter_key ) );

				$use_key = false;

				foreach ( $filter['options'] as $k => $v ) {
					if ( ! is_numeric( $k ) ) {
						$use_key = true;
						break;
					}
				}

				printf(
					'<label for="%1$s" class="screen-reader-text">%2$s</label>',
					esc_attr( $id ),
					esc_html( $filter['label'] )
				);

				# Output the dropdown:
				?>
				<select name="<?php echo esc_attr( $filter_key ); ?>" id="<?php echo esc_attr( $id ); ?>">
					<?php if ( ! isset( $filter['default'] ) ) { ?>
						<option value=""><?php echo esc_html( $filter['title'] ); ?></option>
					<?php } ?>
					<?php
					foreach ( $filter['options'] as $k => $v ) {
						$key = ( $use_key ? $k : $v );
						?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>><?php echo esc_html( $v ); ?></option>
					<?php } ?>
				</select>
				<?php
			} elseif ( isset( $filter['meta_search_key'] ) ) {
				# If we haven't specified a title, generate one from the meta key:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = str_replace(
						[
							'-',
							'_',
						],
						' ',
						$filter['meta_search_key']
					);
					$filter['title'] = ucwords( $filter['title'] );
				}

				$value = wp_unslash( get_query_var( $filter_key ) );

				# Output the search box:
				?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php printf( '%s:', esc_html( $filter['title'] ) ); ?></label>&nbsp;<input type="text" name="<?php echo esc_attr( $filter_key ); ?>" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<?php
			} elseif ( isset( $filter['meta_exists'] ) || isset( $filter['meta_key_exists'] ) ) {
				# If we haven't specified a title, use the all_items label from the post type:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = $pto->labels->all_items;
				}

				$selected = wp_unslash( get_query_var( $filter_key ) );
				$fields = $filter['meta_exists'] ?? $filter['meta_key_exists'];

				if ( 1 === count( $fields ) ) {
					# Output a checkbox:
					foreach ( $fields as $v => $t ) {
						?>
						<input type="checkbox" name="<?php echo esc_attr( $filter_key ); ?>" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $v ); ?>" <?php checked( $selected, $v ); ?>><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $t ); ?></label>
						<?php
					}
				} else {
					if ( ! isset( $filter['label'] ) ) {
						$filter['label'] = $pto->labels->name;
					}

					printf(
						'<label for="%1$s" class="screen-reader-text">%2$s</label>',
						esc_attr( $id ),
						esc_html( $filter['label'] )
					);

					# Output a dropdown:
					?>
					<select name="<?php echo esc_attr( $filter_key ); ?>" id="<?php echo esc_attr( $id ); ?>">
						<?php if ( ! isset( $filter['default'] ) ) { ?>
							<option value=""><?php echo esc_html( $filter['title'] ); ?></option>
						<?php } ?>
						<?php foreach ( $fields as $v => $t ) { ?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $selected, $v ); ?>><?php echo esc_html( $t ); ?></option>
						<?php } ?>
					</select>
					<?php
				}
			} elseif ( isset( $filter['post_date'] ) ) {
				$value = wp_unslash( get_query_var( $filter_key ) );

				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = ucwords( $filter['post_date'] );
				}

				?>
				<label for="<?php echo esc_attr( $id ); ?>"><?php printf( '%s:', esc_html( $filter['title'] ) ); ?></label>&nbsp;
				<input type="date" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $value ); ?>" size="12" placeholder="yyyy-mm-dd" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">
				<?php
			} elseif ( isset( $filter['post_author'] ) ) {
				$value = wp_unslash( get_query_var( 'author' ) );

				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = __( 'All Authors', 'extended-cpts' );
				}

				if ( ! isset( $filter['label'] ) ) {
					$filter['label'] = __( 'Author', 'extended-cpts' );
				}

				printf(
					'<label for="%1$s" class="screen-reader-text">%2$s</label>',
					esc_attr( $id ),
					esc_html( $filter['label'] )
				);

				if ( ! isset( $filter['options'] ) ) {
					# Fetch all the values for our field:
					$filter['options'] = $wpdb->get_col(
						$wpdb->prepare(
							"
								SELECT DISTINCT post_author
								FROM {$wpdb->posts}
								WHERE post_type = %s
							",
							$this->cpt->post_type
						)
					);
				} elseif ( is_callable( $filter['options'] ) ) {
					$filter['options'] = call_user_func( $filter['options'] );
				}

				if ( empty( $filter['options'] ) ) {
					continue;
				}

				# Output a dropdown:
				wp_dropdown_users(
					[
						'id'                => $id,
						'include'           => $filter['options'],
						'name'              => 'author',
						'option_none_value' => '0',
						'selected'          => (int) $value,
						'show_option_none'  => $filter['title'],
					]
				);
			}
		}
	}

	/**
	 * Adds our filter names to the public query vars.
	 *
	 * @param array<int,string> $vars Public query variables
	 * @return array<int,string> Updated public query variables
	 */
	public function add_query_vars( array $vars ): array {
		/** @var array<int,string> */
		$filters = array_keys( $this->args['admin_filters'] );

		return array_merge( $vars, $filters );
	}

	/**
	 * Filters posts by our custom admin filters.
	 *
	 * @param WP_Query $wp_query A `WP_Query` object
	 */
	public function maybe_filter( WP_Query $wp_query ): void {
		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'], true ) ) {
			return;
		}

		$vars = PostType::get_filter_vars( $wp_query->query, $this->cpt->args['admin_filters'], $this->cpt->post_type );

		if ( empty( $vars ) ) {
			return;
		}

		foreach ( $vars as $key => $value ) {
			if ( is_array( $value ) ) {
				$query = $wp_query->get( $key );
				if ( empty( $query ) ) {
					$query = [];
				}
				$value = array_merge( $query, $value );
			}
			$wp_query->set( $key, $value );
		}
	}

	/**
	 * Sets the relevant query vars for sorting posts by our admin sortables.
	 *
	 * @param WP_Query $wp_query The current `WP_Query` object.
	 */
	public function maybe_sort_by_fields( WP_Query $wp_query ): void {
		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'], true ) ) {
			return;
		}

		$sort = PostType::get_sort_field_vars( $wp_query->query, $this->cpt->args['admin_cols'] );

		if ( empty( $sort ) ) {
			return;
		}

		foreach ( $sort as $key => $value ) {
			$wp_query->set( $key, $value );
		}
	}

	/**
	 * Filters the query's SQL clauses so we can sort posts by taxonomy terms.
	 *
	 * @param array<string,string> $clauses  The current query's SQL clauses.
	 * @param WP_Query             $wp_query The current `WP_Query` object.
	 * @return array<string,string> The updated SQL clauses.
	 */
	public function maybe_sort_by_taxonomy( array $clauses, WP_Query $wp_query ): array {
		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'], true ) ) {
			return $clauses;
		}

		$sort = PostType::get_sort_taxonomy_clauses( $clauses, $wp_query->query, $this->cpt->args['admin_cols'] );

		if ( empty( $sort ) ) {
			return $clauses;
		}

		return array_merge( $clauses, $sort );
	}

	/**
	 * Adds our post type to the 'At a Glance' widget on the dashboard.
	 *
	 * @param array<int,string> $items Array of items to display on the widget.
	 * @return array<int,string> Updated array of items.
	 */
	public function glance_items( array $items ): array {
		/** @var \WP_Post_Type */
		$pto = get_post_type_object( $this->cpt->post_type );

		if ( ! current_user_can( $pto->cap->edit_posts ) ) {
			return $items;
		}
		if ( $pto->_builtin ) {
			return $items;
		}

		# Get the labels and format the counts:
		/** @var \stdClass */
		$count = wp_count_posts( $this->cpt->post_type );
		$text = self::n( $pto->labels->singular_name, $pto->labels->name, (int) $count->publish );
		$num = number_format_i18n( $count->publish );

		# This is absolutely not localisable. WordPress 3.8 didn't add a new post type label.
		$url = add_query_arg(
			[
				'post_type' => $this->cpt->post_type,
			],
			admin_url( 'edit.php' )
		);
		$class = 'cpt-' . $this->cpt->post_type . '-count';
		$text = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $num . ' ' . $text ) . '</a>';
		$css = <<<'ICONCSS'
<style>
#dashboard_right_now li a.%1$s:before {
	content: '\%2$s';
}
</style>
ICONCSS;

		// Add styling to display the dashicon. This isn't possible without additional CSS rules.
		// https://core.trac.wordpress.org/ticket/33714
		// https://github.com/WordPress/dashicons/blob/master/codepoints.json
		if ( is_string( $pto->menu_icon ) && 0 === strpos( $pto->menu_icon, 'dashicons-' ) ) {
			$contents = file_get_contents( __DIR__ . '/dashicons-codepoints.json' );
			$codepoints = json_decode( $contents ?: '', true );
			$unprefixed = str_replace( 'dashicons-', '', $pto->menu_icon );

			if ( isset( $codepoints[ $unprefixed ] ) ) {
				$text .= sprintf(
					$css,
					esc_html( $class ),
					esc_html( dechex( $codepoints[ $unprefixed ] ) )
				);
			}
		}

		# Go!
		$items[] = $text;

		return $items;
	}

	/**
	 * Adds our post type to the 'Recently Published' section on the dashboard.
	 *
	 * @param array<string,mixed> $query_args Array of query args for the widget.
	 * @return array<string,mixed> Updated array of query args.
	 */
	public function dashboard_activity( array $query_args ): array {
		$query_args['post_type'] = (array) $query_args['post_type'];

		$query_args['post_type'][] = $this->cpt->post_type;

		return $query_args;
	}

	/**
	 * Adds our post type updated messages.
	 *
	 * The messages are as follows:
	 *
	 *   1 => "Post updated. {View Post}"
	 *   2 => "Custom field updated."
	 *   3 => "Custom field deleted."
	 *   4 => "Post updated."
	 *   5 => "Post restored to revision from [date]."
	 *   6 => "Post published. {View post}"
	 *   7 => "Post saved."
	 *   8 => "Post submitted. {Preview post}"
	 *   9 => "Post scheduled for: [date]. {Preview post}"
	 *  10 => "Post draft updated. {Preview post}"
	 *
	 * @param array<string, array<int, string|false>> $messages An array of post updated message arrays keyed by post type.
	 * @return array<string, array<int, string|false>> Updated array of post updated messages.
	 */
	public function post_updated_messages( array $messages ): array {
		global $post;

		$pto = get_post_type_object( $this->cpt->post_type );

		if ( ! ( $pto instanceof \WP_Post_Type ) ) {
			return $messages;
		}

		$messages[ $this->cpt->post_type ] = [
			1 => sprintf(
				( $pto->publicly_queryable ? '%1$s updated. <a href="%2$s">View %3$s</a>' : '%1$s updated.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( get_permalink( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			2 => 'Custom field updated.',
			3 => 'Custom field deleted.',
			4 => sprintf(
				'%s updated.',
				esc_html( $this->cpt->post_singular )
			),
			5 => isset( $_GET['revision'] ) ? sprintf(
				'%1$s restored to revision from %2$s',
				esc_html( $this->cpt->post_singular ),
				wp_post_revision_title( intval( $_GET['revision'] ), false )
			) : false,
			6 => sprintf(
				( $pto->publicly_queryable ? '%1$s published. <a href="%2$s">View %3$s</a>' : '%1$s published.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( get_permalink( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			7 => sprintf(
				'%s saved.',
				esc_html( $this->cpt->post_singular )
			),
			8 => sprintf(
				( $pto->publicly_queryable ? '%1$s submitted. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s submitted.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( get_preview_post_link( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			9 => sprintf(
				( $pto->publicly_queryable ? '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>' : '%1$s scheduled for: <strong>%2$s</strong>.' ),
				esc_html( $this->cpt->post_singular ),
				esc_html( date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
				esc_url( get_permalink( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			10 => sprintf(
				( $pto->publicly_queryable ? '%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s draft updated.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( get_preview_post_link( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
		];

		return $messages;
	}

	/**
	 * Adds our bulk post type updated messages.
	 *
	 * The messages are as follows:
	 *
	 *  - updated   => "Post updated." | "[n] posts updated."
	 *  - locked    => "Post not updated, somebody is editing it." | "[n] posts not updated, somebody is editing them."
	 *  - deleted   => "Post permanently deleted." | "[n] posts permanently deleted."
	 *  - trashed   => "Post moved to the trash." | "[n] posts moved to the trash."
	 *  - untrashed => "Post restored from the trash." | "[n] posts restored from the trash."
	 *
	 * @param array<string, array<string, string>> $messages An array of bulk post updated message arrays keyed by post type.
	 * @param array<string,int>                    $counts   An array of counts for each key in `$messages`.
	 * @return array<string, array<string, string>> Updated array of bulk post updated messages.
	 */
	public function bulk_post_updated_messages( array $messages, array $counts ): array {
		$messages[ $this->cpt->post_type ] = [
			'updated' => sprintf(
				self::n( '%2$s updated.', '%1$s %3$s updated.', $counts['updated'] ),
				esc_html( number_format_i18n( $counts['updated'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'locked' => sprintf(
				self::n( '%2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $counts['locked'] ),
				esc_html( number_format_i18n( $counts['locked'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'deleted' => sprintf(
				self::n( '%2$s permanently deleted.', '%1$s %3$s permanently deleted.', $counts['deleted'] ),
				esc_html( number_format_i18n( $counts['deleted'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'trashed' => sprintf(
				self::n( '%2$s moved to the trash.', '%1$s %3$s moved to the trash.', $counts['trashed'] ),
				esc_html( number_format_i18n( $counts['trashed'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'untrashed' => sprintf(
				self::n( '%2$s restored from the trash.', '%1$s %3$s restored from the trash.', $counts['untrashed'] ),
				esc_html( number_format_i18n( $counts['untrashed'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
		];

		return $messages;
	}

	/**
	 * Adds our custom columns to the list of sortable columns.
	 *
	 * @param array<string,string> $cols Array of sortable columns keyed by the column ID.
	 * @return array<string,string> Updated array of sortable columns.
	 */
	public function sortables( array $cols ): array {
		$admin_cols = $this->args['admin_cols'];

		/** @var array<string,mixed> $admin_cols */
		foreach ( $admin_cols as $id => $col ) {
			if ( ! is_array( $col ) ) {
				continue;
			}
			if ( isset( $col['sortable'] ) && ! $col['sortable'] ) {
				continue;
			}
			if ( isset( $col['meta_key'] ) || isset( $col['taxonomy'] ) || isset( $col['post_field'] ) ) {
				$cols[ $id ] = $id;
			}
		}

		return $cols;
	}

	/**
	 * Adds columns to the admin screen for this post type.
	 *
	 * @link https://github.com/johnbillion/extended-cpts/wiki/Admin-columns
	 *
	 * @param array<string,string> $cols Associative array of columns
	 * @return array<string,string> Updated array of columns
	 */
	public function cols( array $cols ): array {
		// This function gets called multiple times, so let's cache it for efficiency:
		if ( isset( $this->the_cols ) ) {
			return $this->the_cols;
		}

		$new_cols = [];
		$keep = [
			'cb',
			'title',
		];

		# Add existing columns we want to keep:
		foreach ( $cols as $id => $title ) {
			if ( in_array( $id, $keep, true ) && ! isset( $this->args['admin_cols'][ $id ] ) ) {
				$new_cols[ $id ] = $title;
			}
		}

		/** @var array<string,(string|mixed[])> */
		$admin_cols = array_filter( $this->args['admin_cols'] );

		# Add our custom columns:
		foreach ( $admin_cols as $id => $col ) {
			if ( is_string( $col ) && isset( $cols[ $col ] ) ) {
				# Existing (ie. built-in) column with id as the value
				$new_cols[ $col ] = $cols[ $col ];
			} elseif ( is_string( $col ) && isset( $cols[ $id ] ) ) {
				# Existing (ie. built-in) column with id as the key and title as the value
				$new_cols[ $id ] = esc_html( $col );
			} elseif ( 'author' === $col ) {
				# Automatic support for Co-Authors Plus plugin and special case for
				# displaying author column when the post type doesn't support 'author'
				if ( class_exists( 'coauthors_plus' ) ) {
					$k = 'coauthors';
				} else {
					$k = 'author';
				}
				$new_cols[ $k ] = esc_html__( 'Author', 'extended-cpts' );
			} elseif ( is_array( $col ) ) {
				if ( isset( $col['cap'] ) && ! current_user_can( $col['cap'] ) ) {
					continue;
				}
				if ( isset( $col['connection'] ) && ! function_exists( 'p2p_type' ) ) {
					continue;
				}

				if ( isset( $col['title_cb'] ) ) {
					$new_cols[ $id ] = call_user_func( $col['title_cb'], $col );
				} else {
					$title = esc_html( $this->get_item_title( $col, $id ) );

					if ( isset( $col['title_icon'] ) ) {
						$title = sprintf(
							'<span class="dashicons %s" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
							esc_attr( $col['title_icon'] ),
							$title
						);
					}

					$new_cols[ $id ] = $title;
				}
			}
		}

		# Re-add any custom columns:
		$custom = array_diff_key( $cols, $this->_cols );
		$new_cols = array_merge( $new_cols, $custom );

		$this->the_cols = $new_cols;
		return $this->the_cols;
	}

	/**
	 * Output the column data for our custom columns.
	 *
	 * @param string $col     The column name.
	 * @param int    $post_id The post ID.
	 */
	public function col( string $col, int $post_id ): void {
		# Shorthand:
		$c = $this->args['admin_cols'];

		# We're only interested in our custom columns:
		$custom_cols = array_filter( array_keys( $c ) );

		if ( ! in_array( $col, $custom_cols, true ) ) {
			return;
		}

		if ( isset( $c[ $col ]['post_cap'] ) && ! current_user_can( $c[ $col ]['post_cap'], get_the_ID() ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		if ( ! isset( $c[ $col ]['link'] ) ) {
			$c[ $col ]['link'] = 'list';
		}

		if ( isset( $c[ $col ]['function'] ) ) {
			call_user_func( $c[ $col ]['function'], $post );
		} elseif ( isset( $c[ $col ]['meta_key'] ) ) {
			$this->col_post_meta( $post, $c[ $col ]['meta_key'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['taxonomy'] ) ) {
			$this->col_taxonomy( $post, $c[ $col ]['taxonomy'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['post_field'] ) ) {
			$this->col_post_field( $post, $c[ $col ]['post_field'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['featured_image'] ) ) {
			$this->col_featured_image( $post, $c[ $col ]['featured_image'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['connection'] ) ) {
			$this->col_connection( $post, $c[ $col ]['connection'], $c[ $col ] );
		}
	}

	/**
	 * Outputs column data for a post meta field.
	 *
	 * @param WP_Post             $post     The post object.
	 * @param string              $meta_key The post meta key.
	 * @param array<string,mixed> $args     Array of arguments for this field.
	 */
	public function col_post_meta( WP_Post $post, string $meta_key, array $args ): void {
		$vals = get_post_meta( $post->ID, $meta_key, false );
		$echo = [];

		sort( $vals );

		if ( isset( $args['date_format'] ) ) {
			if ( true === $args['date_format'] ) {
				$args['date_format'] = get_option( 'date_format' );
			}

			foreach ( $vals as $val ) {
				try {
					$val_time = ( new DateTime( '@' . $val ) )->format( 'U' );
				} catch ( Exception $e ) {
					$val_time = strtotime( $val );
				}

				if ( false !== $val_time ) {
					$val = $val_time;
				}

				if ( is_numeric( $val ) ) {
					$echo[] = date_i18n( $args['date_format'], (int) $val );
				} elseif ( ! empty( $val ) ) {
					$echo[] = mysql2date( $args['date_format'], $val );
				}
			}
		} else {
			foreach ( $vals as $val ) {

				if ( ! empty( $val ) || ( '0' === $val ) ) {
					$echo[] = $val;
				}
			}
		}

		if ( empty( $echo ) ) {
			echo '&#8212;';
		} else {
			echo esc_html( implode( ', ', $echo ) );
		}
	}

	/**
	 * Outputs column data for a taxonomy's term names.
	 *
	 * @param WP_Post             $post     The post object.
	 * @param string              $taxonomy The taxonomy name.
	 * @param array<string,mixed> $args     Array of arguments for this field.
	 */
	public function col_taxonomy( WP_Post $post, string $taxonomy, array $args ): void {
		$terms = get_the_terms( $post, $taxonomy );
		$tax = get_taxonomy( $taxonomy );

		if ( is_wp_error( $terms ) ) {
			echo esc_html( $terms->get_error_message() );
			return;
		}

		if ( ! $tax ) {
			return;
		}

		if ( empty( $terms ) ) {
			printf(
				'<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>',
				esc_html( $tax->labels->no_terms )
			);
			return;
		}

		$out = [];

		foreach ( $terms as $term ) {
			if ( $args['link'] ) {
				switch ( $args['link'] ) {

					case 'view':
						if ( $tax->public ) {
							// https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1096
							// @codingStandardsIgnoreStart
							$out[] = sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( get_term_link( $term ) ),
								esc_html( $term->name )
							);
							// @codingStandardsIgnoreEnd
						} else {
							$out[] = esc_html( $term->name );
						}
						break;

					case 'edit':
						if ( current_user_can( $tax->cap->edit_terms ) ) {
							$out[] = sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( get_edit_term_link( $term->term_id, $taxonomy, $post->post_type ) ),
								esc_html( $term->name )
							);
						} else {
							$out[] = esc_html( $term->name );
						}
						break;

					case 'list':
						$link = add_query_arg(
							[
								'post_type' => $post->post_type,
								$taxonomy => $term->slug,
							],
							admin_url( 'edit.php' )
						);
						$out[] = sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( $link ),
							esc_html( $term->name )
						);
						break;

				}
			} else {
				$out[] = esc_html( $term->name );
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( ', ', $out );
	}

	/**
	 * Outputs column data for a post field.
	 *
	 * @param WP_Post             $post  The post object.
	 * @param string              $field The post field.
	 * @param array<string,mixed> $args  Array of arguments for this field.
	 */
	public function col_post_field( WP_Post $post, string $field, array $args ): void {
		switch ( $field ) {

			case 'post_date':
			case 'post_date_gmt':
			case 'post_modified':
			case 'post_modified_gmt':
				if ( '0000-00-00 00:00:00' !== get_post_field( $field, $post ) ) {
					if ( ! isset( $args['date_format'] ) ) {
						$args['date_format'] = get_option( 'date_format' );
					}
					echo esc_html( mysql2date( $args['date_format'], get_post_field( $field, $post ) ) );
				}
				break;

			case 'post_status':
				/** @var \stdClass|null */
				$status = get_post_status_object( $post->post_status );
				if ( $status ) {
					echo esc_html( $status->label );
				}
				break;

			case 'post_author':
				$author = get_the_author();

				if ( $author ) {
					echo esc_html( $author );
				}
				break;

			case 'post_title':
				echo esc_html( get_the_title() );
				break;

			case 'post_excerpt':
				echo esc_html( get_the_excerpt() );
				break;

			default:
				echo esc_html( get_post_field( $field, $post ) );
				break;

		}
	}

	/**
	 * Outputs column data for a post's featured image.
	 *
	 * @param WP_Post                  $post       The post object.
	 * @param string                   $image_size The image size.
	 * @param array<string,string|int> $args       Array of `width` and `height` attributes for the image.
	 */
	public function col_featured_image( WP_Post $post, string $image_size, array $args ): void {
		if ( ! function_exists( 'has_post_thumbnail' ) ) {
			return;
		}

		if ( isset( $args['width'] ) ) {
			$width = is_numeric( $args['width'] ) ? sprintf( '%dpx', $args['width'] ) : $args['width'];
		} else {
			$width = 'auto';
		}

		if ( isset( $args['height'] ) ) {
			$height = is_numeric( $args['height'] ) ? sprintf( '%dpx', $args['height'] ) : $args['height'];
		} else {
			$height = 'auto';
		}

		$image_atts = [
			'style' => esc_attr(
				sprintf(
					'width:%1$s;height:%2$s',
					$width,
					$height
				)
			),
			'title' => '',
		];

		if ( has_post_thumbnail() ) {
			the_post_thumbnail( $image_size, $image_atts );
		}
	}

	/**
	 * Outputs column data for a Posts 2 Posts connection.
	 *
	 * @param WP_Post             $post_object The post object.
	 * @param string              $connection  The ID of the connection type.
	 * @param array<string,mixed> $args        Array of arguments for a given connection type.
	 */
	public function col_connection( WP_Post $post_object, string $connection, array $args ): void {
		global $post;

		if ( ! function_exists( 'p2p_type' ) ) {
			return;
		}

		if ( ! $this->p2p_connection_exists( $connection ) ) {
			echo esc_html(
				sprintf(
					/* translators: %s: The ID of the Posts 2 Posts connection type */
					__( 'Invalid connection type: %s', 'extended-cpts' ),
					$connection
				)
			);
			return;
		}

		$_post = $post;
		$meta = [];
		$out = [];
		$field = 'connected_' . $connection;

		if ( isset( $args['field'] ) && isset( $args['value'] ) ) {
			$meta = [
				'connected_meta' => [
					$args['field'] => $args['value'],
				],
			];
			$field .= sanitize_title( '_' . $args['field'] . '_' . $args['value'] );
		}

		if ( ! isset( $post_object->$field ) ) {
			$type = p2p_type( $connection );
			if ( $type ) {
				$type->each_connected( [ $post_object ], $meta, $field );
			} else {
				echo esc_html(
					sprintf(
						/* translators: %s: The ID of the Posts 2 Posts connection type */
						__( 'Invalid connection type: %s', 'extended-cpts' ),
						$connection
					)
				);
				return;
			}
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		foreach ( $post_object->$field as $post ) {
			setup_postdata( $post );

			/** @var \WP_Post_Type */
			$pto = get_post_type_object( $post->post_type );
			/** @var \stdClass */
			$pso = get_post_status_object( $post->post_status );

			if ( $pso->protected && ! current_user_can( 'edit_post', $post->ID ) ) {
				continue;
			}
			if ( 'trash' === $post->post_status ) {
				continue;
			}

			if ( $args['link'] ) {
				switch ( $args['link'] ) {

					case 'view':
						if ( $pto->public ) {
							if ( $pso->protected ) {
								$out[] = sprintf(
									'<a href="%1$s">%2$s</a>',
									esc_url( get_preview_post_link() ),
									esc_html( get_the_title() )
								);
							} else {
								$out[] = sprintf(
									'<a href="%1$s">%2$s</a>',
									esc_url( get_permalink() ),
									esc_html( get_the_title() )
								);
							}
						} else {
							$out[] = esc_html( get_the_title() );
						}
						break;

					case 'edit':
						if ( current_user_can( 'edit_post', $post->ID ) ) {
							$out[] = sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( get_edit_post_link() ),
								esc_html( get_the_title() )
							);
						} else {
							$out[] = esc_html( get_the_title() );
						}
						break;

					case 'list':
						$link = add_query_arg(
							array_merge(
								[
									'post_type'       => $_post->post_type,
									'connected_type'  => $connection,
									'connected_items' => $post->ID,
								],
								$meta
							),
							admin_url( 'edit.php' )
						);
						$out[] = sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( $link ),
							esc_html( get_the_title() )
						);
						break;

				}
			} else {
				$out[] = esc_html( get_the_title() );
			}
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $_post;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( ', ', $out );
	}

	/**
	 * Removes the Quick Edit link from the post row actions.
	 *
	 * @param array<string,string> $actions Array of post actions.
	 * @param WP_Post              $post    The current post object.
	 * @return array<string,string> Array of updated post actions.
	 */
	public function remove_quick_edit_action( array $actions, WP_Post $post ): array {
		if ( $this->cpt->post_type !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['inline'], $actions['inline hide-if-no-js'] );

		return $actions;
	}

	/**
	 * Removes the Quick Edit link from the bulk actions menu.
	 *
	 * @param array<string,string> $actions Array of bulk actions.
	 * @return array<string,string> Array of updated bulk actions.
	 */
	public function remove_quick_edit_menu( array $actions ): array {
		unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * Logs the default columns so we don't remove any custom columns added by other plugins.
	 *
	 * @param array<string,string> $cols The default columns for this post type screen.
	 * @return array<string,string> The default columns for this post type screen.
	 */
	public function _log_default_cols( array $cols ): array {
		$this->_cols = $cols;

		return $this->_cols;
	}

	/**
	 * A non-localised version of _n()
	 *
	 * @param string $single The text that will be used if $number is 1.
	 * @param string $plural The text that will be used if $number is not 1.
	 * @param int    $number The number to compare against to use either `$single` or `$plural`.
	 * @return string Either `$single` or `$plural` text.
	 */
	protected static function n( string $single, string $plural, int $number ): string {
		return ( 1 === intval( $number ) ) ? $single : $plural;
	}

	/**
	 * Returns a sensible title for the current item (usually the arguments array for a column)
	 *
	 * @param array<string,mixed> $item     An array of arguments.
	 * @param string              $fallback Fallback item title.
	 * @return string The item title.
	 */
	protected function get_item_title( array $item, string $fallback = '' ): string {
		if ( isset( $item['title'] ) ) {
			return $item['title'];
		} elseif ( isset( $item['taxonomy'] ) ) {
			$tax = get_taxonomy( $item['taxonomy'] );
			if ( $tax ) {
				if ( ! empty( $tax->exclusive ) ) {
					return $tax->labels->singular_name;
				} else {
					return $tax->labels->name;
				}
			} else {
				return $item['taxonomy'];
			}
		} elseif ( isset( $item['post_field'] ) ) {
			return ucwords(
				trim(
					str_replace(
						[
							'post_',
							'_',
						],
						' ',
						$item['post_field']
					)
				)
			);
		} elseif ( isset( $item['meta_key'] ) ) {
			return ucwords(
				trim(
					str_replace(
						[
							'_',
							'-',
						],
						' ',
						$item['meta_key']
					)
				)
			);
		} elseif ( isset( $item['connection'] ) && isset( $item['field'] ) && isset( $item['value'] ) ) {
			$fallback = ucwords(
				trim(
					str_replace(
						[
							'_',
							'-',
						],
						' ',
						$item['value']
					)
				)
			);

			if ( ! function_exists( 'p2p_type' ) || ! $this->p2p_connection_exists( $item['connection'] ) ) {
				return $fallback;
			}

			$ctype = p2p_type( $item['connection'] );
			if ( ! $ctype ) {
				return $fallback;
			}

			if ( isset( $ctype->fields[ $item['field'] ]['values'][ $item['value'] ] ) ) {
				if ( '' === trim( $ctype->fields[ $item['field'] ]['values'][ $item['value'] ] ) ) {
					return $ctype->fields[ $item['field'] ]['title'];
				} else {
					return $ctype->fields[ $item['field'] ]['values'][ $item['value'] ];
				}
			}

			return $fallback;
		} elseif ( isset( $item['connection'] ) ) {
			if ( function_exists( 'p2p_type' ) && $this->p2p_connection_exists( $item['connection'] ) ) {
				$ctype = p2p_type( $item['connection'] );
				if ( $ctype ) {
					$other = ( 'from' === $ctype->direction_from_types( 'post', $this->cpt->post_type ) ) ? 'to' : 'from';
					return $ctype->side[ $other ]->get_title();
				}
			}
			return $item['connection'];
		}

		return $fallback;
	}

	/**
	 * Checks if a certain Posts 2 Posts connection exists.
	 *
	 * This is just a caching wrapper for `p2p_connection_exists()`, which performs a
	 * database query on every call.
	 *
	 * @param string $connection A connection type.
	 * @return bool Whether the connection exists.
	 */
	protected function p2p_connection_exists( string $connection ): bool {
		if ( ! isset( $this->connection_exists[ $connection ] ) ) {
			$this->connection_exists[ $connection ] = p2p_connection_exists( $connection );
		}

		return $this->connection_exists[ $connection ];
	}

}
