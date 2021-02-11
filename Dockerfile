FROM php:7.4-fpm-alpine

MAINTAINER Konstantin <starternh@gmail.com>

RUN apk --no-cache add shadow composer gettext supervisor\
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data

ADD ./etc/supervisord.conf /etc

RUN curl -s https://releases.hashicorp.com/consul-template/0.19.5/consul-template_0.19.5_linux_amd64.tgz  > template.tgz \
    && tar -xvzf ./template.tgz \
    && mv ./consul-template /usr/local/bin/ \
    && rm ./template.tgz

COPY ./etc/consul-template /etc/consul-template
COPY ./usr/local/bin/entry-point /usr/local/bin
COPY ./usr/local/etc/php.ini /usr/local/etc/php
COPY ./usr/local/etc/www.conf /usr/local/etc/php-fpm.d
COPY ./public /var/www/html/public
COPY ./src /var/www/html/src
COPY ./composer.json ./composer.lock /var/www/html/
COPY ./bin /var/www/html/bin

RUN chmod 777 /var/log
RUN composer install -n && chown www-data:www-data -R /var/www/html

ENV CONSUL_HTTP_ADDR=consul:8500
ENV APP_DEBUG=false
ENV LOG_OUTPUT=stream
ENV LOG_LEVEL=WARNING
ENV CLUSTER=production

ENTRYPOINT ["entry-point"]
CMD ["consul-template", "-config", "/etc/consul-template/conf.hcl", "-exec", "php-fpm", "supervisord", "--configuration", "/etc/supervisord.conf"]
