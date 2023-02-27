# omdb
osu! map database - rating website

this is the source code to OMDB. I have no clue how to help people contribute so I literally cannot write a guide yet. Keep in mind the code sucks too because this is my first time doing a web project... I promise I can do better :sob:

Please leave issues with the website here in this PR thank you

**Where is the chart update / beatmap retrieval scripts?**
Elsewhere. If any of you twerps want to look at it then I can reveal the algorithm whenever needed

## Developers: setting up with Docker

To set up a development environment, use [Docker compose]. Make sure the Docker
daemon is connected by typing `docker info`. It should print some information
about the Docker daemon.

[docker compose]: https://docs.docker.com/compose

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
