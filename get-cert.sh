#!/bin/bash

/usr/local/bin/certbot-auto certonly --non-interactive --manual-public-ip-logging-ok --agree-tos -m z@limme.net  -d *.limme.net --manual --preferred-challenges dns --manual-auth-hook "/root/certbot-aliyun/au.sh php aly add" --manual-cleanup-hook "/root/certbot-aliyun/au.sh php aly clean"