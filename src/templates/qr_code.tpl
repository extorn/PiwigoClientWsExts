<script>
    function blurQRCode() {
        var elem = document.getElementById('qr_con_img');
        elem.style.filter = 'blur(10px)';
    }
    function hideQRCode() {
            var elem = document.getElementById('qr_con_img');
            elem.style.display = 'none';
    }
    function toggleQRCodeVisibility() {
        var elem = document.getElementById('qr_con_img');
        var currentFilter = elem.style.filter;
        elem.style.filter = 'none';
        elem.style.display = 'inline';
        setTimeout(blurQRCode, 2000);
        setTimeout(hideQRCode, 4000);
        /*
        if(currentFilter === 'blur(10px)') {
            elem.style.filter = 'none';
            elem.style.display = 'inline';
        } else {
            elem.style.filter = 'blur(10px)';
        }*/
    }
</script>
<fieldset>
    <legend>Piwigo Client - Connection Details</legend>
    <p/>
    This QR code can be scanned by your Android mobile device and will configure a connection for this server
     in app '<a href='https://play.google.com/store/apps/details?id=delit.piwigoclient.paid'>Piwigo Client - Pro</a>'
      (Note there is <b>NO AFFILIATION</b> to the similarly named official
      <a href='https://play.google.com/store/apps/details?id=com.piwigo.piwigo_ng'>Piwigo Team released app</a>).
      <p/>
      Once you've downloaded my app and agreed to the EULA, this QR Code will save you from entering your server address, and username.
      If you've extra security such as basic auth or client certificates, enter these along with your password into the app afterward.
    <p/>
    Note: The QR code contains server url, and username of this user. They are not encrypted.
    <p/>
    <button onclick="toggleQRCodeVisibility();">Show QR Code</button>
    <img style="display:none; filter: blur(10px);" id="qr_con_img" src="data:image/x-icon;base64,{$qr_img_src}"></img>
</fieldset>