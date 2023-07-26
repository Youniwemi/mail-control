<?php

namespace Mail_Control;

if (class_exists('\WP_Customize_Control')) {
    class Send_Preview_Control extends \WP_Customize_Control
    {
        public $type = 'send_preview';


        public function render_content()
        {
            $nonce = wp_create_nonce("secure-nonce");
            ?>
            <input type="hidden"  name="preview_email_once" value="<?php echo esc_attr($nonce) ; ?>" />
			<span class="customize-control-title">
				<?php echo esc_html($this->label); ?>
			</span>
			<?php if (! empty($this->description)) : ?>
				<span class="description customize-control-description"><?php echo esc_html($this->description); ?></span>
			<?php endif; ?>
			<input type="email" multiple name="recipients" value="<?php echo esc_attr($this->value()); ?>" id="customize-input-<?php esc_attr_e($this->id); ?>" <?php $this->input_attrs();
            $this->link(); ?>>
			<div style="padding: 10px;"><?php esc_html_e('Settings must be saved to send preview email.', 'mail-control'); ?></div>
			<input type="button" class="button button-primary mail-control-button" id="mail-control-send-email" value="<?php esc_attr_e('Send Email', 'mail-control'); ?>" />

			<script>
				jQuery(document).ready(function($) {
					$('#mail-control-send-email').click(function(){
						var me = $(this);
						var recipients = $('input[name=recipients').val();

						var data = {
							action: 'send_preview_email',
							recipients:     recipients,
							preview_email_once : <?php echo wp_json_encode($nonce); ?>
						};

						me.prop('disabled', true);
						// Send request to server
						$.post( ajaxurl, data, function( result ) {
							 if ( result != 0 ) {
						    	alert( 'sent' );
						    } else {
						    	alert( 'error' );
						    }
						    me .prop('disabled', false);
						});

					});

				});				
			</script>

			<?php
        }
    }
}
