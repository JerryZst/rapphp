<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/10
 * Time: 下午3:06
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc\client;

use rap\config\Config;
use rap\swoole\pool\PoolTrait;
use rap\util\http\Http;
use Swoole\Coroutine\Http\Client;

/**
 * 通过 http 实现的 Rpc 客户端
 * swoole环境下用协程客户端,否者自动降级
 */
class RpcHttpClient implements RpcClient {
    use PoolTrait;

    public $FUSE_STATUS     = 3;
    public $FUSE_FAIL_COUNT = 0;
    public $FUSE_OPEN_TIME;

    private $config = ['host' => '',
                       'auth' => '',
                       'port' => 9501,
                       'base_path' => '',
                       'path' => '/rpc_____call',
                       'authorization' => '',
                       'serialize' => 'serialize',
                       'timeout' => 3,
                       'fuse_time' => 30,//熔断器熔断后多久进入半开状态
                       'fuse_fail_count' => 20,//连续失败多少次开启熔断
                       'pool' => ['min' => 1, 'max' => 10]];



    public function config($config) {
        $this->config = array_merge($this->config, $config);
        $this->config[ 'name' ] = Config::getFileConfig()[ 'app' ][ 'name' ];
        if (!$this->config[ 'name' ]) {
            $this->config[ 'name' ] = 'rap_rpc_client';
        }

    }

    public function poolConfig() {
        return $this->config[ 'pool' ];
    }

    /**
     * 发起请求
     *
     * @param $interface    string 接口
     * @param $method       string 方法名
     * @param $data         array 参数
     * @param $header       array 参数
     * @param $timeout      int|float 超时时间
     *
     * @return mixed
     * @return mixed   返回结果
     */
    public function query($interface, $method, $data, $header = [], $timeout = -1) {
        if ($this->config[ 'auth' ]) {
            $headers[ 'Rpc-Auth' ] = md5($this->config[ 'auth' ] . $interface . $method);
        }
        $authorization = $header[ 'authorization' ];
        $headers = array_merge($header, ['Rpc-Client-Name' => Config::get('app')[ 'name' ],
                                         'Authorization' => $this->config[ 'authorization' ],
                                         'Authorization-Forward' => $authorization,
                                         'Rpc-Serialize' => $this->config[ 'serialize' ],
                                         'Rpc-Interface' => $interface,
                                         'Rpc-Method' => $method]);
        if (IS_SWOOLE && \Co::getuid() !== -1) {
            return $this->queryCoroutine($headers, $data, $timeout );
        } else {
            return $this->queryByRequest($headers, $data, $timeout );
        }
    }


    public function queryByRequest($headers, $data, $timeout = -1) {
        $scheme = 'http://';
        if ($this->config[ 'port' ] == 443) {
            $scheme = 'https://';
        }
        if ($this->config[ 'serialize' ] == 'serialize') {
            $data = serialize($data);
        } else {
            $data = json_encode($data);
        }
        if($timeout==-1){
            $timeout=$this->config[ 'timeout' ];
        }
        $url=$scheme . $this->config[ 'host' ] . ':' . $this->config[ 'port' ] . $this->config[ 'base_path' ] .
            $this->config[ 'path' ];
        $response = Http::put($url, $headers, $data, $timeout);
        if ($response->status_code == 200) {
            $type = $response->headers[ 'content-type' ];
            $data = $response->body;
            if ($data && strpos($type, 'application/php-serialize') == 0) {
                $data = unserialize($data);
            } elseif ($data && strpos($type, 'application/json') == 0) {
                $data = json_decode($data, true);
            }
            if ($response->headers[ 'rpc-exception' ]) {
                $type = $data[ 'type' ];
                $msg = $data[ 'msg' ];
                $code = $data[ 'code' ];
                $exception = new $type($msg, $code);
                throw $exception;
            }
            return $data;
        } else {
            throw new RpcClientException('服务异常', 100);
        }
    }

    public function queryCoroutine($headers, $data, $timeout = -1) {
        $cli = new Client($this->config[ 'host' ], $this->config[ 'port' ]);
        if($timeout==-1){
            $timeout=$this->config[ 'timeout' ];
        }
        $cli->set(['timeout' => $timeout]);
        $cli->setHeaders($headers);
        if ($this->config[ 'serialize' ] == 'serialize') {
            $data = serialize($data);
        } else {
            $data = json_encode($data);
        }
        $cli->post($this->config[ 'base_path' ] . $this->config[ 'path' ], $data);
        $cli->close();
        if ($cli->statusCode == 200) {
            $type = $cli->headers[ 'content-type' ];
            $data = $cli->body;
            if ($data && strpos($type, 'application/php-serialize') == 0) {
                $data = unserialize($data);
            } elseif ($data && strpos($type, 'application/json') == 0) {
                $data = json_decode($data, true);
            }
            if ($cli->headers[ 'rpc-exception' ]) {
                $type = $data[ 'type' ];
                $msg = $data[ 'msg' ];
                $code = $data[ 'code' ];
                $exception = new $type($msg, $code);
                throw $exception;
            }
            return $data;
        } else {
            throw new RpcClientException('服务异常', 100);
        }
    }


    public function connect() {
    }

    public function fuseConfig() {
        return ['fuse_time' => $this->config[ 'fuse_time' ],//熔断器熔断后多久进入半开状态
                'fuse_fail_count' => $this->config[ 'fuse_fail_count' ],//连续失败多少次开启熔断
        ];
    }
}
