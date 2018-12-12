<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/12/10
 * Time: 下午3:08
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\rpc\client;

use rap\config\Config;
use rap\swoole\pool\PoolTrait;
use Swoole\Coroutine\Http2\Client;

/**
 * 通过 http2 实现的 Rpc 客户端 支持长链接
 */
class RpcHttp2Client implements RpcClient {
    use PoolTrait;

    private $config = ['host' => '',
                       'port' => 9501,
                       'path' => 'rpc_____call',
                       'token' => '',
                       'serialize' => 'serialize',
                       'timeout' => 0.05,
                       'fuse_time'=>30,//熔断器熔断后多久进入半开状态
                       'fuse_fail_count'=>20,//连续失败多少次开启熔断
                       'pool' => ['min' => 1, 'max' => 10]];


    /**
     * @var Client
     */
    private $cli;

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
     * @param string $name 接口名称
     * @param mixed  $data 对象或数组
     *
     * @return mixed   返回结果
     */
    public function query($interface, $method, $data) {
        //
        if (!$this->cli) {
            $this->connect();
        }

        if (!$this->cli->connected) {
            $this->cli->connect();
        }
        if (!$this->cli->connected) {
            throw new RpcClientException('连接rpc服务失败', 100);
        }

        $req = new \swoole_http2_request();
        $req->method = 'POST';
        $req->path = $this->config[ 'path' ];
        $req->headers = ['rpc_client_name' => $this->config[ 'name' ],
                         'rpc_serialize' => $this->config[ 'serialize' ],
                         'rpc_token' => $this->config[ 'token' ],
                         'rpc_interface' => $interface,
                         'rpc_method' => $method];
        if ($this->config[ 'serialize' ] == 'serialize') {
            $data = serialize($data);
        } else {
            $data = json_encode($data);
        }
        $req->data = $data;
        $this->cli->send($req);
        $response = $this->cli->recv();
        if (!$this->cli->errCode && $response->statusCode == 200) {
            $type = $response->headers[ 'content-type' ];
            $data = $response->data;
            if ($data && strpos($type, 'application/rap-rpc')) {
                $data = unserialize($data);
                //有错误异常直接外抛
                if ($data instanceof \RuntimeException) {
                    throw $data;
                }
            } else if ($data && strpos($type, 'application/json')) {
                $data = json_decode($data, true);
                if( $response->headers[ 'rpc-exception' ]){
                    $type=$data['type'];
                    $msg=$data['msg'];
                    $code=$data['code'];
                    $exception=new $type($msg,$code);
                    throw $exception;
                }
            }
            return $data;
        } else {
            throw new RpcClientException('服务异常', 100);
        }
    }

    public function fuseConfig() {
       return [
           'fuse_time'=> $this->config[ 'fuse_time' ],//熔断器熔断后多久进入半开状态
           'fuse_fail_count'=>$this->config[ 'fuse_fail_count' ],//连续失败多少次开启熔断
       ];
    }


    public function connect() {
        $this->cli = new Client($this->config[ 'host' ], $this->config[ 'port' ], false);
        $this->cli->set(['timeout' => $this->config[ 'timeout' ]]);
        $this->cli->connect();
    }

}