<?php
$banner_style = get_option( 'ezpz_cookiebar_settings' )['style'];
$cookie_bar_class = 'intrusive' == $banner_style ? 'cookiebar blocking' : 'cookiebar';
if($banner_style == 'intrusive') echo '<div class="cookiebar-overlay"></div>';
?>

<div class="<?php echo $cookie_bar_class;?>">
  <?php
  echo '<p class="cookiebar-heading">'.get_option( 'ezpz_cookiebar_settings' )['text']['cookie_bar_heading'].'</p>';
  echo wpautop(get_option( 'ezpz_cookiebar_settings' )['text']['cookie_bar_message']);
  ?>

  <div class="cookiebar-form">

    <div class="cookiebar-toggle-wrapper">
      <input type="checkbox" id="cookiebar-toggle-checkbox" class="cookiebar-toggle-checkbox" tabindex="1" name="cookiebar-toggle-checkbox" value="accepted" checked="" aria-checked="true" aria-label="<?php _e('Analytics cookies accepted','ezpz-cookies');?>">
      <label for="cookiebar-toggle-checkbox" class="cookiebar-toggle-label">
        <span><span class="sr-text"><?php _e('Accept ', 'ezpz-cookies');?></span><?php _e('Marketing and analytics cookies', 'ezpz-cookies');?></span>
        <span class="cookiebar-toggle"></span>
      </label>
    </div>

    <button tabindex="2" class="cookiebar-submit" id="cookiebar-save-prefs"><?php _e('Save Settings', 'ezpz-cookies');?></button>

  </div>

</div>
