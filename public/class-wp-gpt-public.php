<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Wp_Gpt
 * @subpackage Wp_Gpt/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_Gpt
 * @subpackage Wp_Gpt/public
 * @author     James Wilson <info@oneclickcontent.com>
 */
class Wp_Gpt_Public {

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
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-gpt-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-gpt-public.js', array( 'jquery' ), $this->version, true );

		wp_localize_script(
			$this->plugin_name,
			'chatgpt',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'wp_gpt', array( $this, 'wp_gpt_shortcode' ) );
	}

	/**
	 * Shortcode callback to display the chat form.
	 *
	 * @since 1.0.0
	 * @return string HTML output for the shortcode.
	 */
	public function wp_gpt_shortcode() {
		ob_start();
		?>
		<div id="chatgpt-chatbox">
			<div id="chatgpt-response"></div>
			<form id="chatgpt-form">
				<textarea name="user_input" id="user_input" required placeholder="Enter your question"></textarea>
				<button type="submit">Submit</button>
				<?php wp_nonce_field( 'chatgpt_submit', 'chatgpt_nonce' ); ?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle form submission and communicate with OpenAI API.
	 *
	 * @since 1.0.0
	 */
	public function handle_chatgpt_submit() {
		if ( ! isset( $_POST['chatgpt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['chatgpt_nonce'] ) ), 'chatgpt_submit' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! isset( $_POST['user_input'] ) || empty( $_POST['user_input'] ) ) {
			wp_send_json_error( 'Invalid input' );
		}

		$user_input      = sanitize_text_field( wp_unslash( $_POST['user_input'] ) );
		$conversation_id = isset( $_POST['conversation_id'] ) ? intval( $_POST['conversation_id'] ) : 0;
		$api_key         = get_option( 'chatgpt_api_key' );
		$model           = get_option( 'chatgpt_model', 'text-davinci-003' );
		$temperature     = get_option( 'chatgpt_temperature', '0.7' );
		$max_tokens      = get_option( 'chatgpt_max_tokens', '150' );

		if ( ! $api_key ) {
			wp_send_json_error( 'API key not set' );
		}

		// Determine the correct endpoint based on the model type.
		$is_chat_model = strpos( $model, 'gpt-3.5' ) !== false || strpos( $model, 'gpt-4' ) !== false;
		$endpoint      = $is_chat_model ? 'https://api.openai.com/v1/chat/completions' : 'https://api.openai.com/v1/completions';

		$body = $is_chat_model ? wp_json_encode(
			array(
				'model'       => $model,
				'messages'    => array(
					array(
						'role'    => 'user',
						'content' => $user_input,
					),
				),
				'temperature' => (float) $temperature,
				'max_tokens'  => (int) $max_tokens,
			)
		) : wp_json_encode(
			array(
				'model'       => $model,
				'prompt'      => $user_input,
				'temperature' => (float) $temperature,
				'max_tokens'  => (int) $max_tokens,
			)
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => $body,
				'timeout' => 20, // Set timeout to 20 seconds.
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Error communicating with OpenAI API' );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );

		$data = json_decode( $body, true );

		if ( $is_chat_model && isset( $data['choices'][0]['message']['content'] ) ) {
			$response_text = $data['choices'][0]['message']['content'];
		} elseif ( ! $is_chat_model && isset( $data['choices'][0]['text'] ) ) {
			$response_text = $data['choices'][0]['text'];
		} else {
			wp_send_json_error( 'Invalid response from OpenAI API' );
		}

		// Save or update the conversation.
		if ( $conversation_id > 0 ) {
			// Update existing conversation.
			$post = get_post( $conversation_id );
			if ( $post && 'conversation' === $post->post_type ) {
				$new_content = $post->post_content . "\n\nUser: " . $user_input . "\n\nChatGPT: " . $response_text;
				wp_update_post(
					array(
						'ID'           => $conversation_id,
						'post_content' => $new_content,
					)
				);
			} else {
				wp_send_json_error( 'Invalid conversation ID' );
			}
		} else {
			// Create new conversation.
			$conversation_id = wp_insert_post(
				array(
					'post_title'   => wp_trim_words( $user_input, 5, '...' ),
					'post_content' => 'User: ' . $user_input . "\n\nChatGPT: " . $response_text,
					'post_status'  => 'publish',
					'post_type'    => 'conversation',
				)
			);

			if ( is_wp_error( $conversation_id ) ) {
				wp_send_json_error( 'Error saving conversation' );
			}
		}

		wp_send_json_success(
			array(
				'response_text'   => $response_text,
				'conversation_id' => $conversation_id,
			)
		);
	}
}
?>
