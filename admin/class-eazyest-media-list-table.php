<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;  

	
if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * Eazyest_Media_List_Table
 * List table to show attached images in the folder edit screen
 *
 * @package Eazyest Gallery
 * @subpackage List Table
 * @author Marcel Brinkkemper
 * @copyright 2012 Brimosoft
 * @version 0.2.0 (r323) 
 * @since 0.1.0 (r2)
 * @uses WP_List_Table
 * @access public
 * @see WordPress WP_List_Table
 * @link http://codex.wordpress.org/Class_Reference/WP_List_Table
 */
class Eazyest_Media_List_Table extends WP_List_Table {
	
	/**
	 * Eazyest_Media_List_Table::__construct()
	 * 
	 * @param array $args
	 * @return void
	 */
	function __construct( $args = array() ) {
		parent::__construct( $args ); 
		
		// pretend we are on the media screen
		$this->args['plural'] = 'media';
		$this->args['screen'] = isset( $args['screen'] ) ? $args['screen'] : null;
	}
	
	/**
	 * Eazyest_Media_List_Table::pagination()
	 * Add a view switcher to the table
	 * 
	 * @since 0.1.0 (r2)
	 * @param string $which
	 * @return void
	 */
	function pagination( $which ) {
		global $mode;
		
		extract( $this->_pagination_args, EXTR_SKIP );
		
		?>
		<div class="tablenav-pages one-page">			
			<span class="displaying-num"><?php printf( _n( '1 image', '%s images', $total_items, 'eazyest-gallery' ), number_format_i18n( $total_items ) ) ?></span>
		</div>
		<?php 
		if ( 'top' == $which ) $this->view_switcher( $mode );
	}
	
	function no_items() {
		_e( 'No images found.', 'eazyest-gallery' );
	}
	
	/**
	 * Eazyest_Media_List_Table::get_columns()
	 * 
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	function get_columns() {		
		$drag_url = eazyest_gallery()->plugin_url . 'admin/images/sort.png';
		$columns = array();
		$columns['cb']           = '<input type="checkbox" />';
		if (  'menu_order-ASC' == eazyest_gallery()->sort_by( 'thumbnails' ) ) 
			$columns['media_drag'] = '<img src="' . $drag_url . '" alt="' . __( 'Draggable Column', 'eazyest-gallery' ) .  '" style="width:16px; height=16px"/>';
		$columns['icon']         = '';
		$columns['file']         = _x( 'File', 'column name', 'eazyest-gallery' );
		$columns['description']  = _x( 'Content', 'column name', 'eazyest-gallery' );
		$columns['comments']     = '<span><span class="vers"><div title="' . esc_attr__( 'Comments', 'eazyest-gallery' ) . '" class="comment-grey-bubble"></div></span></span>';
		$columns['date']         = _x( 'Date', 'column name', 'eazyest-gallery' );
		return apply_filters( 'eazyest_gallery_images_columns', $columns );
	}
	
	/**
	 * Eazyest_Media_List_Table::display()
	 * Override display to prevent duplicate ids.
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
		
			<tbody id="the-media-list"<?php if ( $singular ) echo " data-wp-lists='list:$singular'"; ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}
	
	/**
	 * Eazyest_Media_List_Table::get_attached_images()
	 * Get all attached images for use in Edit Folder screen.
	 * 
	 * @since 0.2.0 (r232)
	 * @acces private
	 * @uses add_filter() to add filter for 'posts_orderby'
	 * @uses WP_Query
	 * @uses remove_filter()
	 * @return WP_Query object
	 */
	private function get_attached_images() {
		
		$option = explode( '-', eazyest_gallery()->sort_by( 'thumbnails' ) );		
		$sort_field = $option[0];
		$sort_order = $option[1];
				
		global $post;
		
		$args = array(
			 'post_type'      => 'attachment',
			 'post_parent'    => $post->ID,
			 'post_status'    => array( 'inherit', 'publish' ),
			 'post_mime_type' => 'image',
			 'orderby'        => $sort_field,
			 'order'          => $sort_order,
			 'posts_per_page' => -1, 
		);
		
		add_filter( 'posts_orderby', array( eazyest_folderbase(), 'thumbnails_orderby' ) );
		$query = new WP_Query( $args );		
		remove_filter( 'posts_orderby', array( eazyest_folderbase(), 'thumbnails_orderby' ) );
		
		return $query;
	}
	
	/**
	 * Eazyest_Media_List_Table::prepare_items()
	 * 
	 * @since 0.1.0 (r2)
	 * @uses wpdb::get_results()
	 * @return void
	 */
	function prepare_items() {
		global $mode;
		
 		$this->is_trash = isset( $_REQUEST['status'] ) && 'trash' == $_REQUEST['status'];
		  			
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		$mode = empty( $_REQUEST['mode'] ) ? 'list' : $_REQUEST['mode'];
		
		$option = explode( '-', eazyest_gallery()->sort_by( 'thumbnails' ) );
		$sort_field = $option[0];
		$sort_order = $option[1];
		
		if ( isset( $_REQUEST['orderby'] ) ) {
			switch( $_REQUEST['orderby'] ) {
			 	case 'description' :
			 		$sort_field = "post_excerpt";
			 		break;
		 		case 'date' :
		 			$sort_field = 'post_date';
		 			break;
			}
		}	
		if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			$sort_order = strtoupper( $_REQUEST['order'] );
		}
		eazyest_gallery()->sort_thumbnails = "{$sort_field}-{$sort_order}";
		
		$query = $this->get_attached_images();						
		$this->items = $query->posts;
		$total_items = count( $this->items );
		$this->set_pagination_args( array(
			'total_items' => $query->post_count,
			'total_pages' => 1,
			'per_page' => $query->post_count,
		) );
	}
	
	
	/**
	 * Eazyest_Media_List_Table::get_sortable_columns()
	 * 
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	function get_sortable_columns() {
		$option = explode( '-', eazyest_gallery()->sort_thumbnails );
		
		$sort_field = $option[0];
		$sort_order = $option[1];
		$file_asc = $date_asc = $content_asc = false;
		
		$option = explode( '-', eazyest_gallery()->sort_thumbnails );
		
		$sort_field = $option[0];
		$sort_order = $option[1] == 'ASC';
		
		switch( $sort_field ) {
			case 'name' :
				$file_asc = $sort_order;
				break;
			case 'description' :
				$content_asc = $sort_order;
				break;
			case 'date' :
				$date_asc = $sort_order;
			break;		 	
		}
		
		return array(
			'file'         => array( 'file',    $file_asc    ),
			'date'         => array( 'date',    $date_asc    ),
			'description'  => array( 'description', $content_asc ),
			'comments'     => array( 'comments', 'DESC' ),
		);
	}
		
	function get_views() {
		return array(
			'save-sort' => eazyest_admin()->folder_editor()->hidden_order_field( $this->items, 'media' )
		);		
	}
	
	/**
	 * Eazyest_Media_List_Table::bulk_actions()
	 * override WP_List_Table::bulk_actions to prevent duplicate 'action' fields
	 * 
	 * @since 0.1.0 (r2)
	 * @uses apply_filters
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

		echo "<select name='attachment_action$two'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions', 'eazyest-gallery' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

			echo "\t<option value='$name'$class>$title</option>\n";
		}

		echo "</select>\n";
		if ( ! empty ( $this->items ) ) {
			echo "";
		}
		submit_button( __( 'Apply', 'eazyest-gallery' ), 'action', false, false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}	
	
	/**
	 * Eazyest_Media_List_Table::current_action()
	 * override WP_List_Table::current_action() to handle 'attachment_action' field
	 * 
	 * @return mixed string action bool false if none set
	 */
	function current_action() {
		if ( isset( $_REQUEST['attachment_action'] ) && -1 != $_REQUEST['attachment_action'] )
			return $_REQUEST['attachment_action'];

		if ( isset( $_REQUEST['attachment_action2'] ) && -1 != $_REQUEST['attachment_action2'] )
			return $_REQUEST['attachment_action2'];

		return false;
	}
	
	function extra_tablenav( $which ) {
		$collected = eazyest_folderbase()->images_collected();
		if ( $collected ) {
			$collected_message = 0 < $collected ? 
				sprintf( _n( '1 new image found', '%s new images found', $collected, 'eazyest-gallery'), number_format_i18n( $collected )  ) :
					sprintf( _n( '1 missing image', '%s missing images', -$collected, 'eazyest-gallery'), number_format_i18n( -$collected )  )
			?>
			<div class="tablenav-pages collected">
				<span class="displaying-num"><?php echo $collected_message ?></span>
			</div>
			<?php  
		} 
	}
	
	/**
	 * Eazyest_Media_List_Table::get_bulk_actions()
	 * Define bulk actions for attached images
	 * 
	 * @since 0.1.0 (r2)
	 * @return array
	 */
	function get_bulk_actions() {
	  $actions = array(
	    'delete'    => __( 'Delete Permanently', 'eazyest-gallery' )
	  );
	  return $actions;
	} 
	
	/**
	 * Eazyest_Media_List_Table::_get_row_actions()
	 * Add actions for an attachment item ( Edit, Delete Permanently, View )
	 * 
	 * @param stdClass $item
	 * @param string $att_title
	 * @since 0.1.0 (r2)
	 * @uses add_query_arg()
	 * @uses wp_nonce_url()
	 * @uses current_user_can()
	 * @uses apply_filters()
	 * @return array
	 */
	protected function _get_row_actions( $item, $att_title ){
		
		$actions = array();	
		$item_url   = add_query_arg( array( 'post' => $item->ID ), admin_url( 'post.php' ) );
		$edit_url   = get_edit_post_link( $item->ID, true );
		$delete_url = add_query_arg( array( 'action' => 'attachment_action', 'attachment_action' =>'delete', 'media' => $item->ID, 'bulk-media' => wp_create_nonce( 'bulk-media' ) ), $item_url );
		$view_url   = get_permalink( $item->ID );
		if ( current_user_can( 'edit_post', $item->ID ) )
			$actions['edit'] = "<a href='$edit_url'>" . __( 'Edit', 'eazyest-gallery' ) . "</a>";
		if ( current_user_can( 'delete_post', $item->ID ) )
			$actions['delete'] = "<a class='submitdelete' href='$delete_url' onclick='return showNotice.warn();'>" . __( 'Delete Permanently', 'eazyest-gallery' ) . "</a>";
		$actions['view'] = "<a href='$view_url' title='" . esc_attr( sprintf( __( 'View &#8220;%s&#8221;', 'eazyest-gallery' ), $att_title ) ) . "' rel='permalink'>" . __( 'View', 'eazyest-gallery' ) . "</a>";
		
		$actions = apply_filters( 'eazyest_gallery_media_row_actions', $actions, $item );
		return $actions;
	}
	
	

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 0.1.0 (r2)
	 * @access protected
	 */
	function display_tablenav( $which ) {
		if ( 'top' == $which )
			wp_nonce_field( 'bulk-' . $this->_args['plural'], 'bulk-' . $this->_args['plural'] );
	?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<div class="alignleft actions">
			<?php $this->bulk_actions(); ?>
		</div>
		<?php
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>

		<br class="clear" />
	</div>
	<?php
	}
	
	/**
	 * Eazyest_Media_List_Table::display_rows()
	 * 
	 * @since 0.1.0 (r2)
	 * @uses add_filter()
	 * @uses current_user_can()
	 * @uses _draft_or_post_title()
	 * @uses wp_get_attachment_image()
	 * @uses get_edit_post_link(
	 * @uses get_post_meta()
	 * @uses _media_states()
	 * @uses get_post_mime_type()
	 * @uses get_attached_file()
	 * @uses get_pending_comments_num()
	 * @uses get_post_time()
	 * @uses human_time_diff()
	 * @uses esc_attr()
	 * @return void
	 */
	function display_rows() {
		
		$alt = '';
		$tabindex = 98;
		
		if ( ! empty ( $this->items ) ) {
			foreach( $this->items as $item ) {
				$tabindex = $tabindex + 2;
				$user_can_edit = current_user_can( 'edit_post', $item->ID );
				
				if ( $this->is_trash && $item->post_status != 'trash' ||  !$this->is_trash && $item->post_status == 'trash' )
					continue;

				$item_owner = ( get_current_user_id() == $item->post_author ) ? 'self' : 'other';	
				$alt = ( 'alternate' == $alt ) ? '' : 'alternate';
				$att_title = esc_html( $item->post_excerpt );
				if ( empty( $att_title ) )
					$att_title = esc_html( $item->post_title );
				?>
				<tr id='post-<?php echo $item->ID; ?>' class='<?php echo trim( $alt . ' author-' . $item_owner . ' status-' . $item->post_status ); ?>' valign="top">
				<?php
				list( $columns, $hidden ) = $this->get_column_info();
								
				foreach ( $columns as $column_name => $column_display_name ) {
					$class = "class='$column_name column-$column_name'";
				
					$style = '';
					if ( in_array( $column_name, $hidden ) )
						$style = ' style="display:none;"';
				
					$attributes = $class . $style;
					
					switch( $column_name ) {

					case 'cb':
					?>
						<th scope="row" class="check-column">
							<?php if ( $user_can_edit ) { ?>
								<label class="screen-reader-text" for="cb-select-<?php echo $item->ID; ?>"><?php echo sprintf( __( 'Select %s', 'eazyest-gallery' ), $att_title );?></label>
								<input type="checkbox" name="media[<?php echo $item->ID; ?>]" id="cb-select-<?php echo $item->ID; ?>" value="<?php echo $item->ID; ?>" />
							<?php } ?>
						</th>
						<?php
						break;

					case 'icon':
						$attributes = 'class="column-icon media-icon"' . $style;
						?>
						<td <?php echo $attributes ?>><?php
							if ( $thumb = wp_get_attachment_image( $item->ID, array( 80, 60 ), true ) ) {
								if ( $this->is_trash || ! $user_can_edit ) {
									echo $thumb;
								} else {
						?>
								<a href="<?php echo get_edit_post_link( $item->ID, true ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'eazyest-gallery' ), $att_title ) ); ?>">
									<?php echo $thumb; ?>
								</a>
				
						<?php			
								}
							}
						?>
						</td>
						<?php
						break;
						
						case 'file' :
							$filename = basename( get_post_meta( $item->ID, '_wp_attached_file', true ) );
							?>
								<td <?php echo $attributes ?>><strong>
								<?php if ( $this->is_trash || ! $user_can_edit ) {
									echo $filename;
								} else { ?>
								<a href="<?php echo get_edit_post_link( $item->ID, true ); ?>"
									title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'eazyest-gallery' ), $filename ) ); ?>">
									<?php echo $filename; ?></a>
								<?php };
								_media_states( $item ); ?></strong>
								<p>
									<?php
									if ( preg_match( '/^.*?\.(\w+)$/', get_attached_file( $item->ID ), $matches ) )
										echo esc_html( strtoupper( $matches[1] ) );
									else
										echo strtoupper( str_replace( 'image/', '', get_post_mime_type() ) );
									?>
								</p>
								<?php
								echo $this->row_actions( $this->_get_row_actions( $item, $att_title ) );
								?>
							</td>
							<?php
							break;

						case 'description':
							?>
							<td <?php echo $attributes ?>>
								<?php if ( $this->is_trash || ! $user_can_edit ) {
									echo $att_title;
									if  ( 'excerpt' == $mode ) {
										?>
										<div class="attachment-content excerpt">
										<?php
										$excerpt = strip_tags( $item->post_content );
										$excerpt = 50 < strlen( $excerpt ) ? substr( $excerpt, 0, 50 ) . ' [...]' : $excerpt;
										echo $excerpt;
										?>
										</div>
										<?php
									}
								} else {
									$caption = $item->post_excerpt;
									if ( empty( $caption ) )
										$caption = $item->post_title;
									$caption = stripslashes( $caption );	
									?>
									<input type="text" class="attachment-excerpt" name="attachment[<?php echo $item->ID ?>][post_excerpt]" size="30" value="<?php echo esc_textarea( $caption ); ?>" id="title-<?php echo $item->ID ?>" autocomplete="off" tabindex="<?php echo $tabindex ?>" />								
									<?php
									global $mode;
									if ( 'excerpt' == $mode ) {
										?>
										<div class="attachment-content content">
										<?php
										wp_editor( $item->post_content, "editor{$item->ID}", array(
												'media_buttons' => false,
												'tabindex'			=> $tabindex + 1,
												'textarea_name' => "attachment[{$item->ID}][post_content]",
												'textarea_rows' => 6,
												'teeny'         => false,
												'tinymce'       => false,
												'quicktags'     => false
											) 
										);
										?>
										</div>
										<?php
										do_action( 'eazyest_gallery_attachment_list_edit', $item );
									} 
								} 
								?>			
							</td>
							<?php
							break;					

						case 'comments':
							$attributes = 'class="comments column-comments num"' . $style;
							?>
							<td <?php echo $attributes ?>>
								<div class="post-com-count-wrapper">
									<?php
									$pending_comments = get_pending_comments_num( $item->ID );
							
									$this->comments_bubble( $item->ID, $pending_comments );
									?>
								</div>
							</td>
							<?php
							break;
							
							case 'date':
								if ( '0000-00-00 00:00:00' == $item->post_date ) {
									$h_time = __( 'Unpublished', 'eazyest-gallery' );
								} else {
									$m_time = $item->post_date;
									$time = get_post_time( 'G', true, $item, false );
									if ( ( abs( $t_diff = time() - $time ) ) < DAY_IN_SECONDS ) {
										if ( $t_diff < 0 )
											$h_time = sprintf( __( '%s from now', 'eazyest-gallery' ), human_time_diff( $time ) );
										else
											$h_time = sprintf( __( '%s ago', 'eazyest-gallery' ), human_time_diff( $time ) );
									} else {
										$h_time = mysql2date( __( 'Y/m/d', 'eazyest-gallery' ), $m_time );
									}
								}
								?>
								<td <?php echo $attributes ?>><?php echo $h_time ?></td>
								<?php
								break;
								
						case 'media_drag':
						?><td <?php echo $attributes ?>> 						
							<?php	echo eazyest_admin()->folder_editor()->drag_handle( $item->ID ); ?></td>
							<?php
							break;
											
					}
				}
			}
		}
	}
	
} // Eazyest_Media_List_Table