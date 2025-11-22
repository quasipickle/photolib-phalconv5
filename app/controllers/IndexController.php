<?php

namespace Controller;

class IndexController extends BaseController
{
    public function indexAction()
    {
        $url = $this->url->get([
            'for' => 'album',
            'id' => $this->config->rootAlbumId
        ]);
        if ($this->request->hasQuery('q')) {
            $url .= 'q=' + urlencode($this->request->getQuery('q'));
        }
        return $this->response->redirect($url);
    }

    public function notFoundAction()
    {
        $this->view->title = "Page not found";
    }
}
