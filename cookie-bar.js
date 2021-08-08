/****************
 * Cookie Bar Helpers
 ****************/

function eplsSetCookie(name, value, days) {
  var expires;
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = ";expires=" + date.toUTCString();
  }
  else {
    expires = "";
  }
  // Sets the cookie site-wide with path=/
  document.cookie = name + "=" + encodeURIComponent(value) + expires + ";path=/";
}

function eplsGetCookie(name) {
  var name = name + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return null;
}

function eplsDestroyCookie(name) {
  document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
}

/****************
 * Detect Cookie and Display Banner
 ****************/

jQuery(document).ready(function (e) {

  // Load essential scripts
  eplsEnqueueEssentialScripts();

  // Check for Cookie
  var ezpzCookieValue = eplsGetCookie(ezpzCookieName);

  // The cookie exists
  if (ezpzCookieValue != null && ezpzCookieValue != '') {
    if (ezpzCookieValue == 'accepted') {
      eplsEnqueueOptInScripts();
    }
    if (ezpzCookieValue == 'rejected') {
      eplsRevokeThirdPartyCookies();
    }
  }
  // Display cookie bar
  else {
    if (ezpzCookieSettings.settings.cookie_bar_active == 'true' && typeof ezpzCookieSettings.settings.hide_banner == 'undefined') {
      eplsShowCookieBar();
    }
  }

  /**
   * Set Cookie
   */
  jQuery(document).on('click', '.cookiebar-submit', function (e) {
    // Set Cookie
    if (jQuery('input.cookiebar-toggle-checkbox').prop('checked')) {
      eplsSetCookie(ezpzCookieName, 'accepted', 30);
      eplsEnqueueOptInScripts();
    }
    else {
      eplsSetCookie(ezpzCookieName, 'rejected', 30);
      eplsRevokeThirdPartyCookies();
    }
    eplsHideCookieBar();
  })

  /**
   * Show and Hide Cookie Bar
   */
  function eplsHideCookieBar() {
    jQuery('.cookiebar-overlay').fadeOut('500');
    jQuery('.cookiebar').fadeOut('500');
  }

  function eplsShowCookieBar() {

    var marketingToggleChecked;

    if (ezpzCookieValue == 'rejected') {
      marketingToggleChecked = '';
    }
    else {
      marketingToggleChecked = 'checked';
    }

    // Create Cookiebar overlay if it doesn't exist, and the style is intrusive
    if (ezpzCookieSettings.settings.style == 'intrusive' && jQuery('.cookiebar-overlay').length == 0) {
      jQuery('body').append('<div class="cookiebar-overlay"></div>');
    }

    // Create cookiebar if it doesn't exist yet
    if (jQuery('.cookiebar').length == 0) {
      jQuery('body').append('<div role="banner" class="cookiebar ' + ezpzCookieSettings.settings.style + '"><p class="cookiebar-heading">' + ezpzCookieSettings.settings.text.cookie_bar_heading + '</p><p>' + ezpzCookieSettings.settings.text.cookie_bar_message + '</p><div class="cookiebar-form"><div class="cookiebar-toggle-wrapper"><input type="checkbox" id="cookiebar-toggle-checkbox" class="cookiebar-toggle-checkbox" tabindex="1" name="cookiebar-toggle-checkbox" ' + marketingToggleChecked + '> <label for="cookiebar-toggle-checkbox" class="cookiebar-toggle-label"><span><span class="sr-text">Accept</span>Marketing and analytics cookies</span><span class="cookiebar-toggle"></span></label></div><button tabindex="2" class="cookiebar-submit">Save Settings</button></div></div></div>');
    }
    // If it does exist, just show it.
    else {
      jQuery('.cookiebar').fadeIn();
    }

  }

  jQuery(".cookiebar-show").click(function () {
    eplsShowCookieBar();
  })

});

/**
 * Script Executions
 */
function eplsEnqueueEssentialScripts() {
  if (typeof ezpzCookieSettings.scripts.essential != 'undefined') {
    if (typeof ezpzCookieSettings.scripts.essential.header_scripts != 'undefined') {
      jQuery('head').append(ezpzCookieSettings.scripts.essential.header_scripts);
    }
    if (typeof ezpzCookieSettings.scripts.essential.body_scripts != 'undefined') {
      jQuery('body').prepend(ezpzCookieSettings.scripts.essential.body_scripts);
    }
  }
}
function eplsEnqueueOptInScripts() {
  if (typeof ezpzCookieSettings.scripts.optin != 'undefined') {
    if (typeof ezpzCookieSettings.scripts.optin.header_scripts != 'undefined') {
      jQuery('head').append(ezpzCookieSettings.scripts.optin.header_scripts);
    }
    if (typeof ezpzCookieSettings.scripts.optin.body_scripts != 'undefined') {
      jQuery('body').prepend(ezpzCookieSettings.scripts.optin.body_scripts);
    }
  }
}
function eplsRevokeThirdPartyCookies() {
  if (ezpzCookieSettings.settings.cookies.unset_on_revoke != "") {
    jQuery(ezpzCookieSettings.settings.cookies.unset_on_revoke.split(',')).each(function (index, cookie) {
      eplsDestroyCookie(cookie);
      console.log(cookie + ' has been destroyed');
    });
  }
}
