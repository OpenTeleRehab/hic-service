FROM rathaheang/nginx-php:7.4

RUN apt-get update -y && apt-get install ffmpeg -y
