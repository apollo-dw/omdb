# Developer Documentation

## Architecture

This application is a typical [Laravel] application. For any questions into
particular API methods, consult Laravel documentation.

[laravel]: https://laravel.com

## Environment setup (Only needs to be done FIRST time)

- Make sure you have [Docker] and [Docker compose] on your machine.

  [docker]: https://docs.docker.com/get-docker
  [docker compose]: https://docs.docker.com/compose

  Make sure the Docker daemon is connected by typing `docker info`. It should
  print some information about the Docker daemon. If it says something about not
  being able to connect to the Docker daemon, you didn't set it up correctly.

- Run this command:
 
      docker compose build

  This builds the dev docker image. (if you change the Dockerfile, run this
  again!)

- Copy `.env.sample` to `.env` and then modify some values to your liking.

## Setting up OAuth (Only needs to be done once)

- Go to https://osu.ppy.sh/home/account/edit

- Scroll down to the bottom, under the section OAuth > own clients

- Click "New OAuth Application", and then fill out the details.
  - For the redirect URL, you want to put `<YOUR_HOST>/auth/callback`, replacing
      the YOUR_HOST part with wherever you open omdb for local development. For
      example, if you use http://localhost:8000, when put
      `http://localhost:8000/auth/callback` as the redirect URL.

- Copy the client ID and client secret into your config in the respective
    locations (probably `OSU_CLIENT_ID` and `OSU_CLIENT_SECRET` in the `.env`
    file)

## Developing setup (Needs to be done EVERY time you start developing)

- Start up the development Docker container by running:

      docker compose up -d

- Run a shell in the container:

      docker compose exec dev bash

- Once you're inside, cd to `/usr/src/app/omdb`.

- Start up a [tmux] session by just running `tmux`. You'll
    need this for running multiple things simultaneously.

  [tmux]: https://github.com/tmux/tmux/wiki

- Run `npm install` if you haven't already. This should only need to be done
    once, each time you change node dependencies.

## Setting up Docker

**IMPORTANT.** Create a `.env` file, containing the environment variables you
want. See `.env.sample` for some example values.

Run `docker compose up -d`. Make sure you have the `-d`, or else killing your
current terminal will kill the server. Now, visit port 8400 to view the app!

Other common Docker compose commands for convenience:

- `docker compose down`: Nuke the entire setup
- `docker compose ps`: See how the servers are doing
- `docker compose logs`: See the logs
  - `docker compose logs [container]`: See the logs for a specific container
  - `docker compose logs -f`: Get live updates to the logs
- `docker compose exec [container] bash`: Run a shell inside the container
- `docker compose restart [container]`: Restart one or all containers

## Custom commands

There's a couple custom commands implemented:

- `php artisan omdb:retrieve_beatmaps`

  This command starts pulling new beatmaps from the OSU API.

  TODO: Run this command on an interval.

- `php artisan omdb:import_dump`

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

- `php artisan omdb:chart_update`

  This requires the unreleased `ChartUpdate.php` script. It calculates all the
  weights and rankings and updates the database accordingly.

  TODO: Run this command on an interval.

  TODO: Find a way to have this checked into version control in a different repo
