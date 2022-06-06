<?php
/*
Plugin Name: Pay to Comment with Lightning
Version: 0.2.1
Plugin URI: https://wordpress.org/plugins/wp-lightning-comments/
Author: @citlayik
Author URI:  https://kriptode.com
Description: Require the user to send a bitcoin payment to be able to post comments.
*/

define('LightningComments', 'lncomments');
define('LightningComments_NONCE', LightningComments . '-nonce');

add_action('wp_enqueue_scripts', function(){
  wp_enqueue_script('qrious', plugins_url('qrious.min.js', __FILE__));
  wp_enqueue_script(LightningComments, plugins_url('lightningcomments.js', __FILE__));
  wp_enqueue_style(LightningComments, plugins_url('lightningcomments.css', __FILE__));
});

add_action('comment_form_before', 'lncomments_form');
function lncomments_form(){ ?>
  <div class="<?php echo LightningComments; ?>"
    data-<?php echo LightningComments; ?>="<?php echo esc_attr(rawurlencode(json_encode(get_post_meta(get_the_ID(), LightningComments)))); ?>"
    data-<?php echo LightningComments; ?>-error="<?php echo esc_attr(__('You have not sent the payment correctly. Try again.', LightningComments)); ?>">
    <!-- Would you like to comment?  -->
    <h5 style="text-align: center;">Please send a small Bitcoin payment via Lightning to enable comments.</h5>
    <div id="lncomments-settings" style="display:none;">
      <?php lncomments_text_field_1_render(); ?>
      <?php lncomments_text_field_2_render(); ?>
      <?php lncomments_text_field_3_render(); ?>
      <?php lncomments_text_field_4_render(); ?>
      <?php lncomments_text_field_5_render(); ?>
      <?php lncomments_text_field_6_render(); ?>
    </div>
    <!-- <p>
      We love comments & hate spam.
      That's why we want to make sure that everyone who comments have actually read the story.
      Answer a couple of questions from the story to unlock the comment form.
    </p> -->
    <!-- <input id="yilcheck" type="hidden" name="antspm-a" class="antispam-control antispam-control-a" /> -->
    <!-- value="'.date('Y').'" -->
    <!-- <input id="boscheck" type="text" name="antspm-e-email-url-website" class="antispam-control antispam-control-e" value="" autocomplete="off" /> -->
    <noscript>Please <a href="http://enable-javascript.com/" target="_blank" style="text-decoration:underline">enable javascript</a> to comment</noscript>
  </div>
<?php }

add_action('add_meta_boxes', 'lncomments_add');
function lncomments_add(){
  // add_meta_box(LightningComments, 'CommentQuiz', 'lncomments_edit', 'post', 'side', 'high');
}

function lncomments_edit($post){
  $questions = array_pad(get_post_meta($post->ID, LightningComments), 1, array());
  $addmore = esc_html(__('Add question +', LightningComments));
  $correct = esc_html(__('Correct', LightningComments));
  $answer = esc_attr(__('Answer', LightningComments));

  foreach($questions as $index => $question){
    $title = __('Question', LightningComments) . ' ' . ($index + 1);
    $text = esc_attr(empty($question['text'])? '' : $question['text']);
    $name = LightningComments . '[' . $index . ']';

    echo '<div style="margin-bottom:1em;padding-bottom:1em;border-bottom:1px solid #eee">';
    echo '<label><strong>' . $title . ':</strong><br><input type="text" name="' . $name . '[text]" value="' . $text . '"></label>';
    for($i = 0; $i<3; $i++){
      $check = checked($i, isset($question['correct'])? intval($question['correct']) : 0, false);
      $value = isset($question['answer'][$i])? esc_attr($question['answer'][$i]) : '';

      echo '<br><input type="text" name="' . $name . '[answer][' . $i . ']" placeholder="' . $answer . '" value="' . $value . '">';
      echo '<label><input type="radio" name="' . $name . '[correct]" value="' . $i . '"' . $check . '> ' . $correct . '</label>';
    }
    echo '</div>';
  }
  echo '<button class="button" type="button" data-' . LightningComments . '>' . $addmore . '</button>';

  ?><script>
    document.addEventListener('click', function(event){
      if(event.target.hasAttribute('data-<?php echo LightningComments; ?>')){
        var button = event.target;
        var index = [].indexOf.call(button.parentNode.children, button);
        var clone = button.previousElementSibling.cloneNode(true);
        var title = clone.querySelector('strong');

        title.textContent = title.textContent.replace(/\d+/, index + 1);
        [].forEach.call(clone.querySelectorAll('input'), function(input){
          input.name = input.name.replace(/\d+/, index);  //Update index
          if(input.type === 'text')input.value = '';      //Reset value
        });
        button.parentNode.insertBefore(clone, button);    //Insert in DOM
      }
    });
  </script>
  <!-- <script>
    var comform = document.getElementById("commentform");
    var hidinput = document.createElement("input");
    hidinput.type = "text";
    hidinput.required = true;
    hidinput.id = "ipitythefool";
    comform.append(hidinput);
  </script> -->
  <?php wp_nonce_field(LightningComments, LightningComments_NONCE);
}

add_action('save_post', 'lncomments_save', 10, 3);
function lncomments_save($post_id, $post, $update){
  if(isset($_POST[LightningComments], $_POST[LightningComments_NONCE]) && wp_verify_nonce($_POST[LightningComments_NONCE], LightningComments)){
    delete_post_meta($post_id, LightningComments);                         //Clean up previous quiz meta
    foreach($_POST[LightningComments] as $k=>$v){
      if($v['text'] && array_filter($v['answer'], 'strlen')){   //Only save filled in questions

        // Sanitizing data input
        foreach ( $v as $key => $value ) {
          $key = wp_kses_post( $key );
          $value = wp_kses_post( $value );
          $v[$key] = $value;
        }

        add_post_meta($post_id, LightningComments, $v);
      }
    }
  }
}


/**
 * Add our field to the comment form
 */
add_action( 'comment_form_logged_in_after', 'pmg_comment_tut_fields' );
add_action( 'comment_form_after_fields', 'pmg_comment_tut_fields' );
function pmg_comment_tut_fields()
{
    ?>
    <p class="comment-form-title">
        <label for="pmg_comment_titlez"><?php _e( 'Title' ); ?></label>
        <input type="hidden" name="pmg_comment_titlez" id="pmg_comment_titlez" required/>
    </p>
    <?php
}

add_filter( 'preprocess_comment', 'verify_comment_meta_data' );
function verify_comment_meta_data( $commentdata ) {
    if ( ! isset( $_POST['pmg_comment_titlez'] ) || $_POST['pmg_comment_titlez'] != "youreok" )
        wp_die( __( 'Error: bad bot. go away.' ) );
    return $commentdata;
}


// settings
add_action( 'admin_menu', 'lncomments_add_admin_menu' );
add_action( 'admin_init', 'lncomments_settings_init' );


function lncomments_add_admin_menu(  ) {
	add_options_page( 'Pay to Comment with Lightning', 'Pay to Comment with Lightning', 'manage_options', 'lightning_comments', 'lncomments_options_page' );
}


function lncomments_settings_init(  ) {
	register_setting( 'pluginPage', 'lncomments_settings' );

	add_settings_section(
		'lncomments_pluginPage_section',
		__( 'Pay to Comment with Lightning Settings', 'wordpress' ),
		'lncomments_settings_section_callback',
		'pluginPage'
	);

	// add_settings_field(
	// 	'lncomments_checkbox_field_0',
	// 	__( 'Testnet if checked', 'wordpress' ),
	// 	'lncomments_checkbox_field_0_render',
	// 	'pluginPage',
	// 	'lncomments_pluginPage_section'
	// );


	add_settings_field(
		'lncomments_text_field_3',
		__( 'Comment Amount (sats)', 'wordpress' ),
		'lncomments_text_field_3_render',
		'pluginPage',
		'lncomments_pluginPage_section'
	);
	add_settings_field(
		'lncomments_text_field_1',
		__( 'LNBits URL (e.g. https://legend.lnbits.com)', 'wordpress' ),
		'lncomments_text_field_1_render',
		'pluginPage',
		'lncomments_pluginPage_section'
	);
	add_settings_field(
		'lncomments_text_field_2',
		__( 'LNBits Invoice/Read Key', 'wordpress' ),
		'lncomments_text_field_2_render',
		'pluginPage',
		'lncomments_pluginPage_section'
	);
	add_settings_field(
		'lncomments_text_field_4',
		__( 'BTCPayServer URL (e.g. https://mainnet.demo.btcpayserver.org)', 'wordpress' ),
		'lncomments_text_field_4_render',
		'pluginPage',
		'lncomments_pluginPage_section'
	);
	add_settings_field(
		'lncomments_text_field_5',
		__( 'BTCPayServer Store ID', 'wordpress' ),
		'lncomments_text_field_5_render',
		'pluginPage',
		'lncomments_pluginPage_section'
	);
	add_settings_field(
		'lncomments_text_field_6',
		__( 'BTCPayServer Greenfield Api Key (permissions: canviewinvoices & cancreateinvoice)', 'wordpress' ),
		'lncomments_text_field_6_render',
		'pluginPage',
		'lncomments_pluginPage_section'
	);
  // btcpay.store.cancreateinvoice

}


function lncomments_checkbox_field_0_render(  ) {

	$options = get_option( 'lncomments_settings' );
	?>
	<input type='checkbox' id="lncomments_checkbox_field_0" name='lncomments_settings[lncomments_checkbox_field_0]' <?php checked( $options['lncomments_checkbox_field_0'], 1 ); ?> value='1'>
	<?php

}


function lncomments_text_field_1_render(  ) {
	$options = get_option( 'lncomments_settings' );
	?>
	<input type='text' style='min-width: 300px;' name='lncomments_settings[lncomments_text_field_1]' value='<?php echo $options['lncomments_text_field_1']; ?>'>
	<?php
}

function lncomments_text_field_2_render(  ) {
	$options = get_option( 'lncomments_settings' );
	?>
  <input type='text' style='min-width: 300px;' name='lncomments_settings[lncomments_text_field_2]' value='<?php echo $options['lncomments_text_field_2']; ?>'>
	<?php
}

function lncomments_text_field_3_render(  ) {
	$options = get_option( 'lncomments_settings' );
	?>
	<input type='text' style='min-width: 300px;' name='lncomments_settings[lncomments_text_field_3]' value='<?php echo $options['lncomments_text_field_3']; ?>'>
	<?php
}

function lncomments_text_field_4_render(  ) {
	$options = get_option( 'lncomments_settings' );
	?>
	<input type='text' style='min-width: 300px;' name='lncomments_settings[lncomments_text_field_4]' value='<?php echo $options['lncomments_text_field_4']; ?>'>
	<?php
}
function lncomments_text_field_5_render(  ) {
	$options = get_option( 'lncomments_settings' );
	?>
	<input type='text' style='min-width: 300px;' name='lncomments_settings[lncomments_text_field_5]' value='<?php echo $options['lncomments_text_field_5']; ?>'>
	<?php
}
function lncomments_text_field_6_render(  ) {
	$options = get_option( 'lncomments_settings' );
	?>
	<input type='text' style='min-width: 300px;' name='lncomments_settings[lncomments_text_field_6]' value='<?php echo $options['lncomments_text_field_6']; ?>'>
	<?php
}

function lncomments_settings_section_callback(  ) {

	echo __( 'Populate URL and invoice key for your preferred provider', 'wordpress' );

}


function lncomments_options_page(  ) {

	?>
	<form action='options.php' method='post'>

		<!-- <h2>Lightning Comments</h2> -->

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	<?php

}
