<div class="cookiebar-overlay"></div>

<div class="cookiebar">
  <?php
  echo '<h5 class="cookiebar-heading">'.__('This website uses cookies').'</h5>';
  $cookiebar_message = get_option( 'ezpz_cookiebar_settings' )['cookie_bar_message'];
  echo wpautop($cookiebar_message);
  ?>

  <div class="cookiebar-toggle-wrapper">
    <input type="checkbox" id="cookiebar-toggle-checkbox" class="cookiebar-toggle-checkbox" tabindex="" checked>
    <label for="cookiebar-toggle-checkbox" class="cookiebar-toggle-label">
      <span><span class="sr-text"><?php _e('Accept ');?></span><?php _e('Marketing and analytics cookies');?></span>
      <span class="cookiebar-toggle"></span>
    </label>
  </div>

  <button tabindex="2" class="button cookiebar-submit"><?php _e('Save Settings');?></button>

</div>