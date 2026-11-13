#!/usr/bin/env python3
"""Read-only checks for an explicitly configured private HTTPS preview origin."""
from html.parser import HTMLParser
import re
import sys
from urllib.parse import urljoin,urlsplit
from urllib.request import urlopen

origin=sys.argv[1].rstrip('/') if len(sys.argv)>1 else ''
if not origin.startswith('https://') or not urlsplit(origin).hostname.endswith('.ts.net'):
    raise SystemExit('Usage: python3 scripts/tests/preview-http.py https://machine.tailnet.ts.net')
class Assets(HTMLParser):
    def __init__(self):super().__init__();self.urls=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag in ('img','script') and a.get('src'):self.urls.append(a['src'])
        if tag=='img' and a.get('srcset'):self.urls.extend(x.strip().split()[0] for x in a['srcset'].split(','))
        if tag=='link' and 'stylesheet' in a.get('rel',''):self.urls.append(a['href'])
assets=set()
for path in ('/','/rooms/','/rooms/garden-room/','/booking/','/contact/','/events/','/journal/'):
    with urlopen(origin+path,timeout=20) as r:
        assert r.status==200;html=r.read().decode()
    assert not re.search(r'https?://(?:localhost|127\.0\.0\.1)(?=[:/])',html),(path,'loopback URL leaked')
    document=Assets();document.feed(html)
    assets.update(urljoin(origin+path,u) for u in document.urls)
for url in list(assets):
    if urlsplit(url).netloc!=urlsplit(origin).netloc:continue
    with urlopen(url,timeout=20) as r:
        assert r.status==200
        if 'text/css' in r.headers.get('Content-Type',''):
            css=r.read().decode()
            assert not re.search(r'https?://(?:localhost|127\.0\.0\.1)(?=[:/])',css)
            for found in re.findall(r'url\([\'\"]?([^\)\'\"]+)',css):
                target=urljoin(url,found)
                if urlsplit(target).netloc==urlsplit(origin).netloc:
                    with urlopen(target,timeout=20) as resource:assert resource.status==200
print(f'Private preview passed: 7 pages, {len(assets)} asset URLs, CSS dependencies, no loopback references and valid HTTPS.')
