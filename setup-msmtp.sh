#!/bin/bash
if [ ! -f /usr/bin/msmtp ]; then
  apt-get update && apt-get install -y msmtp
fi

echo -e "account default\nhost mailhog\nport 1025\nauto_from on" > /etc/msmtprc

exec docker-entrypoint.sh apache2-foreground
