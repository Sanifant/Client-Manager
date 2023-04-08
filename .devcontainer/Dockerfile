# DO NOT EDIT: created by update.sh from Dockerfile-debian.template
# FROM php:8.1-apache-bullseye
FROM nextcloud

RUN apt-get update && apt-get install -y git;

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]