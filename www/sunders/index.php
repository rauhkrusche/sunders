<?php
  include './config.php';
  include './add-lists.php';

  $initialIsDefault = 'true';
  $initialZoom = DEFAULT_ZOOM;
  $initialLat = DEFAULT_LAT;
  $initialLon = DEFAULT_LON;

  /* Check if the URL contains a numeric zoom value and if that value is between 1 and 18.
      If not use DEFAULT_ZOOM from config.php. */
  if (array_key_exists('zoom', $_GET)) {
    $initialZoom = $_GET['zoom'];
    if (is_numeric($initialZoom) && intval($initialZoom) >= 1 && intval($initialZoom) <= 18) {
      $initialIsDefault = 'false';
    } else {
      $initialZoom = DEFAULT_ZOOM;
    }
  }

  /* Check if the URL contains a numeric lat value and a numeric lon value.
      If not use DEFAULT_LAT and DEFAULT_LON from config.php. */
  if (array_key_exists('lat', $_GET) && array_key_exists('lon', $_GET)) {
    $initialLat = $_GET['lat'];
    $initialLon = $_GET['lon'];
    if (is_numeric($initialLat) && is_numeric($initialLon)) {
      $initialIsDefault = 'false';
    } else {
      $initialLat = DEFAULT_LAT;
      $initialLon = DEFAULT_LON;
    }
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset='UTF-8'/>
    <title>Surveillance under Surveillance</title>

    <link rel='shortcut icon' href='./favicon.ico'>
    <link rel='icon' type='image/png' href='./favicon.png' sizes='32x32'>
    <link rel='apple-touch-icon' sizes='180x180' href='./apple-touch-icon.png'>
    <meta name='msapplication-TileColor' content='#f1eee8'>
    <meta name='msapplication-TileImage' content='./mstile-144x144.png'>
  </head>
  <body>

    <link rel='stylesheet' href='./Leaflet/leaflet.css'>
    <link rel='stylesheet' href='./Leaflet.label/leaflet.label.css'>
    <link rel='stylesheet' href='./css/sunders.css'>

    <input type='checkbox' id='slider-toggle'>
    <label for='slider-toggle'>
      <img src='./images/slider-toggle.png'>
    </label>

    <div id='map'></div>

    <div class='linkbar'>
      <a title='what this is about' href='#what'><img src='./images/link-what.png' alt='what this is about'></a>
      <a title='how to participate' href='#how'><img src='./images/link-how.png' alt='how to participate'></a>
      <a title='where to get more info' href='#where'><img src='./images/link-where.png' alt='where to get more info'></a>
    </div>

    <div class='slider'>
      <div class='slider-item slider-logo'>
        <img src='./images/logo.png' alt='Surveillance under Surveillance'>
      </div>
      <div id='what'></div>
      <div class='slider-item slider-title'>
        <img src='./images/title-what.png' alt='what this is about'>
      </div>
      <div class='slider-item'>
        <p>Surveillance under Surveillance shows you cameras and guards &mdash; watching you &mdash; almost everywhere. You can see where they are located and, if the information is available, what type they are, the area they observe, or other interesting facts.</p>
      </div>
      <div class='slider-item'>
        <p>Different icons and colors give you a quick overview about the indexed surveillance entries. Click on those icons on the map to get the available information.<p>
      </div>

      <?php
        addListSymbology('./json/symbology.json');
      ?>

      <div id='how'></div>
      <div class='slider-item slider-title'>
        <img src='./images/title-how.png' alt='how to participate'>
      </div>
      <div class='slider-item'>
        <p>Surveillance under Surveillance uses data from Openstreetmap contributors that is not visualized on the regular <a href="https://www.openstreetmap.org" target="_blank">Openstreetmap</a> site. If you like to add new cameras or guards or if you like to revise existing entries <a href="https://www.openstreetmap.org/login" target="_blank">use your existing OSM account</a> or <a href="https://www.openstreetmap.org/user/new" target="_blank">create a new one</a>.</p>
      </div>
      <div class='slider-item'>
        <p>Our database is updated once an hour. So it might take a while until your OSM entries are visible on the Surveillance under Surveillance map.</p>
      </div>
      <div class='slider-item'>
        <p>These are the most common key/value combinations to describe a surveillance node at Openstreetmap:</p>
      </div>

      <?php
        addListManual('./json/manual.json');
      ?>

      <div class='slider-item'>
        <p><br><br>Besides contributing to Openstreetmap feel free to fork this project on <a href='https://github.com/kamba4/sunders' target='_blank'>Github</a>.</p>
      </div>
      <div id='where'></div>
      <div class='slider-item slider-title'>
        <img src='./images/title-where.png' alt='where to get more info'>
      </div>
      <div class='slider-item'>
        <p>Visit the following sites about surveillance related topics:</p>
      </div>

      <?php
        addListLinks('./json/links.json');
      ?>

      <div class='slider-item slider-footer text-small'>
        &#x2756; &#x2756; &#x2756;
        <br><br><br>
        <p>Surveillance under Surveillance is based on the phantastic <a href='https://github.com/khris78/osmcamera' target='_blank'>osmcamera</a> [<a href='./files/license_osmcamera.txt' target='_blank'>CC-BY-SA / MIT / GPLv3 / WTFPL</a>] project of <a href='https://github.com/khris78' target='_blank'>khris78</a>. Furthermore it uses the v0.7.7 code of <a href='https://github.com/Leaflet/Leaflet' target='_blank'>Leaflet/Leaflet</a> [<a href='./files/license_Leaflet.txt' target='_blank'>BSD-2-Clause</a>] and the v0.2.1 code of <a href='https://github.com/Leaflet/Leaflet.label' target='_blank'>Leaflet/Leaflet.label</a> [<a href='./files/license_Leaflet.label.txt' target='_blank'>MIT</a>]. The map itself is the work of millions of <a href='https://www.openstreetmap.org' target='_blank'>OpenStreetMap</a> [<a href='https://www.openstreetmap.org/copyright' target='_blank'>CC BY-SA</a>] contributors. The eye and the locks are icons of <a href='http://fontawesome.io' target='_blank'>Font Awesome</a> [<a href='http://fontawesome.io/license/' target='_blank'>SIL OFL 1.1 / MIT / CC BY 3.0</a>]. The font <a href='https://fontlibrary.org/de/font/grabstein-grotesk' target='_blank'>Grabstein Grotesk</a> [<a href='http://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=OFL' target='_blank'>OLF</a>] is used for the titles.</p>
        <br><br>
        &#x041C;&#x0410;&#x041A;&#x0421; &#x041A;&#x0410;&#x041C;&#x0412;&#x0410;&#x0427;<br>
        Aljoscha Rompe Laan 5<br>
        2517 AR Den Haag<br>
        &#x73;&#x75;&#x6e;&#x64;&#x65;&#x72;&#x73; &#x28;&#x61;&#x74;&#x29; &#x6b;&#x61;&#x6d;&#x62;&#x61;&#x34; &#x28;&#x64;&#x6f;&#x74;&#x29; &#x63;&#x72;&#x75;&#x78; &#x28;&#x64;&#x6f;&#x74;&#x29; &#x75;&#x62;&#x65;&#x72;&#x73;&#x70;&#x61;&#x63;&#x65; &#x28;&#x64;&#x6f;&#x74;&#x29; &#x64;&#x65;<br><br>
        Here is our <a href='./files/sunders.asc' target='_blank'>PGP key</a> &mdash; use it!<br>
        EE12 1A7D C3FB 52BD 46AA<br>
        DD0D 547B 21CD C20D DD88<br><br>
      </div>
    </div>

    <script language='javascript'>
      <?php
        echo "var initialIsDefault = $initialIsDefault;\n";
        echo "var initialZoom = $initialZoom;\n";
        echo "var initialLat = $initialLat;\n";
        echo "var initialLon = $initialLon;\n";
      ?>
    </script>

    <script src='./Leaflet/leaflet.js'></script>
    <script src='./Leaflet.label/leaflet.label.js'></script>
    <script src='./js/leafletembed_icons.js'></script>
    <script src='./js/leafletembed_functions.js'></script>

  </body>
</html>

<?php
  header('Content-type: text/html; charset="UTF-8"');
?>
