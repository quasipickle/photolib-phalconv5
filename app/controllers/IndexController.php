<?php

namespace Controllers;

class IndexController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        return $this->response->redirect("/album/" . $this->config->root_album_id);
    }
}
