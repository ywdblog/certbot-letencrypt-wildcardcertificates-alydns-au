import sys 

def getDomain(domain):
    
    domain_parts = domain.split('.')
    if len(domain_parts) > 2:
        rootdomain='.'.join(domain_parts[-(2 if domain_parts[-1] in {"co.jp","com.tw","net","com","com.cn","org","cn","gov","net.cn","io","top","me","int","edu","link"} else 3):])
	selfdomain=domain.split(rootdomain)[0]
	return (selfdomain[0:len(selfdomain)-1],rootdomain)
    return ("",domain)


print (getDomain(sys.argv[1]))

