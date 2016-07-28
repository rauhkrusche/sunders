<?php

  include './config.php';

  define(MAX_POINTS_FOR_QUICKHULL, 3000);

  class OsmPoint {
    var $id;
    var $lat;
    var $lon;

    function __construct($valId, $valLat, $valLon) {
      $this->id = $valId;
      $this->lat = $valLat;
      $this->lon = $valLon;
    }
  }

  function mergeNeighbor(&$clusterGrid, $posCell, $pos) {
    global $divDiag2;

    if (!array_key_exists($pos, $clusterGrid)) {
      /* the neighbor is empty */
      return;
    }

    $neighbor = $clusterGrid[$pos];

    if ($neighbor['count'] == 0) {
      /* The neighbor is merged to an other cell, yet */
      return;
    }

    $cell = $clusterGrid[$posCell];

    /* Calculate the (square of) distance from the cell to its neighbor */
    $lonDist = ($cell['longitude']/$cell['count']) - ($neighbor['longitude']/$neighbor['count']);
    $latDist = ($cell['latitude'] /$cell['count']) - ($neighbor['latitude'] /$neighbor['count']);
    $dist = ($lonDist * $lonDist) + ($latDist * $latDist);

    if ($dist < $divDiag2 / 10) {
      $count = $cell['count'] + $neighbor['count'];
      $clusterGrid[$posCell]['latitude'] = $cell['latitude'] + $neighbor['latitude'];
      $clusterGrid[$posCell]['longitude'] = $cell['longitude'] + $neighbor['longitude'];
      $clusterGrid[$posCell]['count'] = $count;
      $clusterGrid[$posCell]['points'] = array_merge($cell['points'], $neighbor['points']);
      /* Invalidate the merged neighbor */
      $clusterGrid[$pos]['count'] = 0;
    }
  }

  function mergeNeighborhood(&$clusterGrid, $pos) {
    global $divWCount, $divHCount;

    if ($clusterGrid[$pos]['count'] == 0) {
      /* cell merged yet */
      return;
    }

    $latLon = explode(',', $clusterGrid[$pos]['grid'] );
    if ($latLon[0] > 0 && $latLon[1] < $divHCount - 1) {
      if ($latLon[0] > 0) {
        $posNeigh = ($latLon[0] - 1) . ',' . ($latLon[1] + 1);
        mergeNeighbor($clusterGrid, $pos, $posNeigh);
      }

      $posNeigh = $latLon[0] . ',' . ($latLon[1] + 1);
      mergeNeighbor($clusterGrid, $pos, $posNeigh);

      if ($latLon[0] < $divWCount - 1) {
        $posNeigh = ($latLon[0] + 1) . ',' . ($latLon[1] + 1);
        mergeNeighbor($clusterGrid, $pos, $posNeigh);
      }
    }

    if ($latLon[0] < $divWCount - 1) {
      $posNeigh = ($latLon[0] + 1) . ',' . $latLon[1];
      mergeNeighbor($clusterGrid, $pos, $posNeigh);
    }
  }

  /* Recursive calculation of the quick hull algo
     $isBottom : 1 if bottom point is searched, -1 if top point is searched */
  function quickHullCalc(&$pointList, $count, $minPoint, $maxPoint, $isBottom) {

    $msg = "Quick count=".$count.", min=(".$minPoint->id.",".$minPoint->lat.",".$minPoint->lon."), max=(".$minPoint->id.",".$minPoint->lat.",".$minPoint->lon."), isBottom=".$isBottom."\n";

    $farthestPoint = null;
    $farthestDist = 0;
    $outsideList = array();
    $outsideListCount = 0;

    if ($maxPoint->lon != $minPoint->lon) {
      /* Get the line equation as y = mx + p */
      $m = ($maxPoint->lat - $minPoint->lat) / ($maxPoint->lon - $minPoint->lon);
      $p = ($maxPoint->lat * $minPoint->lon - $minPoint->lat * $maxPoint->lon) / ($minPoint->lon - $maxPoint->lon);
    } else {
      /* The line equation is y = p */
      $m = null;
      $p = $minPoint->lat;
      $coef = (($minPoint->lon > $maxPoint->lon) ? 1 : -1);
    }

    /* For each point, check whether :
      - it is on the right side of the line
      - it is the farthest from the line
    */
    foreach ($pointList as $point) {
      if (isset($m)) {
        $dist = $isBottom * ($m * $point->lon - $point->lat + $p);
      } else {
        $dist = $coef * ($point->lon - $p);
      }
      if ($dist > 0
        && $point->id != $minPoint->id
        && $point->id != $maxPoint->id) {

        array_push($outsideList, $point);
        $outsideListCount++;

        if ($dist > $farthestDist) {
          $farthestPoint = $point;
          $farthestDist = $dist;
        }
      }
    }

    if ($outsideListCount == 0) {
      return array($minPoint);
    } else if ($outsideListCount == 1) {
      return array($minPoint, $outsideList[0]);
    } else {
      return array_merge(
        quickHullCalc($outsideList, $outsideListCount, $minPoint, $farthestPoint, $isBottom),
        quickHullCalc($outsideList, $outsideListCount, $farthestPoint, $maxPoint, $isBottom));
    }
  }

  /* This function receives a list of points [lat, lon] and returns a list of points [lat, lon]
     representing the minimal convex polygon containing the points */
  function quickHull(&$pointList, $count) {

    $msg= "Quick count=".$count."\n";

    if ($count == 0) {
      return array();
    } else if ($count == 1) {
      return array($pointList[0]);
    } else if ($count == 2) {
      return array($pointList[0], $pointList[1]);
    }

    /* retrieves the min and max points on the x axe */
    $minPoint = $pointList[0];
    $maxPoint = $pointList[0];

    foreach ($pointList as $point) {
      if ($point->lon < $minPoint->lon || ($point->lon == $minPoint->lon && $point->lat < $minPoint->lat)) {
        $minPoint = $point;
      }
      if ($point->lon > $maxPoint->lon || ($point->lon == $maxPoint->lon && $point->lat > $maxPoint->lat)) {
        $maxPoint = $point;
      }
    }

    $bottomPoints = quickHullCalc($pointList, $count, $minPoint, $maxPoint, 1);
    $topPoints    = quickHullCalc($pointList, $count, $maxPoint, $minPoint, -1);
    return array_merge($bottomPoints, $topPoints);
  }




  $GRID_MAX_ZOOM   = 16;
  $GRID_CELL_PIXEL = 90;

  /* Check if parameters are not empty */
  if (   !array_key_exists('bbox', $_GET)
      || !array_key_exists('zoom', $_GET)
      || !array_key_exists('width', $_GET)
      || !array_key_exists('height', $_GET)) {
    header('Content-type: application/json');
    $result = '{"error":"bbox, zoom, width and height parameters are required. '
                .(array_key_exists('bbox', $_GET) ? '' : 'bbox is empty. ')
                .(array_key_exists('zoom', $_GET) ? '' : 'zoom is empty. ')
                .(array_key_exists('width', $_GET) ? '' : 'width is empty. ')
                .(array_key_exists('height', $_GET) ? '' : 'height is empty. ')
                .'"}';
    echo $result;
    exit;
  }

  /* Check zoom */
  $zoom = $_GET['zoom'];

  if (   !is_numeric($zoom)
      || intval($zoom) < 1
      || intval($zoom) > 18) {
    header('Content-type: application/json');
    $result = '{"error":"unexpected zoom value : '
                .htmlentities($zoom)
                .'"}';
    echo $result;
    exit;
  }

  /* Check bounds box --> bbox=boundsSW.lng,boundsSW.lat,boundsNE.lng,boundsNE.lat */
  $bbox = explode(',', $_GET['bbox']);

  if (   !is_numeric($bbox[0]) || $bbox[0] > 180                        // 0 = SW.lng
      || !is_numeric($bbox[2]) || $bbox[2] < -180                       // 2 = NE.lng
      || !is_numeric($bbox[1]) || $bbox[1] < -90  || $bbox[1] > 90      // 1 = SW.lat
      || !is_numeric($bbox[3]) || $bbox[3] < -90  || $bbox[3] > 90) {   // 3 = NE.lat
    header('Content-type: application/json');
    $result = '{"error":"unexpected bbox longitude and latitude values : '
                .'lon ['.htmlentities($bbox[0]).', '.htmlentities($bbox[2]).'], '
                .'lat ['.htmlentities($bbox[1]).', '.htmlentities($bbox[3]).']'
                .'"}';
    echo $result;
    exit;
  }

  if ($bbox[0] >= $bbox[2]) {   // 0 = SW.lng | 2 = NE.lng
    header('Content-type: application/json');
    $result = '{"error":"min longitude greater than max longitude : '
              .'lon ['.htmlentities($bbox[0]).', '.htmlentities($bbox[2]).']'
              .'"}';
    echo $result;
    exit;
  }

  if ($bbox[1] >= $bbox[3]) {   // 1 = SW.lat | 3 = NE.lat
    header('Content-type: application/json');
    $result = '{"error":"min latitude greater than max latitude : '
              .'lat ['.htmlentities($bbox[1]).', '.htmlentities($bbox[3]).']'
              .'"}';
    echo $result;
    exit;
  }

  $lonMin = $bbox[0];   // 0 = SW.lng
  $lonMax = $bbox[2];   // 2 = NE.lng
  $latMin = $bbox[1];   // 1 = SW.lat
  $latMax = $bbox[3];   // 3 = NE.lat

  $pixelWidth  = (int) $_GET['width'];
  $pixelHeight = (int) $_GET['height'];

  if ($pixelWidth == 0 || $pixelHeight == 0) {
    header('Content-type: application/json');
    $result = '{"error":"width or height is null : '
              .htmlentities($pixelWidth).'x'.htmlentities($pixelHeight)
              .'"}';
    echo $result;
    exit;
  }

  $lonWidth  = $lonMax - $lonMin;   // NE.lng - SW.lng
  $latHeight = $latMax - $latMin;   // NE.lat - SW.lat

  $divWCount = ((int) ($pixelWidth  / $GRID_CELL_PIXEL)) + 1;
  $divHCount = ((int) ($pixelHeight / $GRID_CELL_PIXEL)) + 1;

  $divWidth  = $lonWidth  / $divWCount;
  $divHeight = $latHeight / $divHCount;
  $divDiag2  = ($divWidth * $divWidth) + ($divHeight * $divHeight);

  /* Connect to database */
  $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
  if($mysqli->connect_errno) {
    header('Content-type: application/json');
    $result = '{"error":"error while connecting to db : ' . $mysqli->error . '"}';
    echo $result;
    exit;
  }

  $rqtLonMin = $lonMin;   // SW.lng
  $rqtLonMax = $lonMax;   // NE.lng

  /* Indicates whether the map displays the -180/180° longitude */
  if ($rqtLonMax - $rqtLonMin > 360) {
    $rqtLonMin = -180;
    $rqtLonMax = 180;
  }

  /* Select the nodes to be returned, and cluster them on a grid if necessary */
  if ($rqtLonMin >= -180 && $lonMax <= 180) {
    $sql="SELECT  id, latitude, longitude
      FROM  position
      WHERE  latitude  between ? and ?
      AND  longitude between ? and ?";
  } else {
    $sql="SELECT  id, latitude, longitude
      FROM  position
      WHERE  latitude  between ? and ?
      AND  (longitude between ? and 1800000000 OR longitude between -1800000000 and ?)";

    while ($rqtLonMin < -180) {
      $rqtLonMin += 360;
    }
    while ($rqtLonMax > 180) {
      $rqtLonMax -= 360;
    }
  }

  $rqtLonMin = bcmul($rqtLonMin, 10000000, 0);    // SW.lng
  $rqtLonMax = bcmul($rqtLonMax, 10000000, 0);    // NE.lng
  $rqtLatMin = bcmul($latMin, 10000000, 0);       // SW.lat
  $rqtLatMax = bcmul($latMax, 10000000, 0);       // NE.lat

  $resArray = array();
  $nbFetch = 0;

  if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param('iiii', $rqtLatMin, $rqtLatMax, $rqtLonMin, $rqtLonMax);    // SW.lat, NE.lat, SW.lng, NE.lng

    $stmt->execute();

    $stmt->bind_result($id, $latitude, $longitude);

    while ($stmt->fetch()) {
      $nbFetch++;

      $latitude  = bcdiv($latitude, 10000000, 7);
      $longitude = bcdiv($longitude, 10000000, 7);

      while ($longitude < $lonMin) {    // $lonMin = SW.lng
        $longitude += 360;
      }
      while ($longitude > $lonMax) {    // $lonMax = NE.lng
        $longitude -= 360;
      }

      /* Initialize the current point */
      if ($zoom >= $GRID_MAX_ZOOM) {
        array_push($resArray, array(
          'points' => array(new OsmPoint($id, $latitude, $longitude)),
          'latitude' => $latitude,
          'longitude' => $longitude,
          'count' => 1 ));
      } else {
        $posLat = (int) (($latitude - $latMin)  / $divHeight);    // $latMin = SW.lat
        $posLon = (int) (($longitude - $lonMin) / $divWidth);     // $lonMin = SW.lng
        $pos = $posLon.','.$posLat;

        if (!array_key_exists($pos, $resArray)) {
          $resArray[$pos] = array(
            'points' => array(),
            'latitude' => 0,
            'longitude' => 0,
            'count' => 0,
            'grid' => $pos);
        }

        $elt = new OsmPoint($id, $latitude, $longitude);
        array_push($resArray[$pos]['points'], $elt);

        /* Increment the number of points */
        $resArray[$pos]['count']++;
        $resArray[$pos]['latitude']  += $latitude;
        $resArray[$pos]['longitude'] += $longitude;
      }
    }

    $stmt->close();
  } else {
    $mysqli->close();
    header('Content-type: application/json');
    $result = '{"error":"Error while request : ' . $mysqli->error . '"}';
    echo $result;
    exit;
  }

  /* Unify some clusters if nodes center are near */
  $pointsCount = 0;
  if ($zoom < $GRID_MAX_ZOOM) {
    foreach($resArray as $val) {
      mergeNeighborhood($resArray, $val['grid']);
      $pointsCount += $val['count'];
    }
  }

  $result='[';
  $separator='';

  /* Writing selected points */
  $sql="SELECT  k, v
    FROM  tag
    WHERE  id = ?
    AND k NOT IN ('lat', 'lon')";

  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param("d", $id);
  $stmt->bind_result($k, $v);

  /* Writing grouped items or not */
  foreach($resArray as $val) {
    if ($val['count'] > 0) {
      $result = $result.$separator
                .'{"lat":"'.($val['latitude']  / $val['count']).'"'
                .',"lon":"'.($val['longitude'] / $val['count']).'"';

      if ($val['count'] == 1) {
        $id = $val['points'][0]->id;
        $result = $result.',"id":"'.$id.'"';

        $stmt->execute();

        while($stmt->fetch()) {
          $result = $result.',"'.htmlentities($k).'":"'.htmlentities($v, ENT_COMPAT, 'UTF-8').'"';
        }
      } else {
        $result = $result
                  .',"count":"'.$val['count'].'"'
                  .',"multi":"yes"'
                  .',"poly":[';

        if ($pointsCount < MAX_POINTS_FOR_QUICKHULL) {
          $convexPoly = quickHull($val['points'], $val['count']);
          $sepPoly = '';
          foreach($convexPoly as $point) {
            $result = $result.$sepPoly.'{'
                      .'"lat":"'.$point->lat.'"'
                      .',"id":"'.$point->id.'"'
                      .',"lon":"'.$point->lon.'"}';
            $sepPoly=',';
          }
        }
        $result=$result.']';
      }

      $result = $result.'}';
      $separator=',';
    }
  }

  $stmt->close();

  $result = $result . ']';

  $mysqli->close();

  header('Content-type: application/json; Charset : utf-8');
  echo $result;
?>
