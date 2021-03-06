<?php

namespace rap\web\response;

use rap\web\Response;

class PlainBody implements ResponseBody {

    private $content;
    /**
     * Body _initialize.
     *
     * @param $content
     */
    public function __construct($content) {
        $this->content = $content;
    }


    public function beforeSend(Response $response) {
        $response->setContent($this->content);
    }


}