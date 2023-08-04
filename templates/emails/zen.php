<?php

namespace Mail_Control;

/**
 * This template originated from github.com:sendwithus/templates.git projet
 */
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- -->
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo esc_html($subject); ?></title>
  <?php do_action('mc_header'); ?>
  <style type="text/css" media="screen">

	/* Force Hotmail to display emails at full width */
	.ExternalClass {
	  display: block !important;
	  width: 100%;
	}

	/* Force Hotmail to display normal line spacing */
	.ExternalClass,
	.ExternalClass p,
	.ExternalClass span,
	.ExternalClass font,
	.ExternalClass td,
	.ExternalClass div {
	  line-height: 100%;
	}

	body#email_body{
		min-height: 100vh;
	}

	body,
	p,
	h1,
	h2,
	h3,
	h4,
	h5,
	h6 {
	  margin: 0;
	  padding: 0;
	}
	h1,h2,h3,h4,h5{
	  color: <?php echo esc_attr($title_color); ?>;
	  font-size: <?php echo esc_attr($title_font_size); ?>px;
	  margin-bottom: <?php echo esc_attr($title_margin_bottom); ?>px;
	  text-transform: <?php echo esc_attr($title_transform); ?>;
	}
	body,
	p,
	td {
	  font-family: <?php echo $main_font_family ? esc_attr(get_font_family($main_font_family)) : 'inherit'; ?> !important;
	  font-size: <?php echo esc_attr($main_font_size); ?>px;
	  line-height: 1.5em;
	}

	h1 {
	  font-size: <?php echo esc_attr(round(intval($title_font_size) * 1.4)); ?>px;
	  font-weight: normal;
	  line-height: 1.5em;
	}
	h2 {
	  font-size: <?php echo esc_attr(round(intval($title_font_size) * 1.3)); ?>px;
	  line-height: 1.4em;
	}
	h3 {
	  font-size: <?php echo esc_attr(round(intval($title_font_size) * 1.2)); ?>px;
	  line-height: 1.3em;
	}

	body,
	p {
	  margin-bottom: 0;
	  -webkit-text-size-adjust: none;
	  -ms-text-size-adjust: none;
	}
	img {
	  outline: none;
	  text-decoration: none;
	  -ms-interpolation-mode: bicubic;
	}

	a img {
	  border: none;
	}

	table td {
	  border-collapse: collapse;
	  vertical-align: top;
	  text-align: left;
	}

	td.background {
	  background-color: <?php echo esc_attr($main_bg_color); ?>;
	}

	table.background {
	  margin: 0;
	  padding: 0;
	  width: 100% !important;
	}
	
	.body-cell p {
	  margin-bottom: 10px;
	  color: <?php echo esc_attr($txt_color); ?>;
	}

	ul {
	  padding-left: 0px;
	}
	
	.body-cell li{
	  color: <?php echo esc_attr($txt_color); ?>;
	}

	.block-img {
	  display: block;
	  line-height: 0;
	}

	.body-cell a,
	.body-cell a:link {
	  color: <?php echo esc_attr($link_color); ?>;
	  text-decoration: underline;
	}

	.wrap {
	  width: <?php echo intval($container_width); ?>px;
	}

	.wrap-cell {
	  padding-top: 30px;
	  padding-bottom: 30px;
	}

	.header-cell,
	.body-cell,
	.footer-cell {
	  padding-left: 20px;
	  padding-right: 20px;
	}

	td.header-cell {
	  background-color: <?php echo esc_attr($header_color); ?>;
	  font-size: 24px;
	  color: #ffffff;
	  padding-top: 20px;
	  padding-bottom: 20px;
	  text-align: <?php echo esc_attr($logo_position); ?>;
	}
	td.header-cell img{
	  width: <?php echo intval($logo_width); ?>px;
	}

	.body-cell {
	  background-color: #ffffff;
	  color:  <?php echo esc_attr($txt_color); ?>;
	  padding: <?php echo intval($container_padding); ?>px;
	}

	td.footer-cell {
	  background-color: <?php echo esc_attr($footer_bg_color); ?>;
	  color: <?php echo esc_attr($footer_txt_color); ?>;;
	  text-align: center;
	  padding-top: 30px;
	  padding-bottom: 30px;
	}

	.force-full-width {
	  width: 100% !important;
	}

	/** BUTTON STYLE **/

	.body-cell a.btn, .body-cell a.button {
	  background:<?php echo esc_attr($button_bg_color); ?>;
	  color: <?php echo esc_attr($button_txt_color); ?>;
	  font-size: <?php echo intval($button_font_size); ?>px;
	  padding: <?php echo intval($button_padding_tb); ?>px <?php echo intval($button_padding_lr); ?>px;
	  border-radius: <?php echo intval($button_radius); ?>px;
	  width: auto;
	  display: inline-flex;
	  border: none;
	  margin-top: 10px;
	  margin-bottom: 10px;
	  text-decoration: none;
	}

	.body-cell a.btn:hover, .body-cell a.button:hover {
	  background:<?php echo esc_attr($button_bg_color_hv); ?>;
	  color: <?php echo esc_attr($button_txt_color_hv); ?>;
	}

	<?php if (is_woocommerce_active()) : ?>
	/* Woocommerce */
	td.td, th.td , table.td{
		font-size: <?php echo intval($table_font_size); ?>px;
		border: <?php echo floatval($table_border_size); ?>px solid <?php echo esc_attr($table_border_color); ?>  !important;
	}
	<?php endif; ?>

  </style>
  <style type="text/css" media="only screen and (max-width: 600px)">
	@media only screen and (max-width: 600px) {
	  body[class*="background"],
	  table[class*="background"],
	  td[class*="background"] {
		background: <?php echo esc_attr($main_bg_color); ?> !important;
	  }


	  table[class="wrap"] {
		width: 100% !important;
	  }

	  td[class="wrap-cell"] {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	  }
	}
  </style>
  <?php
    if ($additional_css) {
        echo '<style type="text/css" >' . wp_kses_post($additional_css) . '</style>';
    }
?>
</head>

<body id="email_body" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" bgcolor="" class="background">
  <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" class="background">
	<tr>
	  <td align="center" valign="top" width="100%" class="background">
		<center>
		  <table cellpadding="0" cellspacing="0" width="600" class="wrap">
			<tr>
			  <td valign="top" class="wrap-cell" >
				<table cellpadding="0" cellspacing="0" class="force-full-width">
				  <?php if ($logo) : ?>
				  <tr>
					  <td id="email_logo" valign="top" class="header-cell">
						<img  src="<?php echo esc_url($logo); ?>" alt="logo" />
					  </td>
				  </tr>
				  <?php endif; ?>
				  <tr>
					<td valign="top" class="body-cell">
						<?php echo wp_kses_post($content); ?>
					</td>
				  </tr>
				  <?php if ($footer) : ?>
				  <tr>
					<td id="footer_text" valign="top" class="footer-cell" >
						<?php echo wp_kses_post($footer); ?>
					</td>
				  </tr>
				  <?php endif; ?>
				  <tr>
				   <td id="footer_widget" valign="top" class="footer-cell" >
					<?php
                if (is_active_sidebar('mc_email_footer')) {
                    dynamic_sidebar('mc_email_footer');
                }
?>
					</td>
				   </tr>
				</table>
			  </td>
			</tr>
		  </table>
		</center>
	  </td>
	</tr>
  </table>
<?php
do_action('mc_footer');
?>
</body>
</html>
