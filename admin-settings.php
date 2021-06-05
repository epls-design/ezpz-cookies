<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

  <form method="post" name="cookie-bar-settings" action="">

    <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="cookie_bar_header_scripts"><?php _e( 'Header Scripts' ); ?></label></th>
          <td><textarea rows="6" name="cookie_bar_header_scripts" id="cookie_bar_header_scripts" class="large-text code" placeholder="Enter scripts to be include inside <head>"><?php echo $options['cookie_bar_header_scripts']; ?></textarea></td>
        </tr>
        <tr>
          <th scope="row"><label for="cookie_bar_body_scripts"><?php _e( 'Body Scripts' ); ?></label></th>
          <td><textarea rows="6" name="cookie_bar_body_scripts" id="cookie_bar_body_scripts" class="large-text code" placeholder="Enter scripts for inclusion after the opening <body> tag"><?php echo $options['cookie_bar_body_scripts']; ?></textarea></td>
        </tr>
        <tr>
          <th scope="row"><label for="cookie_bar_footer_scripts"><?php _e( 'Footer Scripts' ); ?></label></th>
          <td><textarea rows="6" name="cookie_bar_footerbody_scripts" id="cookie_bar_footer_scripts" class="large-text code" placeholder="Enter scripts for inclusion before the closing </body> tag"><?php echo $options['cookie_bar_footer_scripts']; ?></textarea></td>
        </tr>
        <tr>
          <th scope="row"><label for="cookie_bar_message"><?php _e( 'Cookie Bar Message' ); ?></label></th>
          <td>
          <?php
          $default_cookie_notice = sprintf(
            '%s<a href="/cookies/">%s</a>%s',
             __( 'We use various tracking cookies to help us better understand how visitors use our website and to improve the user experience. You can switch these cookies off if you would like. Read more about how we use cookies on our '),
             __( 'cookie policy'),
             __( ' page.'),
          );
          $cookie_bar_notice = isset($options['rhd-cookie-bar-header-scripts']) ? $options['rhd-cookie-bar-header-scripts'] : $default_cookie_notice;
          wp_editor(
            $cookie_bar_notice,
            'cookie_bar_message',
            $settings = array(
              'editor_class' => 'simple-wysiwig', // TODO: Use this class with display: none to hide unrequired buttons
              'media_buttons' => false,
              'textarea_rows' => '10',
              'teeny' => true
              ) );
          ?>
          </td>
        </tr>

    </table>

    <?php submit_button(); ?>

  </form>

</div>
