# diffreader
OpenStreetMap diff reader and edit tracking/rankings tool

Drives displays such as [OpenStreetMap School Edit Tracker](http://harrywood.dev.openstreetmap.org/diffreader/schools/)

It comes in two parts: the diff reader script which takes in OpenStreetMap [http://wiki.openstreetmap.org/wiki/Planet.osm/diffs replication diff files] to process the edits as they come in every minute, and a little PHP website for displaying the edits and querying them in various ways from an SQLite DB.
