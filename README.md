# certbot-letencrypt-wildcardcertificates-alydns-au

### 功能

用于自动设置阿里云 DNS 记录，从而配合 certbot 完成证书验证工作（包括通配符、SAN、单域名证书），从而避免人工干预。

### 使用方法

1：下载：

```
$ git clone https://github.com/ywdblog/certbot-letencrypt-wildcardcertificates-alydns-au

$ cd certbot-letencrypt-wildcardcertificates-alydns-au
```

2：配置：

- au.sh，修改 PHPPROGRAM（au.sh 脚本的目录）、DOMAIN（你的域名）。
- alydns.php，修改 accessKeyId、accessSecrec，需要去阿里云申请 API key 和 Secrec，用于调用阿里云 DNS API。

3：运行 renew 命令（也包括申请证书命令）：

```
# 测试
$ certbot-auto renew --cert-name newyingyong.cn --manual-auth-hook /你的脚本目录/au.sh --dry-run

#renew
$ certbot-auto renew --cert-name newyingyong.cn --manual-auth-hook /你的脚本目录/au.sh 
```
