<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Eazyest_Extra_Fields
 * This class replaces the Eazyest Extra Fields plugin that was included in version 1.1.x
 * Gives the user the ability to add custom fields to Folders and Attachments
 *
 * @package Eazyest Gallery
 * @subpackage Extra Fields
 * @author Marcel Brinkkemper
 * @copyright 2013 Brimosoft
 * @since 0.1.0 (r2)
 * @version 0.1.0 (r12)
 * @access public
 */
class Eazyest_Extra_Fields {
	/**
	 * @staticvar Eazyest_Extra_Fields $instance single instance in memory
	 */
	private static $instance;

	/**
	 * @var array $fields holding extra fields for Eazyest Gallery
	 * @access private
	 */
	private $fields;

	/**
	 * Eazyest_Extra_Fields::__construct()
	 *
	 * @return void
	 */
	function __construct(){}

	/**
	 * Eazyest_Extra_Fields::init()
	 *
	 * @return void
	 */
	private function init() {
		$this->setup_variables();
		$this->actions();
		$this->filters();
	}

	/**
	 * Eazyest_Extra_Fields::instance()
	 *
	 * @static
	 * @return Eazyest_Extra_Fields object
	 */
	static public function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eazyest_Extra_Fields;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Eazyest_Extra_Fields::setup_variables()
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_option()
	 * @return void
	 */
	function setup_variables() {
		$this->fields = array();
		if ( $fields = get_option( 'eazyest-fields' ) ) {
			foreach ( $fields as $key => $field ) {
				$fields[$key]['edit']     = isset( $field['edit'] )     ? true : false;
				$fields[$key]['frontend'] = isset( $field['frontend'] ) ? true : false;
			}
			$this->fields = $fields;
		}
	}

	/**
	 * Eazyest_Extra_Fields::actions()
	 * Add WordPress actions
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_action()
	 * @return void
	 */
	function actions() {
		add_action( 'admin_init',                       array( $this, 'register_setting'   )     );

		if ( $this->enabled() ) {
			add_action( 'add_meta_boxes',                       array( $this, 'folder_meta_box'        ),  5 );
			add_action( 'add_meta_boxes',                       array( $this, 'attachment_meta_box'    )     );
			add_action( 'save_post',                            array( $this, 'save_folder_fields'     )     );
			add_action( 'edit_attachment',                      array( $this, 'save_attachment_fields' )     );
			add_action( 'eazyest_gallery_attachment_list_edit', array( $this, 'attachment_fields'      )     );
			add_action( 'eazyest_gallery_settings_section',     array( $this, 'extra_settings'         ), 20 );
		}
	}

	/**
	 * Eazyest_Extra_Fields::filters()
	 * Add WordPress filters
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_filter()
	 * @return void
	 */
	function filters() {
		add_filter( 'eazyest_gallery_advanced_settings', array( $this, 'section_fields' ) );
	}

	// Core functions
	/**
	 * Eazyest_Extra_Fields::enable()
	 * Enable if Extra Fields
	 *
	 * @since 0.1.0 (r2)
	 * @uses update_option()
	 * @return void
	 */
	function enable() {
		update_option(  'eazyest-enable-extra-fields', true );
	}

	/**
	 * Eazyest_Extra_Fields::enabled()
	 * Check if Extra Fields are enabled
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_option()
	 * @return bool
	 */
	function enabled() {
		return get_option( 'eazyest-enable-extra-fields' );
	}

	/**
	 * Eazyest_Extra_Fields::sanitize_field()
	 * Sanitize Values for new Extra Field
	 *
	 * @since 0.1.0 (r2)
	 * @uses sanitize_title()
	 * @param array $field
	 * @return array
	 */
	function sanitize_field( $field = array() ) {
		// Name [display] or Slug [name] should be set
		if ( empty( $field['name'] ) && empty( $field['display'] )  )
			return false;

		// set slug when empty
		if (! empty( $field['display'] ) && empty( $field['name'] ) )
			$field['name'] = sanitize_title( $field['display'] );
		// set name when empty
		if ( ! empty( $field['name'] ) && empty( $field['display'] ) )
			$field['display'] = ucfirst( str_replace( array( '-', '_' ), ' ', $field['name'] ) );
		// checkbox fields
		$field['edit']     = isset( $field['edit'] )     ? true : false;
		$field['frontend'] = isset( $field['frontend'] ) ? true : false;

		return $field;
	}

	/**
	 * Eazyest_Extra_Fields::add_field()
	 * Add an Extra Field
	 *
	 * @since 0.1.0 (r2)
	 * @uses update_option()
	 * @param array $field
	 * @return void
	 */
	function add_field( $field = array() ) {
		if ( $field = $this->sanitize_field( $field ) )
			$this->fields[] = $field;
		update_option( 'eazyest-fields', $this->fields );
	}

	/**
	 * Eazyest_Extra_Fields::update_post_field()
	 * Store field value as post metadata
	 *
	 * @since 0.1.0 (r2)
	 * @uses update_post_meta()
	 * @param integer $post_id
	 * @param string $field name
	 * @param mixed $value
	 * @param mixed $prev_value
	 * @return void
	 */
	function update_post_field( $post_id, $field, $value, $prev_value = null ) {
		update_post_meta( $post_id, "_eazyest-field_{$field}", $value, $prev_value );
	}

	/**
	 * Eazyest_Extra_Fields::get_post_field()
	 * Get field value from post metadata
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post_meta()
	 * @param integer $post_id
	 * @param string $field name
	 * @return mixed field value
	 */
	function get_post_field( $post_id, $field ) {
		return get_post_meta( $post_id, "_eazyest-field_{$field}", true );
	}

	// Edit Folder screen --------------------------------------------------------

	/**
	 * Eazyest_Extra_Fields::folder_meta_box()
	 * Add a metabox to edit Extra field values
	 *
	 * @since 0.1.0 (r2)
	 * @uses add_meta_box()
	 * @return void
	 */
	function folder_meta_box() {
		if ( count( $this->fields ) && $this->enabled() ) {
			foreach( $this->fields as $field ) {
				if ( 'folder' == $field['target']  && $field['edit'] ) {
					add_meta_box(
						'eazyest_gallery_extra_fields_meta_box',
						__( 'Custom Folder Fields', 'eazyest-gallery' ),
						array( $this, 'folder_meta_box_content' ),
						eazyest_gallery()->post_type,
						'normal',
						'high'
					);
					break;
				}
			}
		}
	}

	/**
	 * Eazyest_Extra_Fields::folder_meta_box_content()
	 * Content for Extra Fields metabox
	 * Plugins can output content for a particular field by using action:
	 * <code>eazyest-gallery-folder-field_fieldname</code>
	 *
	 * @param galleryfolder post type $folder
	 * @since 0.1.0 (r2)
	 * @uses has_action()
	 * @uses do_action()
	 * @uses esc_html()
	 * @return void
	 */
	function folder_meta_box_content( $folder ){
		if ( count( $this->fields ) && $this->enabled() ) {
			foreach( $this->fields as $field ) {
				if ( 'folder' == $field['target']  && $field['edit'] ) {
					$identifier = "eazyest-gallery-folder-field_{$field['name']}";
					// allow custom input fields through action
					if ( has_action( $identifier ) ) {
						do_action( $identifier, $folder->ID, $field );
						continue;
					}
					// if no action, display a text input field
					$value    = $this->get_post_field( $folder->ID, $field['name'], true );
					$field_id = "folder-field_{$field['name']}";
					?>
					<p>
						<label for="<?php echo $field_id ?>"><?php echo esc_html( $field['display'] ); ?></label>
						<input id="<?php echo $field_id ?>" name="<?php echo "folder-field[{$field['name']}]" ?>" type="text" value="<?php echo esc_textarea( $value ) ?>" size="60" />
					</p>
					<?php
				}
			}
		}
	}

	/**
	 * Eazyest_Extra_Fields::save_folder_fields()
	 * Save Extra Fields as post metadata
	 *
	 * @since 0.1.0 (r2)
	 * @uses sanitize_text_field()
	 * @param integer $post_id
	 * @return void
	 */
	function save_folder_fields( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) || defined( 'LAZYEST_GALLERY_UPGRADING' ) )
			return;
		// don't  run if not initiated from edit post
		if ( ! isset( $_POST['action'] ) )
			return;
		if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == eazyest_gallery()->post_type ) {
			if ( isset( $_POST['folder-field'] ) ) {
				foreach( $_POST['folder-field'] as $name => $value ) {
					$this->update_post_field( $post_id, $name, sanitize_text_field( $value ) );
				}
			}
		}
	}

	// Attachment functions ------------------------------------------------------

	/**
	 * Eazyest_Extra_Fields::attachment_fields()
	 * Show extra fields in the Folder editor screen's Attachment List Table
	 * and in Attachment metabox
	 *
	 * Plugins can output content for a particular field by using action:
	 * <code>eazyest-gallery-attachment-field_fieldname</code>
	 *
	 * @since 0.1.0 (r2)
	 * @uses has_action()
	 * @uses do_action()
	 * @uses esc_html()
	 * @param integer $attachment_id
	 * @return void
	 */
	function attachment_fields( $attachment ) {
		if ( count( $this->fields ) && $this->enabled() ) {
			foreach( $this->fields as $field ) {
				if ( 'image' == $field['target']  && $field['edit'] ) {
					$identifier = "eazyest-gallery-attachment-field_{$field['name']}";
					// allow custom input fields through action
					if ( has_action( $identifier ) ) {
						do_action( $identifier, $attachment->ID, $field );
						continue;
					}
					// if no action, display a text input field
					$value    = $this->get_post_field( $attachment->ID, $field['name'], true );
					$field_id = "attachment-field_{$field['name']}";
					?>
					<p>
						<label for="<?php echo $field_id ?>"><?php echo esc_html( $field['display'] ); ?></label>
						<input id="<?php echo $field_id ?>" name="<?php echo "attachment-field[{$field['name']}]" ?>" type="text" value="<?php echo esc_textarea( $value ) ?>" size="60" />
					</p>
					<?php
				}
			}
		}
	}

	/**
	 * Eazyest_Extra_Fields::attachment_meta_box()
	 * Add a metabox with extra felds for attachment in Eazyest Gallery
	 *
	 * @since 0.1.0 (r2)
	 * @uses get_post()
	 * @uses get_post_type()
	 * @uses add_meta_box()
	 * @param string $post_type
	 * @return void
	 */
	function attachment_meta_box( $post_type ) {
		if ( 'attachment' == $post_type && count( $this->fields ) && $this->enabled()  ) {
			$post_id = isset( $_REQUEST['post'] ) ? intval( $_REQUEST['post'] ) : 0;
			if ( eazyest_folderbase()->is_gallery_image( $post_id ) ) {
					foreach( $this->fields as $field ) {
					if ( 'image' == $field['target']  && $field['edit'] ) {
						add_meta_box(
							'eazyest_gallery_extra_fields_meta_box',
							__( 'Custom Attachment Fields', 'eazyest-gallery' ),
							array( $this, 'attachment_fields' ),
							'attachment',
							'normal',
							'high'
						);
						break;
					}
				}
			}
		}
	}

	/**
	 * Eazyest_Extra_Fields::save_attachment_fields()
	 * Save Extra Fields as post metadata
	 *
	 * @since 0.1.0 (r2)
	 * @uses sanitize_text_field()
	 * @param integer $post_id
	 * @return void
	 */
	function save_attachment_fields( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) || defined( 'LAZYEST_GALLERY_UPGRADING' ) )
			return;
		// don't  run if not initiated from edit post
		if ( ! isset( $_POST['action'] ) )
			return;
		if ( $_POST['post_type'] == 'attachment' ) {
			if ( isset( $_POST['attachment-field'] ) ) {
				foreach( $_POST['attachment-field'] as $name => $value ) {
					$this->update_post_field( $post_id, $name, sanitize_text_field( $value ) );
				}
			}
		}
	}

	// Settings functions --------------------------------------------------------

	/**
	 * Eazyest_Extra_Fields::register_setting()
	 *
	 * @since 0.1.0 (r2)
	 * @uses register_setting()
	 * @uses unregister_setting()
	 * @return void
	 */
	function register_setting() {
		register_setting( 'eazyest-gallery', 'eazyest-enable-extra-fields', array( $this, 'sanitize_enable' ) );
		if ( $this->enabled() )
			register_setting( 'eazyest-gallery', 'eazyest-fields', array( $this, 'sanitize_settings' ) );
		else
			unregister_setting( 'eazyest-gallery', 'eazyest-fields', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Eazyest_Extra_Fields::sanitize_enable()
	 * Sanitize the enable option
	 *
	 * @since 0.1.0 (r2)
	 * @param array $options
	 * @return array
	 */
	function sanitize_enable( $options ) {
		$options = empty( $options ) ? false : true;
		return $options;
	}

	/**
	 * Eazyest_Extra_Fields::sanitize_settings()
	 * Sanitize Extra Fields added in Eazyest Gallery Settings
	 *
	 * @since 0.1.0 (r2)
	 * @param array $options
	 * @return array
	 */
	function sanitize_settings( $options ) {
		if ( ! count( $options ) )
			return $options;

		foreach( $options as $key => $field ) {
			if ( $field = $this->sanitize_field( $field ) )
				$options[$key] = $field;
			else
				unset( $options[$key] );
		}
		$options = array_merge( array(), $options );
		return $options;
	}

	// Settings screen -----------------------------------------------------------

	/**
	 * Eazyest_Extra_Fields::section_fields()
	 * Enable Extra Fields option in Eazyest Gallery settings
	 *
	 * @since 0.1.0 (r2)
	 * @param mixed $fields
	 * @return array
	 */
	function section_fields( $fields ) {
		$fields['enable_extra_fields'] = array(
			'title'    => __( 'Extra Fields', 'eazyest-gallery'  ),
			'callback' => array( $this, 'enable_extra_fields' )
		);
		return $fields;
	}

	/**
	 * Eazyest_Extra_Fields::enable_extra_fields()
	 *
	 * @since 0.1.0 (r2)
	 * @uses _e()
	 * @return void
	 */
	function enable_extra_fields() {
		?>
		<input type="checkbox" name="eazyest-enable-extra-fields" id="eazyest-enable-extra-fields" <?php checked( $this->enabled() ) ?> />
		<label for="eazyest-enable-extra-fields"><?php _e( 'Enable extra fields', 'eazyest-gallery' ); ?></label>
		<?php
	}

	/**
	 * Eazyest_Extra_Fields::extra_settings()
	 * Show extra Fields section on Eazyest Gallery settings screen.
	 *
	 * @since 0.1.0 (r2)
	 * @uses _e()
	 * @uses checked()
	 * @uses @selected()
	 * @uses wp_enqueue_script()
	 * @return void
	 */
	function extra_settings() {

		if( !  $this->enabled() )
			return;

		$fields   = $this->fields;
		$fields[count($fields)] = array( 'name' => '', 'display' => '', 'target' => 'folder', 'frontend' => 1, 'edit' => 0 );
		$head_remove = eazyest_gallery()->plugin_url . "includes/images/head-remove.png";
		$list_remove = eazyest_gallery()->plugin_url . "includes/images/list-remove.png";
		$j = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'js' : 'min.js';
		wp_enqueue_script( 'eazyest-fields', eazyest_gallery()->plugin_url . "includes/js/eazyest-extra-fields-settings.$j", array( 'jquery' ), true );
		?>
		<style media="screen">
		  td.check-column, th.check-column{ width:100px; }
			td.delete-cross, th.delete-cross{ width:18px }
			a.delete-cross {
				background: transparent url("<?php echo $list_remove ?>") no-repeat;
				display: block;
				height:16px;
				margin:auto;
				width:16px;
			}
			#eazyest-gallery-extra-fields code {
				border: 1px solid #dfdfdf;
				border-radius: 3px;
				display:block;
				font-size:12px;
				height: 21px;
			}
		</style>
		<h3><?php _e( 'Custom Fields', 'eazyest-gallery' ); ?></h3>
		<p><?php _e( 'Add extra fields to be stored with your images and folders', 'eazyest-gallery' ); ?></p>
		<table class="form-table" id="eazyest-gallery-extra-fields">
			<thead>
				<th scope="col" class="delete-cross"><img src="<?php echo $head_remove; ?>" alt="<?php _e( 'delete', 'eazyest-gallery' ); ?>" /></th>
				<th scope="col"><?php _e( 'Name',         'eazyest-gallery' ); ?></th>
				<th scope="col"><?php _e( 'Slug',         'eazyest-gallery' ); ?></th>
				<th scope="col"><?php _e( 'Applies to',   'eazyest-gallery' ); ?></th>
				<th scope="col" class="check-column"><?php _e( 'Display',      'eazyest-gallery' ); ?></th>
				<th scope="col" class="check-column"><?php _e( 'Editable',     'eazyest-gallery' ); ?></th>
			</thead>
			<tbody>
				<?php  foreach( $fields as $key => $field ) : ?>
				<tr>
					<td>
						<?php if( '' != $field['name'] ) : ?>
						<a class="delete-cross" href="#delete-<?php echo $key ?>" id="delete-<?php echo $key ?>" title="<?php _e( 'Delete this field', 'eazyest-gallery' ); ?>"> </a>
						<?php endif; ?>
					</td>
					<td>
						<input type="text" name="eazyest-fields[<?php echo $key; ?>][display]" value="<?php echo esc_html( $field['display'] );  ?>" size="32" />
					</td>
					<td>
						<code><?php echo $field['name']; ?></code>
						<input type="hidden" name="eazyest-fields[<?php echo $key; ?>][name]" value="<?php echo $field['name'] ?>"/>
					</td>
					<td>
						<select name="eazyest-fields[<?php echo $key; ?>][target]">
							<option value="folder" <?php selected( $field['target'] == 'folder' ) ?>><?php _e( 'Folder', 'eazyest-gallery'); ?></option>
							<option value="image" <?php selected( $field['target'] == 'image' )?>><?php _e( 'Image', 'eazyest-gallery' ); ?></option>
						</select>
					</td>
					<td class="check-column">
						<input type="checkbox" name="eazyest-fields[<?php echo $key; ?>][frontend]" value="1" <?php checked( $field['frontend'] ) ?> />
					</td>
					<td class="check-column">
						<input type="checkbox" name="eazyest-fields[<?php echo $key; ?>][edit]" value="1" <?php checked( $field['edit'] ) ?> />
					</td>
					<td>
						<?php do_action( 'eazyest_gallery_settings_extra_field_column' ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

} // Eazyest_Extra_Fields

/**
 * eazyest_extra_fields()
 *
 * @since 0.1.0 (r2)
 * @return object Eazyest_Extra_Fields
 */
function eazyest_extra_fields() {
	return Eazyest_Extra_Fields::instance();
}
