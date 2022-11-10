#!/bin/sh
nice -n 20 /usr/bin/supervisord -c /etc/supervisor/conf.d/workers.conf
