FROM node:latest AS node

RUN mkdir -p /code
WORKDIR /code/omdb

CMD ["bash", "-c", "npm install; npm run dev"]
