<?php
  // Convert the content of a JSON file to a PHP array.
  function getDecodedJSON($jsonPath) {
    $handle = fopen($jsonPath, 'r');
    $jsonContent = fread($handle, filesize($jsonPath));
    fclose($handle);

    return json_decode($jsonContent);
  }
?>
