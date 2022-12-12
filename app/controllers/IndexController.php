<?php

namespace Controller;

class IndexController extends BaseController
{
    public function indexAction()
    {
        return $this->response->redirect("/album/" . $this->config->root_album_id);
    }

    public function notFoundAction()
    {}
}
