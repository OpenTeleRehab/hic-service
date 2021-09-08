FROM rathaheang/nginx-php:7.4

COPY config/ansible/roles/build/templates/queue-worker.conf.j2 /etc/supervisor/conf.d/queue-worker.conf
RUN apt-get update -y && apt-get install ffmpeg -y && apt install imagemagick -y
