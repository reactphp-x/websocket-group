<?php

namespace Reactphp\Framework\WebsocketGroup;

use Reactphp\Framework\ConnectionGroup\ConnectionGroup;
use Psr\Http\Message\ServerRequestInterface;
use Ratchet\RFC6455\Handshake\RequestVerifier;
use React\Http\Message\Response;
use React\Promise\Deferred;
use WyriHaximus\React\Stream\Json\JsonStream;

class WebsocketGroupMiddleware
{
    protected $connectionGroup;
    protected $requestVerifier;
    protected $attribute;
    protected $tokens = [];

    public function __construct(ConnectionGroup $connectionGroup = null)
    {
        $this->connectionGroup = $connectionGroup ?? new ConnectionGroup();
        $this->requestVerifier = new RequestVerifier();
    }

    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
    }

    public function __invoke(ServerRequestInterface $request, $next = null)
    {
        if ($this->requestVerifier->verifyAll($request)) {
            if (!$next) {
                return new Response(404);
            }
            return $next($request);
        }

        $params = [];
        if ($request->getMethod() === 'POST') {
            $params = json_decode((string) $request->getBody(), true);
            $params = $params ?: [];
        }
        $params = $params + $request->getQueryParams();


        if ($this->tokens) {
            $token = $params['token'] ?? '';
            if (!in_array($token, $this->tokens)) {
                return Response::json([
                    'code' => 1,
                    'msg' => 'token error',
                    'data' => []
                ]);
            }
        }

        if ($this->attribute) {
            $event = $request->getAttribute('event');
        } else {
            $event = $params['event'] ?? '';
        }

        if (!$event || !is_string($event)) {
            return Response::json([
                'code' => 1,
                'msg' => 'event error',
                'data' => []
            ]);
        }
        
        $events = explode(',', $event);
        $methodToParams = [];
        $extra = [];
        foreach ($events as $method) {
            if (method_exists($this->connectionGroup, $method)) {
                $className = get_class($this->connectionGroup);
                $rp = new \ReflectionClass($className);
                $methodParameters = [];
                $rpParameters = $rp->getMethod($method)->getParameters();
                foreach ($rpParameters as $rpParameter) {
                    $name = $rpParameter->getName();
                    $position = $rpParameter->getPosition();
                    if (isset($params[$method][$name])) {
                        $methodParameters[$position] = $params[$method][$name];
                    } else {
                        if ($rpParameter->isOptional()) {
                            $methodParameters[$position] = $rpParameter->getDefaultValue();
                        } else {
                            return  Response::json([
                                'code' => 1,
                                'msg' => "方法 $method 缺少 $name 参数",
                                'data' => []
                            ]);
                        }
                    }
                }

                $methodToParams[$method] = $methodParameters;
            } else {
                $extra[] = "方法 $method 不存在 或 缺少参数";
            }
        }


        if (empty($methodToParams)) {
            return Response::json([
                'code' => 1,
                'msg' => implode(',', $extra),
                'data' => []
            ]);
        }

        $data = [];
        foreach ($methodToParams as $m => $p) {
            $data[$m] = $this->connectionGroup->{$m}(...$p);
        }

        return $this->getJsonPromise($data)->then(function ($data) use ($extra) {
            return Response::json([
                'code' => 0,
                'msg' => 'ok',
                'extra' => $extra,
                'data' => $data
            ]);
        }, function ($e) {
            return Response::json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        })->catch(function ($e) {
            return Response::json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        });
    }

    public function getJsonPromise($array = [])
    {

        $deferred = new Deferred();
        $buffer = '';
        $jsonStream = new JsonStream();
        $jsonStream->on('data', function ($data) use (&$buffer) {
            $buffer .= $data;
        });

        $jsonStream->on('end', function () use (&$buffer, $deferred) {
            $deferred->resolve(json_decode($buffer, true));
            $buffer = '';
        });

        $jsonStream->end($array);
        return $deferred->promise();
    }
}
