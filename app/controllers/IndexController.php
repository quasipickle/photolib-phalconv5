<?php

namespace Controller;

class IndexController extends BaseController
{
    public function indexAction()
    {
        return $this->response->redirect("/album/" . $this->config->rootAlbumId);
    }

    public function notFoundAction()
    {
        $this->view->title = "Page not found";
    }
}
