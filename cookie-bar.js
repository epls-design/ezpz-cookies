(function ($) {

  // TODO: Take a look at setting a JS Event like https://www.cyber-duck.co.uk/

  function cbSetCookie(name, value, days) {
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

  function cbGetCookie(name) {
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

  function cbDestroyCookie(name) {
    document.cookie = name + '=; Max-Age=-99999999;';
  }

  // Save Cookie Prefs on Save
  $("#cookiebar-save-prefs").click(function () {

    // Hide UI Elements
    $(".cookiebar").hide("slow");
    $(".cookiebar-overlay").hide();

    // Set Cookie
    if ($('input.cookiebar-toggle-checkbox').prop('checked')) {
      cbSetCookie(cookiePrefsName, 'accepted', 30);
    }
    else {
      cbSetCookie(cookiePrefsName, 'rejected', 30);
    }
    location.reload(); // TODO: Check this doesn't count as extra traffic in GA or as a direct rather than eg. social source. EventListeners will probably fix this.


  });

})(jQuery);
