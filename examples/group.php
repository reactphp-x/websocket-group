<?php

require __DIR__ . '/../vendor/autoload.php';

use ReactphpX\WebsocketGroup\WebsocketGroupComponent;
use ReactphpX\WebsocketGroup\WebsocketGroupMiddleware;
use ReactphpX\ConnectionGroup\ConnectionGroup;
use ReactphpX\ConnectionGroup\SingleConnectionGroup;
use ReactphpX\WebsocketMiddleware\WebsocketMiddleware;

$connectionGroup = SingleConnectionGroup::instance();
// $connectionGroup = new ConnectionGroup;

$connectionGroup->on('open', function ($conn, $request) use ($connectionGroup) {
    // var_dump('open', $conn->_id, $request->getServerParams());
    $connectionGroup->sendMessageTo_id($conn->_id, json_encode([
        'cmd' => 'open',
        '_id' => $conn->_id,
    ]));
    // $connectionGroup->bindId(1, $conn->_id);
});

$connectionGroup->on('message', function ($from, $msg) use ($connectionGroup) {
    var_dump('message', $from->_id, $msg);
    // $connectionGroup->sendMessageToId(1, 'get it');
    if ($msg == 'ping') {
        $connectionGroup->sendMessageTo_id($from->_id, json_encode([
            'cmd' => 'open',
            '_id' => $from->_id,
        ]));
    }

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
