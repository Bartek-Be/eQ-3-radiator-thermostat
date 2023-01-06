<?php
$script = "/usr/local/bin/eq3.exp ";
$mac_regex = "/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/";

//$modes = array("ON" => "auto", "OFF" => "manual");

function readUsage() {
  global $script;
  //$cmd = $script;
  header("Content-Type: text/plain");
  
  return shell_exec($script);
}

function readStatus($mac, $mode) {
  global $script;
  header("Content-Type: text/plain");
  $cmd = $script . $mac . " " . $mode;
  echo "Status (" . $mode . ") for " . $mac . "\r\n";

  return shell_exec($cmd);
}

function readJsonStatus($mac) {
  global $script;
  header('Content-Type: application/json');
  $cmd = $script . $mac . " json";

  return shell_exec($cmd);
}

function setMode($mac, $mode) {
  global $script;
  header("Content-Type: text/plain");
  echo "Response:\r\n";
  $cmd = $script . $mac . " " . $mode;
  return shell_exec($cmd);
}

function setTemperature($mac, $temp) {
  global $script;
  header("Content-Type: text/plain");
  echo "Response:\r\n";
  $cmd = $script . $mac . " temp " . $temp;
  return shell_exec($cmd);
}

function setComforteco($mac, $comfort, $eco) {
  global $script;
  header("Content-Type: text/plain");
  echo "Response:\r\n";
  $cmd = $script . $mac . " comforteco " . $comfort . " " . $eco;
  return shell_exec($cmd);
}

function setBoost($mac, $off) {
  global $script;
  header("Content-Type: text/plain");
  echo "Response:\r\n";
  $cmd = $script . $mac . " boost";
  if ($off) {
    $cmd .= " off";
  }
  return shell_exec($cmd);
}

if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0){
  $request_parameters = $_POST;
} else if(strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') == 0) {
  $request_parameters = $_GET;
}

//(is_null(
if (isset($request_parameters['mac'])) {
  $mac = str_replace("-", ":", $request_parameters['mac']);
  if (preg_match($mac_regex, $mac)) {
  // reads
    //sync
    if (isset($request_parameters['sync'])) {
      $response = readStatus($mac, 'sync');
    //status
    } elseif (isset($request_parameters['status'])) {
      $response = readStatus($mac, 'status');
    //json
    } elseif (isset($request_parameters['json'])) {
      $response = readJsonStatus($mac);
  // sets
    } else {
      // set temp (comfort, eco, temp)
      if (isset($request_parameters['temp'])) {
        $temp = $request_parameters['temp'];
        if (strcasecmp($temp, "comf") == 0) {
          $response = setMode($mac, 'comf');
        } elseif (strcasecmp($temp, "eco") == 0) {
          $response = setMode($mac, 'eco');
        } else {
          $temp = floatval(str_replace(",", ".", $request_parameters['temp']));
          if ($temp > 4) {
            $response = setTemperature($mac, $temp);
          } else {
            // wrong temp set
            echo $temp . ' is wrong and cannot be set.';
          }
        }
      // set mode (auto, manual)
      } elseif (isset($request_parameters['mode'])) {
        $mode = $request_parameters['mode'];
        $response = setMode($mac, $mode);
      //set boost
      } elseif (isset($request_parameters['boost'])) {
        $boost = $request_parameters['boost'];
        if (strcasecmp($boost, "off") == 0) {
          $response = setBoost($mac, 1);
        } else {
          $response = setBoost($mac, 0);
        }
      }
      //$response = readJsonStatus($mac);
      //$response = readStatus($mac, 'sync');
    }
  } else {
  // wrong mac
    echo $mac . ' has wrong MAC format. Should match RegExp: ' . $mac_regex;
  }
} else {
    echo "\r\n";
    echo 'Usage: ip_address?mac=<MAC>&temp=<comf|eco|temp>&mode=<auto|manual>&boost=<off>
      
      temp:
        comf         - sets target temperature to programmed comfort temperature
        eco          - sets target temperature to programmed eco temperature
        temp         - sets target temperature to given value
                       temp: 5.0 to 29.5 in intervals of 0.5°C, e.g. 19.5

      mode:   
        auto        - sets auto mode and deactivates vacation mode if active
        manual      - sets manual mode and deactivates vacation mode if active
      
      boost         - activates boost mode for 5 minutes
        off         - deactivates boost mode
            
      known MAC`s:

    echo "\r\n\n";
      
    echo 'eq3.exp:';
    $response = readUsage();
}

echo $response;

?>
