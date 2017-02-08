# eQ-3 radiator thermostat API

## Device Information

**Handle 0x0321 - The product name of the thermostat**
- Encoded in ASCII, you must transform hex to ascii
- Default value: „CC-RT-BLE“
- Get: char-read-hnd 321
- Characteristic value/descriptor: 43 43 2d 52 54 2d 42 4c 45
- Set: n/a

**Handle 0x311 – The vendor of the thermostat**
- Encoded in ASCII, you must transform hex to ascii
- Default value: „eq-3“
- Get: char-read-hnd 311 
- Characteristic value/descriptor: 65 71 2d 33 
- Set: n/a

## Read current status and mode
It is possible to request some status information of the thermostat, i.e.
- current mode
- target temperature
- current level of valve
- and details of vacation mode

Request:
```
char-write-req 0411 03
               |    + request status command
               + request via handle 411
```

Data will be returned via notification handle
```
Notification handle = 0x0421 value: 02 01 00 00 04 2a
```

### Modes (Byte 3) ###
The thermostat has the following modes which can be active at one and the same time:
- "auto"        - Bit 0 is not set (mask 0x00)
- "manual"      - Bit 0 is set (mask 0x01)
- "vacation"    - Bit 1 is set (mask 0x02)
- "boost"       - Bit 2 is set (mask 0x04)
- "dst"         - Bit 3 is set (mask 0x08)
- "open window" - Bit 4 is set (mask 0x10)
- "locked"      - Bit 5 is set (mask 0x20)
- "unknown"     - Bit 6 is set (mask 0x40)
- "low battery" - Bit 7 is set (mask 0x80)

### Valve (Byte 4) ###
Byte 4 represents the percentage value of the valve. However, I haven't seen a value more than 80% so far even if boost mode is running.

### Current target temperature (Byte 6) ###
Byte 6 represents the target temperature. It has to be calculated.

```
temp = dec(value of byte 6) / 2.0
```  

### Vacation data ###
The bytes 7 to 9 are only returned on case that vacation mode is active. 
- Byte 8: Vacation year (yy) in hex
- Byte 9: Vacation month (mm) in hex
- Byte 7: Vacation day in month (dd) in hex
- Byte 10: Vacation time in 30 minutes steps in hex. Time can be calculated like this:
```
hh = int(dec(value of byte 10) / 2)
mm = dec(value of byte 10) modulo 2 * 30
```

**Example 1 - auto mode**
```
Request:
char-write-req 0411 03
               |    + request status command
               + request via handle 411

Notification handle = 0x0421 value: 02 01 00 00 04 2a
                      |             |  |  |  |  |  + Byte 6: Current temperature in 0.5°C intervals in hex, here 21°C
                      |             |  |  |  |  +--- Byte: 5: Always "04" 
                      |             |  |  |  +------ Byte 4: Current level of valve in percent in hex
                      |             |  |  +--------- Byte 3: Current mode, here "auto", see modes
                      |             |  +------------ Byte 2: Always "01"
                      |             +--------------- Byte 1: Always "02"
                      + response via notification handle on 0x421
```

Human readable status:
```
Status (0x0411 03):		02 01 00 00 04 2a 
Temperature:			21.0°C
Valve:				0%
Mode:				auto 
Vacation mode:			off
```

**Example 2 - active vacation mode until 2017-02-29**
```
char-write-req 0411 03
               |    + request status
               + request via handle 411

Notification handle = 0x0421 value: 02 01 02 00 04 26 1c 11 03 02 
                      |             |  |  |  |  |  |  |  |  |  + Byte 10: Vacation month, here 02 = February
                      |             |  |  |  |  |  |  |  |  +--- Byte 9: Vacation time in 30 minutes steps in hex, here 01:30
                      |             |  |  |  |  |  |  |  +------ Byte 8: Vacation year (yy) in hex, here 2017
                      |             |  |  |  |  |  |  +--------- Byte 7: Vacation day in month (dd) in hex, here 29
                      |             |  |  |  |  |  +------------ Byte 6: Current temperature in 0.5°C intervals in hex, here 19°C
                      |             |  |  |  |  +--------------- Byte 5: Always "04" 
                      |             |  |  |  +------------------ Byte 4: Current level of valve in percent in hex
                      |             |  |  +--------------------- Byte 3: Current mode, here "vacation mode", see modes
                      |             |  +------------------------ Byte 2: Always "01"
                      |             +--------------------------- Byte 1: Always "02"
                      + response via notification handle on 0x421
```

## Set date and time
Since the thermostat has timers it has an internal clock and calender of course. Unfortunately it is not possible to read data from it. But it can be set. 
Note: It does not seem to be possible to set the "daylight summertime" (dst) via bluetooth. 

Request:
```
char-write-req 0411 03110208151f05
               |    | + Byte 2 to 7: yy-mm-day hh-MM-ss in hex
               |    +-- Byte 1: request command "03"
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
notification: 0x0421 02 01 00 00 04 2a 
```

## Set mode
You can set the operation mode of the thermostat via bluetooth.

### Set auto mode
In auto mode the thermostat follows the temperatures that are configured in timers for each week day. 

Request:
```
char-write-req 0411 4000
               |    | + Byte 2: Set "00" for mode "auto"
               |    +-- Byte 1: "40" indicates request in order to change mode
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 00 00 04 2a
                                          + mode, now "auto"
```

### Set manual mode
In manual mode the thermostat does not follow the configured timers. The target temperature that is set at this moment won't change in manual mode. 

Request:
```
char-write-req 0411 4000
               |    | + Byte 2: Set "01" for mode "manual"
               |    +-- Byte 1: "40" indicates request in order to change mode
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 01 00 04 2a
                                          + mode, now "manual"
```

### Set vacation mode
In vacation mode the target temperatures taken from auto or manual mode will be overruled for a certain period.

Request:
```
char-write-req 0411 40a31f112b03
               |    | | | | | + Byte 6: Until date, month (mm) in hex
               |    | | | | +-- Byte 5: Encoded until time hh:mm in 30 minutes steps in hex, here 21:30
               |    | | | +---- Byte 4: Until date, year (yy) in hex
               |    | | +------ Byte 3: Until date, day in month (dd) in hex
               |    | +-------- Byte 2: Target temperature calculated as follows: temperature * 2 + 128 in hex, here 17.5°C
               |    +---------- Byte 1: "40" indicates request in order to change mode
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen in status for vacation mode:
```
Notification handle = 0x0421 value: 02 01 02 00 04 23 1f 11 2b 03 
                                          + mode, now "vacation"
```

## Target temperatures
The desired temperature can be set via several ways, e.g. by switching to a predefined temperature or by selecting a temperature directly. 
In addition you can activate the boost mode.

### Switch to comfort temperature
Switch to the comfort temperature by the following request:

Request:
```
char-write-req 0411 43
               |    + Byte 1: "43" indicates request in order to change to comfort temperature
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 00 00 04 2c
                                          + mode, still "auto"!
```

### Switch to eco temperature
Switch to the eco temperature by the following request:

Request:
```
char-write-req 0411 43
               |    + Byte 1: "44" indicates request in order to change to eco temperature
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 00 26 04 24
                                          + mode, still "auto"!
```

### Set target temperature
Switch to any temperature by the following request:

Request
```
char-write-req 0411 412d
               |    | +---------- Byte 2: Target temperature calculated as follows: temperature * 2 in hex, here 22.5°C
               |    +------------ Byte 1: "41" indicates request in order to change to a given temperature
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 00 00 04 2d
                                          + mode, still "auto"!
```

### Set thermostat on
Switch the thermostat on by setting temperature to 30°C. 

**Note** I don't know if this stops timers. Probably yes.

Request
```
char-write-req 0411 413c
               |    | +---------- Byte 2: Target temperature calculated as follows: temperature * 2 in hex, for "on" mode set 30.0°C
               |    +------------ Byte 1: "41" indicates request in order to change to a given temperature
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 00 37 04 3c
                                          + mode, still "auto" althougt I expect that timers are off know!
```

### Set thermostat off

Switch the thermostat off by setting temperature to 4.5°C. 

**Note** I don't know if this stops timers. Probably yes.

Request
```
char-write-req 0411 4109
               |    | +---------- Byte 2: Target temperature calculated as follows: temperature * 2 in hex, for "on" mode set 4.5°C
               |    +------------ Byte 1: "41" indicates request in order to change to a given temperature
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 00 37 04 09
                                          + mode, still "auto" althougt I expect that timers are off know!
```

### Activate boost mode
You can turn on the boost mode by the following command. Boost mode will be activated for 5 minutes until it stops. 
Unfortunately it does not seem to be possible to read the ETA before boost mode turns off automatically. 

Request
```
char-write-req 0411 45ff
               |    | +---------- Byte 2: Any value greater or equal than 0 starts boost mode. Values don't seem to make a difference.
               |    +------------ Byte 1: "45" indicates request in order to start boost mode
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 05 50 04 2c 
                                          + mode, now "boost" in combination with "manual"
```

### Stop boost mode
Turn off the boost mode by the following command.

Request
```
char-write-req 0411 4500
               |    | +---------- Byte 2: "00" stops boost mode. All values greater than 0 start boost mode.
               |    +------------ Byte 1: "45" indicates request in order to start boost mode
               + request via handle 411
```

The thermostat returns status via notification handle simular to what we have seen before:
```
Notification handle = 0x0421 value: 02 01 01 50 04 2c 
                                          + mode, now back to "normal"
```

## Timers
The thermostat has at least one time plan per day. In other forums you can read that there are even more timer programs possible but I haven't double-checked it. 
From my point of view it is good enough to have the possibility to have a schedule plan for each day of the week. 

Timers can be read and written via bluetooth. 

### Read timer for a day

### Set timer for a day

## Configuration

### Configure comfort and eco temperature

### Configure window open mode

### Configure offset temperature

## Others

### Lock thermostat

### Unlock thermostat

### Read latest command request

### Clear latest command request

### Factory reset 
