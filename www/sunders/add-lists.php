<?php
  include './decode-json.php';

  // Convert the content of the symbology JSON file to HTML.
  function addListSymbology($jsonPath) {
    $decodedJSON = getDecodedJSON($jsonPath);

    echo   "<div class='slider-item slider-list'>\n";

    // Loop over the lists to display.
    foreach($decodedJSON as $listObject) {
      echo   "<div class='slider-list-title'>".htmlentities($listObject->{'listTitle'})."</div>\n";

      // Loop over the entries of the current list.
      foreach($listObject->{'listEntries'} as $listEntryObject) {
        echo "<div class='slider-list-entry'>\n
                <div class='w-45'>\n";

        // Loop over the icons of the current list entry.
        foreach($listEntryObject->{'icons'} as $icon) {
          echo   "<img src='./images/".$icon->{'src'}."' alt='".htmlentities($icon->{'alt'})."'>\n";
        }
        echo   "</div>\n
                <div class='pl-20 w-315'>".htmlentities($listEntryObject->{'description'})."</div>\n
              </div>\n";
      }
    }
    echo   "</div>\n";
  }

  // Convert the content of the manual JSON file to HTML.
  function addListManual($jsonPath) {
    $decodedJSON = getDecodedJSON($jsonPath);

    echo   "<div class='slider-item slider-list text-small'>\n";

    // Loop over the lists to display.
    foreach($decodedJSON as $listObject) {
      echo   "<div class='slider-list-title'>".htmlentities($listObject->{'listTitle'})."</div>\n";

      // Loop over the entries of the current list.
      foreach($listObject->{'listEntries'} as $listEntryObject) {
        $keysAsHTMLArray   = array();
        $valuesAsHTMLArray = array();

        // Loop over the keys of the current list entry.
        foreach($listEntryObject->{'keys'} as $key) {

          if(is_null($key->{'href'})){
            array_push($keysAsHTMLArray, htmlentities($key->{'key'}));
          } else {
            array_push($keysAsHTMLArray, "<a href='".$key->{'href'}."' target='_blank'>".htmlentities($key->{'key'})."</a>");
          }
        }

        // Loop over the values of the current list entry.
        foreach($listEntryObject->{'values'} as $value) {

          if(is_null($value->{'href'})){
            array_push($valuesAsHTMLArray, htmlentities($value->{'value'}));
          } else {
            array_push($valuesAsHTMLArray, "<a href='".$value->{'href'}."' target='_blank'>".htmlentities($value->{'value'})."</a>");
          }
        }

        echo "<div class='slider-list-entry'>\n
                <div class='w-100'>\n";
                  echo implode("<br>", $keysAsHTMLArray);
        echo   "</div>\n";

        // Some lists have an icon column.
        if($listObject->{'isListWithIcons'}) {
          echo "<div class='pl-20 w-240'>\n"
                  .implode("<br>", $valuesAsHTMLArray)."\n
                </div>\n
                <div class='w-20'>\n";
          $iconObject  = $listEntryObject->{'icon'};
          if(!is_null($iconObject)){
            echo "<img src='./images/".$iconObject->{'src'}."' alt='".htmlentities($iconObject->{'alt'})."'>\n";
          }
        } else {
          echo "<div class='pl-20 w-260'>\n"
                  .implode("<br>", $valuesAsHTMLArray)."\n";
        }

        echo   "</div>\n
              </div>\n";
      }

      // Some lists end with examples, i.e. 3 images with descriptions.
      if(!is_null($listObject->{'examples'})){
        $examplesObject = $listObject->{'examples'};

        echo "<div class='slider-list-entry'>\n
                <div class='w-100'>\n
                  <br>Examples:\n
                </div>\n
                <div class='pl-20 w-260'>\n
                </div>\n
              </div>\n
              <div class='slider-list-entry'>\n
                <div class='fieldofview'>\n";

        foreach($examplesObject->{'images'} as $image) {
          echo   "<div class='fov-image'>\n
                    <img src='./images/".$image->{'src'}."' alt='".htmlentities($image->{'alt'})."'>\n
                  </div>\n";
        }

        echo   "</div>\n
              </div>\n
              <div class='slider-list-entry'>\n
                <div class='fieldofview'>\n";

        foreach($examplesObject->{'descriptions'} as $description) {
          $linesAsHTMLArray = array();

          foreach($description->{'lines'} as $line) {
            array_push($linesAsHTMLArray, htmlentities($line));
          }

          echo   "<div class='w-100'>\n"
                    .implode("<br>", $linesAsHTMLArray)."\n
                  </div>\n";
        }

        echo   "</div>\n
              </div>\n";
      }

    }
    echo   "</div>\n";
  }

  // Convert the content of the links JSON file to HTML.
  function addListLinks($jsonPath) {
    $decodedJSON = getDecodedJSON($jsonPath);

    echo   "<div class='slider-item slider-list'>\n";

    // Loop over the lists to display.
    foreach($decodedJSON as $listObject) {
      echo   "<div class='slider-list-title'>".htmlentities($listObject->{'listTitle'})."</div>\n";

      // Loop over the entries of the current list.
      foreach($listObject->{'listEntries'} as $listEntryObject) {
        echo "<div class='slider-list-entry'>\n
                <div class='w-20'>\n";

        // Choose lock icon according to https or http connection.
        if(substr($listEntryObject->{'href'}, 0, 5) == "https") {
          echo   "<img src='./images/lock-secure.png' alt='Secure Connection'>\n";
        } else {
          echo   "<img src='./images/lock-insecure.png' alt='Insecure Connection!'>\n";
        }
        echo   "</div>\n
                <div class='pl-20 w-340'>\n
                  [ ".htmlentities($listEntryObject->{'sourceText'})." ]<br><a href='".$listEntryObject->{'href'}."' target='_blank'>".htmlentities($listEntryObject->{'linkText'})."</a>\n
                </div>\n
              </div>\n";
      }
    }
    echo   "</div>\n";
  }
?>
