<?php

namespace ReactphpX\WebsocketGroup;

use Psr\Http\Message\ServerRequestInterface;
use ReactphpX\WebsocketMiddleware\ConnectionInterface;
use ReactphpX\WebsocketMiddleware\MessageComponentInterface;
use ReactphpX\ConnectionGroup\ConnectionGroup;

class WebsocketGroupComponent implements MessageComponentInterface
{
    protected $connectionGroup;

    public function __construct(ConnectionGroup $connectionGroup = null)
    {
        $this->connectionGroup = $connectionGroup ?? new ConnectionGroup();
    }

    public function onOpen(ConnectionInterface $conn, ServerRequestInterface $request)
    {
        $this->connectionGroup->addConnection($conn, $request->getQueryParams());
        $this->connectionGroup->emit('open', [$conn, $request]);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->connectionGroup->emit('message', [$from, $msg]);
    }

    public function onClose(ConnectionInterface $conn, $reason = null)
    {
        $this->connectionGroup->emit('close', [$conn, $reason]);
        $this->connectionGroup->closeConnection($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->connectionGroup->emit('error', [$conn, $e]);
        $conn->close();
    }
}