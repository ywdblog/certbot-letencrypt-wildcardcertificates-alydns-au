# coding:utf-8

import json
import sys
import os 

class GodaddyDns:
    def __init__(self, access_key_id, access_key_secret, domain_name):
        self.access_key_id = access_key_id
        self.access_key_secret = access_key_secret
        self.domain_name = domain_name

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

    def curl(self, url, data, method):
        if sys.version_info[0] < 3:
            print ("s")
            import urllib2
            from urllib2 import URLError, HTTPError
            httpdata = json.dumps(data).encode('utf-8')
            req = urllib2.Request(url=url, data=httpdata)
            req.get_method = lambda: method
            req.add_header('accept', 'application/json')
            req.add_header('Content-Type', 'application/json')
            key = "sso-key " + self.access_key_id + ':' + self.access_key_secret
            req.add_header('authorization', key)
            try:
                with urllib2.urlopen(req) as res:
                    code = res.getcode()
                    print (res.info())
                    resinfo = res.read().decode('utf-8')
                    result = True
                    if code != 200:
                        result = False
                    return (result, resinfo)
            except AttributeError as e:
                #python2 处理 PATCH HTTP 方法的一个Bug，不影响结果
                return (True,'')
            except (HTTPError, URLError) as e:
                return (False, str(e))

        else :
            import urllib.request
            from urllib.error import URLError, HTTPError
            
            httpdata = json.dumps(data).encode('utf-8')

            req = urllib.request.Request(url=url, data=httpdata, method=method)
            req.add_header('accept', 'application/json')
            req.add_header('Content-Type', 'application/json')
            key = "sso-key " + self.access_key_id + ':' + self.access_key_secret

            req.add_header('authorization', key)
            try:
                with urllib.request.urlopen(req) as res:
                    code = res.getcode()
                    # print (res.info())
                    resinfo = res.read().decode('utf-8')
                    result = True
                    if code != 200:
                        result = False
                    return (result, resinfo)
            except (HTTPError, URLError) as e:
                return (False, str(e))

    def CreateDNSRecord(self, name, value, recordType='TXT'):
        url = "https://api.godaddy.com/v1/domains/" + \
            self.domain_name + "/records"
        data = [{"data": value, "name": name, "ttl": 3600, "type": recordType}]
        return self.curl(url, data, "PATCH")

    def GetDNSRecord(self, name, recordType='TXT'):
        url = "https://api.godaddy.com/v1/domains/" + \
            self.domain_name + "/records/" + recordType + "/" + name
        return self.curl(url, {}, "GET")

    def DeleteDNSRecord(self, name, recordType='TXT'):
        '''
        Godaddy DNS  没有提供删除DSN记录的API
        '''
        return True

file_name, cmd, certbot_domain, acme_challenge, certbot_validation, ACCESS_KEY_ID, ACCESS_KEY_SECRET = sys.argv

certbot_domain = GodaddyDns.getDomain(certbot_domain)
if certbot_domain[0] == "":
    selfdomain = acme_challenge
else:
    selfdomain = acme_challenge + "." + certbot_domain[0]

domain = GodaddyDns(ACCESS_KEY_ID, ACCESS_KEY_SECRET, certbot_domain[1])
# print (domain.GetDNSRecord(selfdomain))

if cmd == "add":
    print(domain.CreateDNSRecord(selfdomain, certbot_validation))
