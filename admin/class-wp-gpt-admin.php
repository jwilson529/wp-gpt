<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Wp_Gpt
 * @subpackage Wp_Gpt/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Gpt
 * @subpackage Wp_Gpt/admin
 * @author     James Wilson <info@oneclickcontent.com>
 */
class Wp_Gpt_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'init', array( $this, 'register_conversation_cpt' ) );
		add_action( 'admin_menu', array( $this, 'create_settings_page' ) );
		add_action( 'admin_init', array( $this, 'setup_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-gpt-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-gpt-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register the custom post type for conversations.
	 *
	 * @since 1.0.0
	 */
	public function register_conversation_cpt() {
		$labels = array(
			'name'               => _x( 'Conversations', 'post type general name' ),
			'singular_name'      => _x( 'Conversation', 'post type singular name' ),
			'menu_name'          => _x( 'Conversations', 'admin menu' ),
			'name_admin_bar'     => _x( 'Conversation', 'add new on admin bar' ),
			'add_new'            => _x( 'Add New', 'conversation' ),
			'add_new_item'       => __( 'Add New Conversation' ),
			'new_item'           => __( 'New Conversation' ),
			'edit_item'          => __( 'Edit Conversation' ),
			'view_item'          => __( 'View Conversation' ),
			'all_items'          => __( 'All Conversations' ),
			'search_items'       => __( 'Search Conversations' ),
			'parent_item_colon'  => __( 'Parent Conversations:' ),
			'not_found'          => __( 'No conversations found.' ),
			'not_found_in_trash' => __( 'No conversations found in Trash.' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'conversation' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor' ),
		);

		register_post_type( 'conversation', $args );
	}

	/**
	 * Add settings page to the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function create_settings_page() {
		add_options_page(
			'WP-GPT Settings',
			'WP-GPT Settings',
			'manage_options',
			'chatgpt-settings',
			array( $this, 'settings_page_content' )
		);
	}

	/**
	 * Display the settings page content.
	 *
	 * @since 1.0.0
	 */
	public function settings_page_content() {
		?>
		<div class="wrap">
			<h1>WP-GPT Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'chatgpt_settings' );
				do_settings_sections( 'chatgpt-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Setup plugin settings and fields.
	 *
	 * @since 1.0.0
	 */
	public function setup_settings() {
		register_setting( 'chatgpt_settings', 'chatgpt_api_key' );
		register_setting( 'chatgpt_settings', 'chatgpt_model' );
		register_setting( 'chatgpt_settings', 'chatgpt_temperature' );
		register_setting( 'chatgpt_settings', 'chatgpt_max_tokens' );

		add_settings_section( 'chatgpt_api_section', 'API Settings', null, 'chatgpt-settings' );

		add_settings_field( 'chatgpt_api_key', 'OpenAI API Key', array( $this, 'api_key_field_html' ), 'chatgpt-settings', 'chatgpt_api_section' );
		add_settings_field( 'chatgpt_model', 'OpenAI Model', array( $this, 'model_field_html' ), 'chatgpt-settings', 'chatgpt_api_section' );
		add_settings_field( 'chatgpt_temperature', 'Temperature', array( $this, 'temperature_field_html' ), 'chatgpt-settings', 'chatgpt_api_section' );
		add_settings_field( 'chatgpt_max_tokens', 'Max Tokens', array( $this, 'max_tokens_field_html' ), 'chatgpt-settings', 'chatgpt_api_section' );
	}

	/**
	 * Display the API key field.
	 *
	 * @since 1.0.0
	 */
	public function api_key_field_html() {
		$api_key = get_option( 'chatgpt_api_key' );
		?>
		<input type="text" name="chatgpt_api_key" value="<?php echo esc_attr( $api_key ); ?>" />
		<?php
	}

	/**
	 * Display the model selection field.
	 *
	 * @since 1.0.0
	 */
	public function model_field_html() {
		$api_key = get_option( 'chatgpt_api_key' );

		if ( ! $api_key ) {
			echo '<p>' . esc_html__( 'Please enter your OpenAI API key to fetch available models.', 'oneclickcontent' ) . '</p>';
			return;
		}

		$model  = get_option( 'chatgpt_model' );
		$models = $this->get_openai_models();
		?>
		<select name="chatgpt_model" id="chatgpt_model" onchange="updateMaxTokensLimit()">
			<?php foreach ( $models as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Display the temperature field.
	 *
	 * @since 1.0.0
	 */
	public function temperature_field_html() {
		$temperature = get_option( 'chatgpt_temperature', '0.7' );
		?>
		<input type="number" name="chatgpt_temperature" value="<?php echo esc_attr( $temperature ); ?>" step="0.1" min="0" max="1" />
		<?php
	}

	/**
	 * Display the max tokens field.
	 *
	 * @since 1.0.0
	 */
	public function max_tokens_field_html() {
		$max_tokens = get_option( 'chatgpt_max_tokens', '150' );
		?>
		<input type="number" name="chatgpt_max_tokens" id="chatgpt_max_tokens" value="<?php echo esc_attr( $max_tokens ); ?>" min="1" max="4096" />
		<p id="max-tokens-info">Maximum tokens depend on the selected model.</p>
		<?php
	}

	/**
	 * Fetch available OpenAI models.
	 *
	 * @since 1.0.0
	 * @return array Available OpenAI models.
	 */
	private function get_openai_models() {
		$api_key = get_option( 'chatgpt_api_key' );

		if ( ! $api_key ) {
			return array();
		}

		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['data'] ) ) {
			return array();
		}

		$models = array();
		foreach ( $data['data'] as $model ) {
			$models[ $model['id'] ] = $model['id'];
		}

		return $models;
	}
}
?>
