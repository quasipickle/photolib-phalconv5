<?php

namespace Controller;

use Component\Retval;
use Model\{Album, AlbumPhoto, Photo};
use Phalcon\Http\Response;
use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;

class BattleController extends BaseController
{
    public function indexAction()
    {
        if ($this->request->isPost()) {
            $winnerId = $this->request->getPost("winnerId");
            $loserId = $this->request->getPost("loserId");

            $Winner = Photo::findFirst($winnerId);
            if ($Winner != null) {
                $Winner->wins = $Winner->wins + 1;
                $Winner->battles = $Winner->battles + 1;
                $Winner->save();
            }
            $Loser = Photo::findFirst($loserId);
            if ($Loser != null) {
                $Loser->battles = $Loser->battles + 1;
                $Loser->save();
            }
            $Response = new Response();
            return $Response->redirect("/battle", false, 302);
        }
        $this->footerCollection
            ->addJs($this->url->get("/public/js/zoom.js"), true, false, ["type" => "module"])
            ->addJs($this->url->get("/public/js/battle.js"), true, false, ["type" => "module"]);
        $this->view->title = "Battle";

        $photos = Photo::find(["order" => "RAND()", "limit" => 2]);
        $this->view->photos = $photos;

        $photoIds = array_column($photos->toArray(), "id");
        $memberships = AlbumPhoto::find([
            "columns" => ["album_id" => "MAX(album_id)", "photo_id"],
            "conditions" => "photo_id IN ({ids:array})",
            "bind" => [ "ids" => $photoIds ],
            "group" => "photo_id"
        ]);
        $breadcrumbs = [];
        foreach ($memberships as $albumPhoto) {
            $Album = Album::findFirst("id = " . $albumPhoto->album_id);

            $breadcrumbs[$albumPhoto->photo_id] = array_filter(
                $this->buildBreadcrumbs($Album),
                fn($album) => $album->id != $this->config->rootAlbumId
            );
        }

        $this->view->breadcrumbs = $breadcrumbs;
    }

    /**
     * This is called by AJAX, and the rendered output injected into the calling page
     * @return string
     */
    public function statsAction()
    {
        $Builder = new QueryBuilder();
        // Photo rate count distribution
        $distributionResult = $Builder
            ->from([
                "photo" => Photo::class
            ])
            ->columns([
                "photo.battles",
                "count" => "COUNT(*)"
            ])
            ->groupBy("photo.battles")
            ->orderBy("photo.battles")
            ->getQuery()
            ->execute();
        $distribution = [];
        foreach ($distributionResult as $row) {
            $distribution[$row["battles"]] = $row["count"];
        }
        for ($i = min(array_keys($distribution)); $i < max(array_keys($distribution)); $i++) {
            if (!array_key_exists($i, $distribution)) {
                $distribution[$i] = 0;
            }
        }
        ksort($distribution);
        $this->view->distribution = $distribution;
        $this->view->distributionMax = max($distribution);

        $Builder = new QueryBuilder();
        $photoResult = $Builder
            ->from([
                "photo" => Photo::class
            ])
            ->where("battles > 1")
            ->orderBy("wins/battles DESC, battles DESC")
            ->limit(10)
            ->getQuery()
            ->execute();
        $this->view->popularPhotos = $photoResult;

        $Builder = new QueryBuilder();
        $albumResult = $Builder
            ->columns([
                "a.name",
                "pf.thumb_path",
                "photoWins" => "SUM(p.wins)",
                "photoBattles" => "SUM(p.battles)",
                "success" => "SUM(p.wins) / SUM(p.battles)"
            ])
            ->from([
                "pf" => Photo::class
            ])
            ->leftJoin(Album::class, "pf.id = a.photo_id", "a")
            ->leftJoin(AlbumPhoto::class, "a.id = ap.album_id", "ap")
            ->leftJoin(Photo::class, "ap.photo_id = p.id", "p")
            ->groupBy("a.id, pf.thumb_path")
            ->orderBy("success DESC, photoBattles DESC")
            ->limit(10)
            ->getQuery()
            ->execute();

        $this->view->popularAlbums = $albumResult;
        $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);

        $this->view->start();
        $this->view->render("battle", "stats");
        $this->view->finish();

        $Retval = new Retval();
        return $Retval->success(true)->content($this->view->getContent())->response();
    }
}
