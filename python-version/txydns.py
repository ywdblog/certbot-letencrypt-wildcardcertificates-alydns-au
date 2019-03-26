# -*- coding: utf-8 -*-
import sys
import hmac
import time
import random
import hashlib
import binascii
#第三方包,需要安装
# python2:pip install requests
# python3:pip3 install requests
import requests

# @akgnah https://github.com/akgnah


class Client(object):
    def __init__(self, secret_id, secret_key, host, uri, **params):
        self.secret_id = secret_id
        self.secret_key = secret_key
        self.host = host
        self.uri = uri
        self.params = params
        if sys.version_info[0] > 2:
            self.Py3 = True
            self.secret_key = bytes(self.secret_key, 'utf-8')
        else:
            self.Py3 = False

    def public_params(self):
        params = {
            'Nonce': random.randint(1, 9999),
            'SecretId': self.secret_id,
            'SignatureMethod': 'HmacSHA1',
            'Timestamp': int(time.time()), 
        }
        params.update(self.params)

        return params

    def sign(self, params, method='GET'):
        params = params.copy()
        params.update(self.public_params())
        p = {}
        for k in params:
            if method == 'POST' and str(params[k])[0:1] == '@':
                continue
            p[k.replace('_', '.')] = params[k]
        ps = '&'.join('%s=%s' % (k, p[k]) for k in sorted(p))

        msg = '%s%s%s?%s' % (method.upper(), self.host, self.uri, ps)
        if self.Py3:
            msg = bytes(msg, 'utf-8')

        hashed = hmac.new(self.secret_key, msg, hashlib.sha1)
        base64 = binascii.b2a_base64(hashed.digest())[:-1]
        if self.Py3:
            base64 = base64.decode()

        params['Signature'] = base64

        return params

    def send(self, params, method='GET'):
        params = self.sign(params, method)
        req_host = 'https://{}{}'.format(self.host, self.uri)
        if method == 'GET':
            resp = requests.get(req_host, params=params)
        else:
            resp = requests.post(req_host, data=params)

        return resp.json()


# View details at https://cloud.tencent.com/document/product/302/4032
class Cns:
    def __init__(self, secret_id, secret_key):
        host, uri = 'cns.api.qcloud.com', '/v2/index.php'
        self.client = Client(secret_id, secret_key, host, uri)

    def list(self, domain,subDomain):
        body = {
            'Action': 'RecordList',
            'domain': domain,
            'subDomain': subDomain
        }

        return self.client.send(body)

    @staticmethod
    def getDomain(domain):
        domain_parts = domain.split('.')
        if len(domain_parts) > 2:
            rootdomain='.'.join(domain_parts[-(2 if domain_parts[-1] in {"co.jp","com.tw","net","com","com.cn","org","cn","gov","net.cn","io","top","me","int","edu","link"} else 3):])
            selfdomain=domain.split(rootdomain)[0]
            return (selfdomain[0:len(selfdomain)-1],rootdomain)
        return ("",domain)

    def create(self, domain, name, _type, value):
        body = {
            'Action': 'RecordCreate',
            'domain': domain,
            'subDomain': name,
            'recordType': _type,
            'recordLine': '默认',
            'value': value
        }
        return self.client.send(body)

    def delete(self, domain, _id):
        body = {
            'Action': 'RecordDelete',
            'domain': domain,
            'recordId': _id
        }

        return self.client.send(body)


if __name__ == '__main__':
    # Create your secret_id and secret_key at https://console.cloud.tencent.com/cam/capi

    _, option, domain, name, value,secret_id, secret_key = sys.argv  # pylint: disable=all

    domain = Cns.getDomain(domain)
    if domain[0]=="":
        selfdomain =  name
    else:
        selfdomain = name + "." + domain[0]

    cns = Cns(secret_id, secret_key)
    if option == 'add':
        result=(cns.create(domain[1], selfdomain, 'TXT', value))
    elif option == 'clean':
        for record in cns.list(domain[1],selfdomain)['data']['records']:
            #print (record['name'],record['id'] )
            result= (cns.delete(domain[1], record['id']))
	    #print (result["message"])
    #print(result)
