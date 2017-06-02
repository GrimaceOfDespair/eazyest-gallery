<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * Eazyest_Folder_List_Table
 * This is a highly modified WP_List_Table/WP_Posts_List_Table, to display alongside Eazyest_Media_List_Table
 *
 * @package Eazyest Gallery
 * @subpackage List Table
 * @author Marcel Brinkkemper
 * @copyright 2012 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.2.0 (r323)
 * @access public
 * @see WordPress WP_List_Table
 * @link http://codex.wordpress.org/Class_Reference/WP_List_Table
 */
class Eazyest_Folder_List_Table extends WP_List_Table {

	/**
	 * Whether the items should be displayed hierarchically or linearly
	 *
	 * @since 0.1.0 (r2)
	 * @var bool $hierarchical_display
	 * @access protected
	 */
	protected $hierarchical_display;

	/**
	 * Eazyest_Folder_List_Table::__construct()
	 *
	 * @uses WP_List_Table
	 * @uses wp_parse_args()
	 * @uses add_filter()
	 * @uses add_action()
	 * @uses convert_to_screen()
	 * @param mixed $args
	 * @return void
	 */
	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'plural' => '',
			'singular' => '',
			'ajax' => true,
			'screen' => null,
		) );

		$this->screen = convert_to_screen( $args['screen'] );

		$post_type = eazyest_gallery()->post_type;
		add_filter( "manage_{$post_type}_columns", array( $this, 'get_columns' ), 0 );
		$this->_args = $args;

		if ( $args['ajax'] ) {
			// wp_enqueue_script( 'list-table' );
			add_action( 'admin_footer', array( $this, '_js_vars' ) );
		}
	}

	/**
	 * Eazyest_Folder_List_Table::no_items()
	 *
	 * @since 0.1.0 (r2)
	 * @return void
	 */
	function no_items() {
		_e( 'No folders found.', 'eazyest-gallery' );
	}

	/**
	 * Eazyest_Folder_List_Table::get_columns()
	 *
	 * @since 0.1.0 (r2)
	 * @return
	 */
	function get_columns() {
		return apply_filters( 'eazyest_gallery_subfolders_columns', eazyest_admin()->folder_editor()->folder_columns( array() ) );
	}

	/**
	 * Eazyest_Folder_List_Table::get_sortable_columns()
	 *
	 * @since 0.1.0 (r2)
	 * @return
	 */
	function get_sortable_columns() {
		return array(
			'folder_title'    => 'title',
			'folder_parent'   => 'parent',
			'folder_comments' => 'comment_count',
			'folder_date'     => array( 'date', true )
		);
	}

	/**
	 * Eazyest_Folder_List_Table::print_column_headers()
	 *
	 * @since 0.1.0 (r2)
	 * @uses set_url_scheme()
	 * @uses remove_query_arg()
	 * @uses add_query_arg()
	 * @uses esc_url()
	 * @param bool $with_id
	 * @return void
	 */
	function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_GET['folder_orderby'] ) )
			$current_orderby = $_GET['folder_orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All', 'eazyest-gallery' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( array( 'folder_orderby' => $column_key, 'order' => $order ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
				$class = str_replace( '-folder_', '-', $class );
			}

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}

	/**
	 * Eazyest_Folder_List_Table::display()
	 * Override display to prevent duplicate ids in folder-editor.
	 * @see WP_List_Table::display()
	 *
	 * @since 0.1.0 (r150)
	 * @return void
	 */
	function display() {
		extract( $this->_args );

		$this->display_tablenav( 'top' );

		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

			<tbody id="the-folder-list"<?php if ( $singular ) echo " data-wp-lists='list:$singular'"; ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Eazyest_Folder_List_Table::get_subfolders()
	 * Get all subfolders for use in Edit Folder screen.
	 *
	 * @since 0.2.0 (r323)
	 * @access private
	 * @uses WP_Query
	 * @return WP_Query object
	 */
	private function get_subfolders() {
		$option = explode( '-', eazyest_gallery()->sort_by( 'folders' ) );
		$sort_field = $option[0] == 'menu_order' ? 'menu_order' :  substr( $option[0], 5 );
		$sort_order = $option[1];
		global $post;
		$args = array(
			'post_type'   => eazyest_gallery()->post_type,
			'post_parent' => $post->ID,
			'post_status' => array( 'publish', 'inherit' ),
		  'orderby'     => $sort_field,
		  'order'       => $sort_order,
		);
		return new WP_Query( $args );
	}

	/**
	 * Eazyest_Folder_List_Table::prepare_items()
	 *
	 * @since 0.1.0 (r2)
	 * @uses is_post_type_hierarchical()
	 * @return void
	 */
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->hierarchical_display = is_post_type_hierarchical( $this->screen->post_type );

		$option = explode( '-', eazyest_gallery()->sort_by( 'folders' ) );
		$sort_field = $option[0];
		$sort_order = $option[1];
		global $wpdb, $post;
		if ( isset( $_REQUEST['folder_orderby'] ) ) {
			switch( $_REQUEST['folder_orderby'] ) {
			 	case 'folder_title' :
			 		$sort_field = "post_title";
			 		break;
		 		case 'folder_date' :
		 			$sort_field = 'post_date';
		 			break;
			}
		}
		if ( isset( $_REQUEST['folder_orderby'] ) && isset( $_REQUEST['order'] ) ) {
			$sort_order = strtoupper( $_REQUEST['order'] );
		}
		eazyest_gallery()->sort_folders = "{$sort_field}-{$sort_order}";
		$query = $this->get_subfolders();

		$this->items = $query->posts;
		$this->set_pagination_args( array(
			'total_items' => $query->post_count,
			'total_pages' => 1,
			'per_page'    => $query->post_count,
		) );
	}

	/**
	 * Eazyest_Folder_List_Table::pagination()
	 *
	 * @since 0.1.0 (r2)
	 * @uses number_format_i18n()
	 * @param string $which
	 * @return void
	 */
	function pagination( $which ) {
		extract( $this->_pagination_args, EXTR_SKIP );

		?>
		<div class="tablenav-pages one-page">
			<span class="displaying-num"><?php printf( _n( '1 folder', '%s folders', $total_items, 'eazyest-gallery' ), number_format_i18n( $total_items ) ) ?></span>
		</div>
		<?php
	}


	/**
	 * Eazyest_Folder_List_Table::display_tablenav()
	 * Display table navigation with  altered nonce field
	 *
	 * @since 0.1.0 (r2)
	 * @uses wp_nonce_field()
	 * @param string $which
	 * @return void
	 */
	function display_tablenav( $which ) {
		if ( 'top' == $which )
			wp_nonce_field( 'bulk-folders', 'bulk-folders' );
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( ! isset( $_GET['post'] ) ) : ?>
			<div class="alignleft actions">
				<?php $this->bulk_actions(); ?>
			</div>
			<?php endif; ?>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Eazyest_Folder_List_Table::extra_tablenav()
	 *
	 * @since 0.1.0 (r2)
	 * @uses number_format_i18n()
	 * @param mixed $which
	 * @return void
	 */
	function extra_tablenav( $which ) {
		$collected = eazyest_folderbase()->folders_collected();
		global $post;
		if ( ! in_array( $post->post_status, array('new', 'auto-draft', 'draft', 'trash' ) ) ) :
		?>
			<div class="tablenav-pages add-new">
				<a href="post-new.php?post_type=<?php echo eazyest_gallery()->post_type ?>&post_parent=<?php echo $post->ID ?>" class="add-subfolder"><?php _e( 'Add subfolder', 'eazyest-gallery' ) ?></a>
			</div>
		<?php
		endif;
		if ( $collected ) {
			$collected_message = 0 < $collected ?
				sprintf( _n( '1 new folder found', '%s new folders found', $collected, 'eazyest-gallery'), number_format_i18n( $collected )  ) :
					sprintf( _n( '1 missing folder', '%s missing folders', -$collected, 'eazyest-gallery'), number_format_i18n( -$collected )  )
			?>
			<div class="tablenav-pages collected">
				<span class="displaying-num"><?php echo $collected_message ?></span>
			</div>
			<?php
		}
	}

	/**
	 * Eazyest_Folder_List_Table::get_bulk_actions()
	 *
	 * @since 0.1.0 (r2)
	 * @return array()
	 */
	function get_bulk_actions() {
		$actions = array();
		$actions['trash'] = __( 'Move to Trash', 'eazyest-gallery' );

		return $actions;
	}

	/**
	 * Eazyest_Folder_List_Table::get_views()
	 * Override WP_List_Table::get_views()
	 * Add only field with menu order
	 *
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	function get_views() {
		$post_type = eazyest_gallery()->post_type;
		global $post;
		return array(
			'save-sort'  => eazyest_admin()->folder_editor()->hidden_order_field( $this->items, 'pages' )
		);
	}

	/**
	 * Eazyest_Folder_List_Table::views()
	 * ovveride WP_list_Table::views()
	 * apply filter 'views_galleryfolder' to add views
	 *
	 * @since 0.1.0 (r2)
	 * @uses apply_filters()
	 * @return void
	 */
	function views() {
		$views = $this->get_views();
		$views = apply_filters( 'views_galleryfolder', $views );
		$views['save-sort'] = eazyest_admin()->folder_editor()->hidden_order_field( $this->items );
		echo "<ul class='subsubsub'>\n";
		foreach ( $views as $class => $view ) {
			$views[$class] = "\t<li class='$class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo "</ul>";
	}

	/**
	 * Eazyest_Folder_List_Table::bulk_actions()
	 *
	 * @since 0.1.0 (r2)
	 * @uses submit_button
	 * @return void
	 */
	function bulk_actions($which = '') {
		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			// This filter can currently only be used to remove actions.
			$this->_actions = apply_filters( 'bulk_actions-' . $this->screen->id, $this->_actions );
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) )
			return;

		echo "<select name='folder_action$two'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions', 'eazyest-gallery' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

			echo "\t<option value='$name'$class>$title</option>\n";
		}

		echo "</select>\n";

		submit_button( __( 'Apply', 'eazyest-gallery' ), 'action', false, false, array( 'id' => "do_folderaction$two" ) );
		echo "\n";
	}

	/**
	 * Eazyest_Folder_List_Table::single_row()
	 *
	 * @since 0.1.0 (r2)
	 * @uses  get_post()
	 * @uses setup_postdata()
	 * @uses _draft_or_post_title()
	 * @uses get_post_type_object()
	 * @uses current_user_can()
	 * @uses get_current_user_id()
	 * @uses get_post_class()
	 * @uses apply_filters()
	 * @uses esc_attr()
	 * @uses sanitize_term_field()
	 * @uses add_query_arg()
	 * @uses the_ID()
	 * @uses wp_nonce_url()
	 * @uses admin_url()
	 * @uses get_delete_post_link()
	 * @uses get_inline_data()
	 * @uses do_action()
	 * @param WP_Post $post
	 * @param integer $level
	 * @return void
	 */
	function single_row( $post, $level = 0 ) {
		global $mode;
		static $alternate;

		$global_post = get_post();
		$GLOBALS['post'] = $post;
		setup_postdata( $post );

		$edit_link = get_edit_post_link( $post->ID );
		$title = _draft_or_post_title();
		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes = $alternate . ' iedit author-' . ( get_current_user_id() == $post->post_author ? 'self' : 'other' );
	?>
		<tr id="post-<?php echo $post->ID; ?>" class="<?php echo implode( ' ', get_post_class( $classes, $post->ID ) ); ?>" valign="top">
	<?php

		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class_name = $column_name;
			if ( 'galleryfolder_drag' != $column_name )
				$class_name = false === strpos( $column_name, 'folder_') ? $column_name : substr( $column_name, 7 );
			$class = "class=\"$class_name column-$class_name\"";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			switch ( $column_name ) {

			case 'cb':
			?>
			<th scope="row" class="check-column">
				<?php if ( $can_edit_post ) { ?>
				<label class="screen-reader-text" for="cb-select-<?php the_ID(); ?>"><?php printf( __( 'Select %s', 'eazyest-gallery' ), $title ); ?></label>
				<input id="cb-select-<?php the_ID(); ?>" type="checkbox" name="folders[]" value="<?php the_ID(); ?>" />
				<?php } ?>
			</th>
			<?php
			break;

			case 'folder_title':
				if ( $this->hierarchical_display ) {
					$attributes = 'class="post-title page-title column-title"' . $style;

					$level = eazyest_admin()->folder_editor()->folder_level( $post->ID, $post->post_parent );

					$pad = str_repeat( '&#8212; ', $level );
					?>
					<td <?php echo $attributes ?>><strong>
					<a class="row-title" href="<?php echo $edit_link; ?>" title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'eazyest-gallery' ), $title ) ); ?>"><?php echo $pad; echo $title ?></a></strong>
					<?php
				}

				$actions = array();
				if ( $can_edit_post && 'trash' != $post->post_status ) {
					$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '" title="' . esc_attr( __( 'Edit this item', 'eazyest-gallery' ) ) . '">' . __( 'Edit', 'eazyest-gallery' ) . '</a>';
				}
				if ( current_user_can( $post_type_object->cap->delete_post, $post->ID ) ) {
					if ( 'trash' == $post->post_status )
						$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash', 'eazyest-gallery' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore', 'eazyest-gallery' ) . "</a>";
					elseif ( EMPTY_TRASH_DAYS )
						$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash', 'eazyest-gallery' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', 'eazyest-gallery' ) . "</a>";
					if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
						$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently', 'eazyest-gallery' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', 'eazyest-gallery' ) . "</a>";
				}
				if ( $post_type_object->public ) {
					if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ) ) ) {
						if ( $can_edit_post )
							$actions['view'] = '<a href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;', 'eazyest-gallery' ), $title ) ) . '" rel="permalink">' . __( 'Preview', 'eazyest-gallery' ) . '</a>';
					} elseif ( 'trash' != $post->post_status ) {
						$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'eazyest-gallery' ), $title ) ) . '" rel="permalink">' . __( 'View', 'eazyest-gallery' ) . '</a>';
					}
				}

				$actions = apply_filters( is_post_type_hierarchical( $post->post_type ) ? 'page_row_actions' : 'post_row_actions', $actions, $post );
				echo $this->row_actions( $actions );

				get_inline_data( $post );
				echo '</td>';
			break;

			case 'folder_date':
				if ( '0000-00-00 00:00:00' == $post->post_date ) {
					$t_time = $h_time = __( 'Unpublished', 'eazyest-gallery' );
					$time_diff = 0;
				} else {
					$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'eazyest-gallery' ) );
					$m_time = $post->post_date;
					$time = get_post_time( 'G', true, $post );

					$time_diff = time() - $time;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
						$h_time = sprintf( __( '%s ago', 'eazyest-gallery' ), human_time_diff( $time ) );
					else
						$h_time = mysql2date( __( 'Y/m/d', 'eazyest-gallery' ), $m_time );
				}

				echo '<td ' . $attributes . '>';
				if ( 'excerpt' == $mode )
					echo apply_filters( 'post_date_column_time', $t_time, $post, $column_name, $mode );
				else
					echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, $mode ) . '</abbr>';
				echo '<br />';
				if ( 'publish' == $post->post_status ) {
					_e( 'Published', 'eazyest-gallery' );
				} elseif ( 'future' == $post->post_status ) {
					if ( $time_diff > 0 )
						echo '<strong class="attention">' . __( 'Missed schedule', 'eazyest-gallery' ) . '</strong>';
					else
						_e( 'Scheduled', 'eazyest-gallery' );
				} else {
					_e( 'Last Modified', 'eazyest-gallery' );
				}
				echo '</td>';
			break;

			case 'folder_comments':
			?>
			<td <?php echo $attributes ?>><div class="post-com-count-wrapper">
			<?php
				$pending_comments = isset( $this->comment_pending_count[$post->ID] ) ? $this->comment_pending_count[$post->ID] : 0;

				$this->comments_bubble( $post->ID, $pending_comments );
			?>
			</div></td>
			<?php
			break;

			default:
				if ( 'categories' == $column_name )
					$taxonomy = 'category';
				elseif ( 'tags' == $column_name )
					$taxonomy = 'post_tag';
				elseif ( 0 === strpos( $column_name, 'taxonomy-' ) )
					$taxonomy = substr( $column_name, 9 );
				else
					$taxonomy = false;

				if ( $taxonomy ) {
					$taxonomy_object = get_taxonomy( $taxonomy );
					echo '<td ' . $attributes . '>';
					if ( $terms = get_the_terms( $post->ID, $taxonomy ) ) {
						$out = array();
						foreach ( $terms as $t ) {
							$posts_in_term_qv = array();
							if ( 'post' != $post->post_type )
								$posts_in_term_qv['post_type'] = $post->post_type;
							if ( $taxonomy_object->query_var ) {
								$posts_in_term_qv[ $taxonomy_object->query_var ] = $t->slug;
							} else {
								$posts_in_term_qv['taxonomy'] = $taxonomy;
								$posts_in_term_qv['term'] = $t->slug;
							}

							$out[] = sprintf( '<a href="%s">%s</a>',
								esc_url( add_query_arg( $posts_in_term_qv, 'edit.php' ) ),
								esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) )
							);
						}
						/* translators: used between list items, there is a space after the comma */
						echo join( __( ', ', 'eazyest-gallery' ), $out );
					} else {
						echo '&#8212;';
					}
					echo '</td>';
					break;
				}
			?>
			<td <?php echo $attributes ?>><?php
				if ( is_post_type_hierarchical( $post->post_type ) )
					do_action( 'manage_pages_custom_column', $column_name, $post->ID );
				else
					do_action( 'manage_posts_custom_column', $column_name, $post->ID );
				do_action( "manage_{$post->post_type}_posts_custom_column", $column_name, $post->ID );
			?></td>
			<?php
			break;
			}
		}
	?>
		</tr>
	<?php
		$GLOBALS['post'] = $global_post;
	}

} // Eazyest_Folder_List_Table
