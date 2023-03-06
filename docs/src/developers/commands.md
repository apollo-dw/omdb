# Commands

There's a couple custom commands implemented:

## Retrieve Beatmaps

`php artisan omdb:retrieve_beatmaps`

This command starts pulling new beatmaps from the OSU API.

TODO: Run this command on an interval.

## Import dump

`php artisan omdb:import_dump`

This is the command to import the old database format into the Laravel one. It
should be invoked like this:

    php artisan omdb:import_dump ../dumps/dump.db --cache=../dumps/api.db

The cache part is optional, but using it means all OSU API requests are cached
and can be reused on subsequent calls. It can also be transferred to a
production host to speed up importing so the OSU API doesn't constantly need
to be hit.

The dump.db part needs to be stitched together from the old dump. I used this
[random project][mysql2sqlite] to help me convert MySQL dumps into an SQLite
dump suitable for this.

[mysql2sqlite]: https://github.com/dumblob/mysql2sqlite

TODO: Investigate if we can just migrate directly from the MySQL tables

## Update charts

`php artisan omdb:chart_update`

This requires the unreleased `ChartUpdate.php` script. It calculates all the
weights and rankings and updates the database accordingly.

TODO: Run this command on an interval.

TODO: Find a way to have this checked into version control in a different repo
