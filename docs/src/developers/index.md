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

- Install Composer dependencies. Run this command:
 
      docker compose run dev bash

  This should bring you to a bash shell. Run:

      composer install

  This will spin for a minute, installing all the PHP dependencies. After this,
  you should be good to go.

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

## Developing setup + Installing dependencies (Needs to be done EVERY time you start developing)

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
