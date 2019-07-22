# -*- coding: utf-8 -*-
import sys
import hmac
import time
import random
import hashlib
import json
import urllib
import base64
import os

pv = "python2"
if sys.version_info[0] < 3:
    from urllib import quote
    from urllib import urlencode
else:
    from urllib.parse import quote
    from urllib.parse import urlencode
    from urllib import request
    pv = "python3"

class Client(object):
    def __init__(self, secret_id, secret_key, host, uri, **params):
        self.secret_id = secret_id
        self.secret_key = secret_key
        self.host = host
        self.uri = uri
        self.params = params

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

        if pv == "python2":
            h = hmac.new(self.secret_key, msg, digestmod=hashlib.sha1)
            signature = base64.encodestring(h.digest()).strip()
        else:
            h = hmac.new(self.secret_key.encode('utf-8'),
                         msg.encode('utf-8'), digestmod=hashlib.sha1)
            signature = base64.encodebytes(h.digest()).strip()

        '''
        hashed = hmac.new(self.secret_key, msg, hashlib.sha1)
        base64 = binascii.b2a_base64(hashed.digest())[:-1]
        '''
        params['Signature'] = signature
        return params

    def send(self, params, method='GET'):
        params = self.sign(params, method)
        req_host = 'https://{}{}'.format(self.host, self.uri)
        url = req_host + "?" + urlencode(params)

        if pv == "python2":
            f = urllib.urlopen(url)
            result = f.read().decode('utf-8')
            return json.loads(result)
        else:
            req = request.Request(url)
            with request.urlopen(req) as f:
                result = f.read().decode('utf-8')
                return json.loads(result)

class Cns:
    def __init__(self, secret_id, secret_key):
        host, uri = 'cns.api.qcloud.com', '/v2/index.php'
        self.client = Client(secret_id, secret_key, host, uri)

    def list(self, domain, subDomain):
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
            dirpath = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
            domainfile = dirpath + "/domain.ini"
            domainarr = []
            with open(domainfile) as f:
                for line in f:
                    val = line.strip()
                    domainarr.append(val)

            rootdomain = '.'.join(domain_parts[-(2 if domain_parts[-1] in domainarr else 3): ])
            selfdomain = domain.split(rootdomain)[0]
            return (selfdomain[0:len(selfdomain)-1], rootdomain)
        return ("", domain)

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

    _, option, domain, name, value, secret_id, secret_key = sys.argv  # pylint: disable=all

    domain = Cns.getDomain(domain)
    if domain[0] == "":
        selfdomain = name
    else:
        selfdomain = name + "." + domain[0]

    cns = Cns(secret_id, secret_key)
    if option == 'add':
        result = (cns.create(domain[1], selfdomain, 'TXT', value))
    elif option == 'clean':
        for record in cns.list(domain[1], selfdomain)['data']['records']:
            #print (record['name'],record['id'] )
            result = (cns.delete(domain[1], record['id']))
	    #print (result["message"])
