# Surveillance under Surveillance

Surveillance under Surveillance shows you cameras and guards — watching you — almost everywhere. You can see where they are located and, if the information is available, what type they are, the area they observe, or other interesting facts.

Different icons and colors give you a quick overview about the indexed surveillance entries. Click on those icons on the map to get the available information.

Icon | Description
---- | -----------
![Fixed cameras][img_fixedall] | Fixed camera — usually observing a limited area
![Dome cameras][img_domeall] | Dome camera — usually observing a 360° area
![Guards][img_guardall] | Guard — e.g. an employee of a security service
![Automatic Licence Plate Recognition][img_traffic] | ALPR — Automatic Licence Plate Recognition

Color | Description
----- | -----------
![Public surveillance][img_redall] | Red background — observing a public outdoor area, accessable by everyone
![Outdoor surveillance][img_blueall] | Blue background — observing a private outdoor area, accessable only by autherized persons
![Indoor surveillance][img_greenall] | Green background — observing an indoor area
![Fixed cameras, fixme][img_todo_fixedall]<br>![Dome cameras, fixme][img_todo_domeall]<br>![Guards, fixme][img_todo_guardall]<br>![Automatic Licence Plate Recognition, fixme][img_todo_traffic] | Yellow icon — camera or guard marked with a fixme key because it needs further attention

A running instance of this project can be visited at [https://kamba4.crux.uberspace.de](https://kamba4.crux.uberspace.de).

## Installation

If you like to run Surveillance under Surveillance on your own LAMP or LNMP server follow these steps:

1. Get the sources

 - Copy the content of **home/sunders/** to your home directory, e.g. to **~/sunders/**.

 - Copy the content of **www/sunders/** to your server's www directory, e.g. to **/var/www/sunders/**.

2. Set up the datebase

 - Change to the directory **~/sunders/init_cameras/db/**.

 - Open the file **createDB.sql** and enter a password for the new database user **camera**.

 - Create the database **camera** by executing the file **createDB.sql**.

    `mysql -h localhost -u root --password=[mysql root password] < createDb.sql`

3. Initialize the database

  Decide whether you like to start with the surveillance entries of the first planet.osm file from September 12, 2012 or if you like to start with the latest planet.osm file or an extract of an individual country or region.

  **Start with the surveillance entries of the first planet.osm file from September 12, 2012**

  Pros: You don't have to download the latest +50GB planet.osm file to create a sql import file.

  Cons: It could take several days until your database is up-to-date and contains all surveillance entries that have been added between September 12, 2012 and today. Furthermore you start with a data record for the whole planet. Maybe you are only interested in the data of a certain country or region.

  - Execute the file **initializeDB_planet_20120912.sql** for database user **camera**.

    `mysql camera -h localhost -u camera --password=[camera user password] < initializeDB_planet_20120912.sql`

  **Start with the latest planet.osm file or an extract of an individual country or state**

  Pros: You start with the latest data records. Furthermore you can choose what country or region you like to map.

  Cons: If you like to map the whole planet you have to download the latest +50GB planet.osm file. According to your internet connection this could take a while. At last you have to install the command line Openstreetmap data processor [Osmosis](https://wiki.openstreetmap.org/wiki/Osmosis) on your computer.

  - Download the latest osm.bz2 file you like to extract the data from, e.g. from [planet.openstreetmap.org](https://planet.openstreetmap.org/) or from [download.geofabrik.de](http://download.geofabrik.de/). They also offer a MD5 sum to verify the downloaded file.

  - Copy the just downloaded osm.bz2 file to the directory **~/sunders/init_cameras/**.

  - Open the file **~/sunders/init_cameras/createInitialDataFiles.sh** and enter the name of the osm.bz2 file as **XML_FILE**.

    `XML_FILE=[file name].osm.bz2`

  - Execute **createInitialDataFiles.sh** to create the files **surveillance.osm** and **initializeDB.sql**.

  - Move the new files **surveillance.osm** and **initializeDB.sql** to the directory **~/sunders/init_cameras/db/** and change to that directory.

  - Execute the file **initializeDB.sql** for database user **camera**.

    `mysql camera -h localhost -u camera --password=[camera user password] < initializeDB.sql`

4. Update the database

  - Change to the directory **~/sunders/update_cameras/**.

  - Rename the file **config.php.example** to **config.php**.

  - Open the file **config.php** and enter the **MYSQL_PASSWD** of the database user **camera**. Furthermore enter the **REPLICATE_URL** that fits to your project, e.g. from [planet.openstreetmap.org](https://planet.openstreetmap.org/replication/) or from [download.openstreetmap.fr](http://download.openstreetmap.fr/replication/). Here are some examples:

    `https://planet.openstreetmap.org/replication/minute/`
    `http://download.openstreetmap.fr/replication/planet/minute/`
    `http://download.openstreetmap.fr/replication/europe/minute/`
    `http://download.openstreetmap.fr/replication/europe/netherlands/minute/`

  - The update process is based on the sequence number comparison between the current **state.txt** file from the replication server, and the locally stored **lastState.txt**. So if you downloaded a osm.bz2 file, you should modify the **sequenceNumber** in the **lastState.txt** file accordingly.

  - Execute the file **update_camera.sh** to import all surveillance entries that have been added between the creation of the osm.bz2 file and today.

5. Schedule automatic database updates

  - Add this line to your crontab:

    `* * * * * /home/[user]/sunders/update_cameras/update_camera.sh > /dev/null 2>&1`

  - Go to the directory **~/sunders/update_cameras/logs** to check if your schedule works. After one minute there should be a new log file.

  - Go back to your crontab to change the schedule to the values you prefer, e.g. to `23 * * * *` to run the update at every 23rd minute past every hour.

6. Configure the website

  - Change to the directory **/var/www/sunders/**.

  - Rename the file **config.php.example** to **config.php**.

  - Open the file **config.php** and change the definitions of **DEFAULT_ZOOM**, **DEFAULT_LAT**, and **DEFAULT_LON** to set the initial focus of the map to the location you want.

## Surveillance nodes

Surveillance under Surveillance uses data from Openstreetmap contributors that is not visualized on the regular [Openstreetmap](https://www.openstreetmap.org/) site. If you like to add new cameras or guards or if you like to revise existing entries [use your existing OSM account](https://www.openstreetmap.org/login) or [create a new one](https://www.openstreetmap.org/user/new).

These are the most common key/value combinations to describe a surveillance node at Openstreetmap:

### Mandatory to display an icon on the map

Icon | Key | Value
---- | --- | -----
 | [`man_made`](https://wiki.openstreetmap.org/wiki/Key:man_made) | [`surveillance`](https://wiki.openstreetmap.org/wiki/Tag:man_made%3Dsurveillance)
![Public surveillance][img_red] | [`surveillance`](https://wiki.openstreetmap.org/wiki/Key:surveillance) | `public`
![Outdoor surveillance][img_blue] | [`surveillance`](https://wiki.openstreetmap.org/wiki/Key:surveillance) | `outdoor`
![Indoor surveillance][img_green] | [`surveillance`](https://wiki.openstreetmap.org/wiki/Key:surveillance) | `indoor`
![Fixed camera][img_fixed] | [`surveillance:type`](https://wiki.openstreetmap.org/wiki/Key:surveillance:type)<br>`camera:type` | `camera`<br>`fixed` / `panning`
![Dome camera][img_dome] | [`surveillance:type`](https://wiki.openstreetmap.org/wiki/Key:surveillance:type)<br>`camera:type` | `camera`<br>`dome`
![Guard][img_guard] | [`surveillance:type`](https://wiki.openstreetmap.org/wiki/Key:surveillance:type) | `guard`
![Automatic Licence Plate Recognition][img_traffic] | [`surveillance:type`](https://wiki.openstreetmap.org/wiki/Key:surveillance:type) | `ALPR`

### Mandatory to draw camera's field of view

Key | Value
--- | -----
[`camera:direction`](https://wiki.openstreetmap.org/wiki/Proposed_features/Extended_tags_for_Key:Surveillance#Camera) | `0` - `360` (in degrees) or<br>`N` / `NE` / `E` / `SE` / `S` / `SW` / `W` / `NW`
[`camera:angle`](https://wiki.openstreetmap.org/wiki/Proposed_features/Extended_tags_for_Key:Surveillance#Camera) | `15` - `90` (in degrees)<br>from an almost horizontal view to a ground-pointed camera<br>default: 15°
[`height`](https://wiki.openstreetmap.org/wiki/Key:height) | `3` - `12` (in meters)<br>default: 5m

Example 1 | Example 2 | Example 3
--------- | --------- | ---------
![Field of view (direction: 90°, angle: 15°, height: 5m)][img_example1] | ![Field of view (direction: 90°, angle: 15°, height: 10m)][img_example2] | ![Field of view (direction: 90°, angle: 60°, height: 10m)][img_example3]
direction = 90°<br>angle = 15°<br>height = 5m | direction = 90°<br>angle = 15°<br>height = 10m  | direction = 90°<br>angle = 60°<br>height = 10m

### Optional

Key | Value
--- | -----
[`surveillance:zone`](https://wiki.openstreetmap.org/wiki/Key:surveillance:zone) | `bank` / `building` / `parking` / `shop` / `town` / `traffic`
[`camera:mount`](https://wiki.openstreetmap.org/wiki/Proposed_features/Extended_tags_for_Key:Surveillance#Camera) | `ceilling` / `pole` / `wall`
[`operator`](https://wiki.openstreetmap.org/wiki/Key:operator) | organization or person operating the camera
[`name`](https://wiki.openstreetmap.org/wiki/Key:name) | name of the camera
[`ref`](https://wiki.openstreetmap.org/wiki/Key:ref) | reference number of the camera
[`image`](https://wiki.openstreetmap.org/wiki/Key:image) | link to an externally hosted image that depicts the surveillance object

## Credits

* Surveillance under Surveillance is based on the phantastic [osmcamera](https://github.com/khris78/osmcamera) [CC-BY-SA / MIT / GPLv3 / WTFPL] project of [khris78](https://github.com/khris78).
* Furthermore it uses the v0.7.7 code of [Leaflet/Leaflet](https://github.com/Leaflet/Leaflet) [BSD-2-Clause] and the v0.2.1 code of [Leaflet/Leaflet.label](https://github.com/Leaflet/Leaflet.label) [MIT].
* The map itself is the work of millions of [OpenStreetMap](https://www.openstreetmap.org/) [CC BY-SA]  contributors.
* The eye and the locks are icons of [Font Awesome](http://fontawesome.io/) [SIL OFL 1.1 / MIT / CC BY 3.0].
* The font [Grabstein Grotesk](https://fontlibrary.org/de/font/grabstein-grotesk) [OLF] is used for the titles.


[img_red]: ./www/sunders/images/colorRed.png "Public surveillance"
[img_redall]: ./www/sunders/images/colorRedAll.png "Public surveillance"

[img_blue]: ./www/sunders/images/colorBlue.png "Outdoor surveillance"
[img_blueall]: ./www/sunders/images/colorBlueAll.png "Outdoor surveillance"

[img_green]: ./www/sunders/images/colorGreen.png "Indoor surveillance"
[img_greenall]: ./www/sunders/images/colorGreenAll.png "Indoor surveillance"

[img_fixed]: ./www/sunders/images/fixed.png "Fixed camera"
[img_fixedall]: ./www/sunders/images/fixedAll.png "Fixed cameras"
[img_todo_fixedall]: ./www/sunders/images/todo_fixedAll.png "Fixed cameras, fixme"

[img_dome]: ./www/sunders/images/dome.png "Dome camera"
[img_domeall]: ./www/sunders/images/domeAll.png "Dome cameras"
[img_todo_domeall]: ./www/sunders/images/todo_domeAll.png "Dome cameras, fixme"

[img_guard]: ./www/sunders/images/guard.png "Guard"
[img_guardall]: ./www/sunders/images/guardAll.png "Guards"
[img_todo_guardall]: ./www/sunders/images/todo_guardAll.png "Guards, fixme"

[img_traffic]: ./www/sunders/images/traffic.png "Automatic Licence Plate Recognition"
[img_todo_traffic]: ./www/sunders/images/todo_traffic.png "Automatic Licence Plate Recognition, fixme"

[img_example1]: ./www/sunders/images/fixed_z-17_d-90_a-15_h-5.png "Field of view (direction: 90°, angle: 15°, height: 5m)"
[img_example2]: ./www/sunders/images/fixed_z-17_d-90_a-15_h-10.png "Field of view (direction: 90°, angle: 15°, height: 10m)"
[img_example3]: ./www/sunders/images/fixed_z-17_d-90_a-60_h-10.png "Field of view (direction: 90°, angle: 60°, height: 10m)"
