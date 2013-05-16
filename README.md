http.echo
==========
HTTP Request &amp; Response Service, written in PHP

## ENDPOINTS

- `/ip` Returns Request IP.
- `/user-agent` Returns user-agent.
- `/headers` Returns the array of request headers.
- `/get` Send GET data.
- `/post` Send POST data.
- `/patch` Send PATCH data.
- `/put` Send PUT data.
- `/delete` Send DELETE data
- `/gzip` Returns gzip-encoded data.
- `/status/:code` Returns given HTTP Status code.
- `/response-headers?key=val` Returns given response headers.
- `/redirect/:n` 301 Redirects *n* times.
- `/relative-redirect/:n` 302 Relative redirects *n* times.
- `/cookies` Returns cookie data.
- `/cookies/set?name=value` Sets one or more cookies.
- `/basic-auth/:user/:passwd` Challenges HTTP Basic Auth.
- `/download` Prompts the file download dialog.
- `/stream/:n` Streams *n* lines with a 1 second delay between each line.
- `/delay/:n` Delays responding for *n* seconds.
- `/html` Renders an HTML Page.
- `/robots.txt` Returns some robots.txt rules.
- `/deny` Denied by robots.txt file.


## DESCRIPTION

Web service for testing HTTP or cURL based libraries. Or testing your CLI cURL foo.

All endpoint responses are JSON-encoded.


## SETUP

##### Install in apache by pointing a vhost against the folder.

Requires PHP >= 5.3.0

If hosted in a subfolder, modify the `define('PATH_SHIFT', 0);` constant to the level of subpaths.

=========

##### Running on localhost

Requires PHP >= 5.4.0

```bash
php -S localhost:8080 index.php
```


## EXAMPLES

### $ curl http://localhost:8080/ip

```json
{
    "ip": "::1"
}
```

### $ curl http://localhost:8080/user-agent

```json
{
    "user-agent": "curl\/7.29.0"
}
```

### $ curl http://localhost:8080/get?k=v

```json
{
    "method": "GET",
    "ip": "::1",
    "uri": "http:\/\/localhost:8080\/get?k=v",
    "path": {
        "path": "\/get",
        "query": "k=v"
    },
    "headers": {
        "Host": "localhost:8080",
        "User-Agent": "Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko\/20100101 Firefox\/20.0",
        "Accept": "text\/html,application\/xhtml+xml,application\/xml;q=0.9,*\/*;q=0.8",
        "Accept-Language": "en-US,en;q=0.5",
        "Accept-Encoding": "gzip, deflate",
        "Connection": "keep-alive",
        "Cache-Control": "max-age=0"
    },
    "get": {
        "k": "v"
    },
    "post": [],
    "files": [],
    "body": "",
    "json": null
}
```

### $ curl http://localhost:8080/post?k=v -F somefile=@sample.txt

```json
{
    "method": "POST",
    "ip": "::1",
    "uri": "http:\/\/localhost:8080\/post?k=v",
    "path": {
        "path": "\/post",
        "query": "k=v"
    },
    "headers": {
        "User-Agent": "curl\/7.29.0",
        "Host": "localhost:8080",
        "Accept": "*\/*",
        "Content-Length": "356",
        "Expect": "100-continue",
        "Content-Type": "multipart\/form-data; boundary=----------------------------9ddcdc88b911"
    },
    "get": {
        "k": "v"
    },
    "post": [],
    "files": {
        "somefile": "File content output here...\n"
    },
    "body": "",
    "json": null
}
```

### $ curl -X PUT http://localhost:8080/put?k=v -d '{"a":1}'

```json
{
    "method": "PUT",
    "ip": "::1",
    "uri": "http:\/\/localhost:8080\/put?k=v",
    "path": {
        "path": "\/put",
        "query": "k=v"
    },
    "headers": {
        "User-Agent": "curl\/7.29.0",
        "Host": "localhost:8080",
        "Accept": "*\/*",
        "Content-Length": "7",
        "Content-Type": "application\/x-www-form-urlencoded"
    },
    "get": {
        "k": "v"
    },
    "post": [],
    "files": [],
    "body": "{\"a\":1}",
    "json": {
        "a": 1
    }
}
```

### $ curl -I http://localhost:8080/status/201

```
HTTP/1.1 201 Created
Host: localhost:8080
Connection: close
X-Powered-By: PHP/5.4.11
Content-type: text/html
```

## AUTHOR

[Wei Kin Huang](http://weikinhuang.com/)

## SEE ALSO

Based on [httpbin](https://github.com/kennethreitz/httpbin) by Kenneth Reitz.
