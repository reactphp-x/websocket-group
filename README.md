# reactphp-framework/websocket-group

# install 

```
composer require reactphp-framework/websocket-group -vvv
```

# Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Reactphp\Framework\WebsocketGroup\WebsocketGroupComponent;
use Reactphp\Framework\WebsocketGroup\WebsocketGroupMiddleware;
use Reactphp\Framework\ConnectionGroup\ConnectionGroup;
use Reactphp\Framework\ConnectionGroup\SingleConnectionGroup;
use Reactphp\Framework\WebsocketMiddleware\WebsocketMiddleware;

$connectionGroup = SingleConnectionGroup::instance();
// $connectionGroup = new ConnectionGroup;

$connectionGroup->on('open', function ($conn, $request) use ($connectionGroup) {
    var_dump('open', $conn->_id, $request->getQueryParams());
    $connectionGroup->sendMessageTo_id($conn->_id, json_encode([
        'cmd' => 'open',
        '_id' => $conn->_id,
    ]));
    $connectionGroup->bindId(1, $conn->_id);
});

$connectionGroup->on('message', function ($from, $msg) use ($connectionGroup) {
    var_dump('message', $from->_id, $msg);
    $connectionGroup->sendMessageToId(1, 'get it');
});

$connectionGroup->on('close', function ($conn, $reason) {
    var_dump('close', $conn->_id, $reason);
});

$http = new React\Http\HttpServer(
    new WebsocketGroupMiddleware($connectionGroup),
    new WebsocketMiddleware(new WebsocketGroupComponent($connectionGroup))
);
$socket = new React\Socket\SocketServer('0.0.0.0:8090');
echo 'Server running at 8090' . PHP_EOL;
$http->listen($socket);
```

# call http send message

visit http://10.10.10.2:8090/isOnlineId?isOnlineId[id]=1

visit http://10.10.10.2:8090/isOnlineId,sendMessageToId?isOnlineId[id]=1&sendMessageToId[id]=1&sendMessageToId[msg]=hello


# see mor message

Reactphp\Framework\ConnectionGroup\ConnectionGroup


# License
MIT