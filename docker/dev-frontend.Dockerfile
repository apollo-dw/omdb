FROM node:latest AS node

RUN mkdir -p /code
WORKDIR /code/omdb
