<?php
$script = "/usr/local/bin/eq3.exp ";
$mac_regex = "/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/";

function readExpUsage() {
  global $script;
  //$cmd = $script;
  header("Content-Type: text/plain");
  return shell_exec($script);
}

function readUsage() {
  header("Content-Type: text/html");
  $pre = '<pre>
Usage:  address_of_eq3.php?mac=&lt;MAC&gt;[&amp;sync|&amp;status|&amp;json][&amp;temp=&lt;comf|eco|temp&gt;][&amp;mode=&lt;auto|manual&gt;][&amp;boost=&lt;off&gt;]

sync          - syncs time and prints target temperature and mode
status        - syncs time, prints target temperature, mode and schedule (like sync+schedule commands)
json          - same as status but in json format

temp:
  comf        - sets target temperature to programmed comfort temperature
  eco         - sets target temperature to programmed eco temperature
  temp        - sets target temperature to given value
                temp: 5.0 to 29.5 in intervals of 0.5, e.g. 19.5

mode:
  auto        - sets auto mode and deactivates vacation mode if active
  manual      - sets manual mode and deactivates vacation mode if active

boost         - activates boost mode for 5 minutes
  off         - deactivates boost mode
  
known MAC`s:
  Bathroom:   - 00-1A-22-16-4B-6C
  Bedroom:    - 00-1A-22-16-D1-F5
  Left:       - 00-1A-22-12-62-C7
  Right:      - 00-1A-22-17-04-1A  
</pre>' . "\r\n";
  return $pre;
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
  $cmd = $script . $mac . " comfeco " . $comfort . " " . $eco;
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
} elseif(strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') == 0) {
  $request_parameters = $_GET;
}

if (isset($request_parameters['mac'])) {
  $mac = str_replace("-", ":", $request_parameters['mac']);
  if (preg_match($mac_regex, $mac)) {
  // reads
    // sync
    if (isset($request_parameters['sync'])) {
      $response = readStatus($mac, 'sync');
    // status
    } elseif (isset($request_parameters['status'])) {
      $response = readStatus($mac, 'status');
    // json
    } elseif (isset($request_parameters['json'])) {
      $response = readJsonStatus($mac);
  // sets
    } else {
      // set temp (comf, eco, temp)
      if (isset($request_parameters['temp'])) {
        $temp = $request_parameters['temp'];
        if (strcasecmp($temp, 'comf') == 0) {
          $response = setMode($mac, 'comf');
        } elseif (strcasecmp($temp, 'eco') == 0) {
          $response = setMode($mac, 'eco');
        } else {
          $tempf = floatval($temp);
          if (($tempf > 4.5) && ($tempf < 30)) {
            $response = setTemperature($mac, $tempf);
          } else {
            // wrong temp set
            echo "Temp: '" . $temp . "' is wrong and cannot be set. Must be in range (5.0 - 29.5).";
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
      } else {
        // mac only
        $response = readStatus($mac, 'sync');
        //$response = readJsonStatus($mac);
      }
    }
  } else {
    // wrong mac
    echo $mac . ' has wrong MAC format. Should match RegExp: ' . $mac_regex;
  }
// no mac
} else { 
    $response = readUsage();
   // $response = readExpUsage();
}

echo $response;

?>
