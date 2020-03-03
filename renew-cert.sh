#!/bin/bash
# Haven't tested yet

./certbot-auto renew --non-interactive --manual-public-ip-logging-ok --agree-tos  --manual --preferred-challenges dns --manual-auth-hook "/root/certbot-aliyun//au.sh php aly add" --manual-cleanup-hook "/root/certbot-aliyun//au.sh php aly clean"