<?php
if (! isset($_SERVER['REQUEST_URI'])) {
    die();
}

define('PATH_SHIFT', 0);
if (isset($_SERVER['REDIRECT_URL'])) {
    $parts = explode('/', $_SERVER['REDIRECT_URL']);
} else {
    $parts = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
}
$prefix = ltrim(implode('/', array_splice($parts, 0, PATH_SHIFT + 1))) . '/';
if (! $parts) {
    die();
}

/**
 * Retrieve the IP address of the client machine
 *
 * @return string|false
 */
function getRemoteIp()
{
    static $ip;
    if ($ip !== null) {
        return $ip;
    }
    $ip = false;
    $headers = array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
        'REMOTE_HOST'
    );
    foreach ($headers as $header) {
        if (! empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            break;
        }
    }
    return $ip;
}

/**
 * Retrieve all request headers
 * @return array
 */
function getHeaders()
{
    static $headers;
    if ($headers) {
        return $headers;
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        return $headers;
    }
    $headers = array();
    foreach ($_SERVER as $k => $v) {
        $name = strtoupper($k);
        if (strpos($name, 'HTTP_') === 0) {
            $name = preg_replace_callback('/(^|_)(\w)/', function ($matches)
            {
                return ($matches[1] == '_' ? '-' : '') . strtoupper($matches[2]);
            }, preg_replace('/^http_/', '', strtolower($k)));
            $headers[$name] = $v;
        }
    }
    return $headers;
}

/**
 * Get the current hostname url
 *
 * @return string
 */
function getFullHost()
{
    $url = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $url .= 's';
    }
    $url .= '://' . $_SERVER['SERVER_NAME'];
    if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
        $url .= ':' . $_SERVER['SERVER_PORT'];
    }
    return $url;
}

/**
 * JSON encode with pretty print
 *
 * @param mixed $data
 * @return string
 */
function jsonify($data)
{
    return json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Send the http status header
 *
 * @param int $status
 */
function status($status)
{
    static $http_codes = array(
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',

        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',

        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version Not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    );
    header("{$_SERVER['SERVER_PROTOCOL']} $status {$http_codes[$status]}", true, $status);
}

// response data
$data = array(
    'method' => $_SERVER['REQUEST_METHOD'],
    'ip' => getRemoteIp(),
    'uri' => getFullHost() . $_SERVER['REQUEST_URI'],
    'path' => parse_url($_SERVER['REQUEST_URI']),
    'headers' => getHeaders(),
    'get' => $_GET,
    'post' => $_POST,
    'files' => array(),
    'body' => file_get_contents('php://input')
);
// if the put data is json, decode it
$data['json'] = json_decode($data['body']);
/* read all uploaded files */
foreach ($_FILES as $k => $v) {
    $data['files'][$k] = $v['error'] == UPLOAD_ERR_OK ? file_get_contents($v['tmp_name']) : false;
}

/* route requests */
switch (strtolower($parts[0])) {
    /* Check ip address */
    case 'ip':
        header('Content-Type: application/json');
        echo jsonify(array(
            'ip' => getRemoteIp()
        ));
        break;
    /* Check useragent */
    case 'user-agent':
        header('Content-Type: application/json');
        echo jsonify(array(
            'user-agent' => $_SERVER['HTTP_USER_AGENT']
        ));
        break;
    /* Check & Test headers */
    case 'headers':
        header('Content-Type: application/json');
        echo jsonify(array(
            'headers' => getHeaders()
        ));
        break;
    case 'response-headers':
        foreach ($_GET as $key => $value) {
            header($key . ': ' . $value);
        }
        echo jsonify(array(
            'headers' => $_GET
        ));
        break;
    /* Test request method */
    case 'get':
    case 'post':
    case 'put':
    case 'patch':
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] != strtoupper($parts[0])) {
            status(405);
            echo '405 Method Not Allowed';
            break;
        }
        header('Content-Type: application/json');
        echo jsonify($data);
        break;
    /* Test gzip */
    case 'gzip':
        ob_start('ob_gzhandler');
        header('Content-Type: application/json');
        $data['gzipped'] = true;
        echo jsonify($data);
        break;
    /* Test handing a status code */
    case 'status':
        $status_options = explode(',', isset($parts[1]) ? $parts[1] : '200');
        $status = intval($status_options[array_rand($status_options)]);
        status($status);
        switch ($status) {
            case 300:
            case 301:
            case 302:
            case 303:
            case 304:
            case 305:
            case 306:
            case 307:
                header('Location: ' . $prefix . 'redirect/1');
                break;
            case 401:
                header('WWW-Authenticate: Basic realm="Fake Realm"');
                break;
            case 407:
                header('Proxy-Authenticate: Basic realm="Fake Realm"');
                break;
        }
        break;
    /* Test handling cookies */
    case 'cookies':
        switch (isset($parts[1]) ? $parts[1] : null) {
            case 'set':
                foreach ($_GET as $k => $v) {
                    setcookie($k, $v, null, $prefix);
                }
                status(302);
                header('Location: ' . $prefix . $parts[0]);
                break;
            default:
                header('Content-Type: application/json');
                echo jsonify(array(
                    'cookies' => $_COOKIE
                ));
                break;
        }
        break;
    /* Test file download dialog */
    case 'download':
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="download.json"');
        echo jsonify($data);
        break;
    /* Test basic http auth */
    case 'basic-auth':
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $parts[1], $parts[2])) {
            if ($_SERVER['PHP_AUTH_USER'] == $parts[1] && $_SERVER['PHP_AUTH_PW'] == $parts[2]) {
                header('Content-Type: application/json');
                echo jsonify(array(
                    'auth' => true,
                    'user' => $_SERVER['PHP_AUTH_USER']
                ));
                break;
            }
        }
        header('WWW-Authenticate: Basic realm="Fake Realm"');
        status(401);
        header('Content-Type: application/json');
        echo jsonify(array(
            'auth' => false
        ));
        break;
    /* For testing streaming content */
    case 'stream':
        set_time_limit(0);
        $count = max(1, isset($parts[1]) ? intval($parts[1]) : 5);
        $output = json_encode($data);
        header('Content-Type: application/json');
        @ob_flush();
        @flush();
        echo '[';
        while ($count -- > 0) {
            sleep(1);
            @ob_flush();
            @flush();
            echo $output;
            if ($count > 0) {
                echo ',' . "\r\n";
            }
        }
        echo ']';
        break;
    /* For testing lag */
    case 'delay':
        set_time_limit(0);
        $delay = max(1, isset($parts[1]) ? intval($parts[1]) : 3);
        sleep($delay);
        header('Content-Type: application/json');
        echo jsonify($data);
        break;
    /* For testing redirects */
    case 'redirect':
    case 'redirect-relative':
        $n = max(1, isset($parts[1]) ? intval($parts[1]) : 1) - 1;
        status($parts[0] == 'redirect' ? 301 : 302);
        if ($n == 0) {
            header('Location: ' . $prefix . 'get');
        } else {
            header('Location: ' . $prefix . $parts[0] . '/' . $n);
        }
        break;
    /* For testing robots */
    case 'html':
        header('Content-Type: text/html');
        echo '<!DOCTYPE html><html><head></head><body><h1>Heading</h1><div><p>hi!</p></div></body></html>';
        break;
    case 'robots.txt':
        header('Content-Type: text/plain');
        echo 'User-agent: *' . "\n" . 'Disallow: /deny';
        break;
    case 'deny':
        header('Content-Type: text/plain');
        echo 'There\'s nothing here, move along...';
        break;
    default:
        status(404);
        break;
}
