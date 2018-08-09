**基本操作和 PHP 版差不多，只列举差异部分，具体可参考 [README.md](README.md)**

### 配置

修改 python-version/alydns27.py 或者 python-version/alydns36.py (Python2/Python3 均支持)，替换 ACCESS_KEY_ID、ACCESS_KEY_SECRET  变量，需要去阿里云申请 API key 和 Secrec。

### 申请证书

```
# 测试是否有错误
$ certbot-auto certonly  -d *.example.com --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/python-version/au.sh  --dry-run  

# 实际申请
$ certbot-auto certonly  -d *.example.com --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/python-version/au.sh    
```

### 续期证书

1：对机器上所有证书 renew

```
$ certbot-auto renew  --manual --preferred-challenges dns  --manual-auth-hook /脚本目录/python-version/au.sh   
```

2：对某一张证书续期 

```
$ certbot-auto renew --cert-name simplehttps.com  --manual-auth-hook /脚本目录/python-version/au.sh 
```

**再说一遍：详细操作见 [README.md](README.md)**
