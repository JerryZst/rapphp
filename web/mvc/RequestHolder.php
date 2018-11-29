<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/8/30
 * Time: 上午8:37
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\mvc;


use rap\swoole\pool\CoHolder;
use rap\web\Request;


class RequestHolder {

    public static function setRequest(Request $request) {
        CoHolder::getHolder()->add('request',$request);
    }

    /**
     * 获取request
     * @return Request
     */
    public static function getRequest() {
       return CoHolder::getHolder()->get('request');
    }

    /**
     * 获取response
     * @return \rap\web\Response
     */
    public static function getResponse() {
        return self::getRequest()->response();
    }

}