<?php

namespace Controller;

use Component\Retval;
use Component\Image\File\{File, UploadedFile, DownloadedFile};
use Component\Image\{Image, Name, Validator};
use Component\Image\Validator\{DownloadValidator, UploadValidator};
use Model\{Album, AlbumPhoto, Photo};

class PhotoController extends BaseController
{
    public function uploadAction()
    {
        $albumId = $this->request->getPost("albumId");
        if (!$this->request->hasFiles()) {
            $Retval = new Retval();
            return $Retval->message("No files were uploaded")->response();
        }

        $File = $this->request->getUploadedFiles()[0];
        $UploadedFile = new UploadedFile($File);
        $Validator  = new UploadValidator($UploadedFile);
        $Retval = $this->importFile($albumId, $UploadedFile, $Validator);



        return $Retval->response();
    }

    public function downloadAction()
    {
        $Retval = new Retval();
        $url = $this->request->getPost("url");
        $albumId = $this->request->getPost("albumId");
        try {
            $Response = \Httpful\Request::get($url)->send();
        } catch (\Httpful\Exception\ConnectionErrorException $e) {
            return $Retval->message("Could not connect to the source server")->response();
        }

        if ($Response->code !== 200) {
            return $Retval->message("File could not be found.")->response();
        }

        $tmp_file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "photolib-upload";
        file_put_contents($tmp_file_path, $Response->body);

        $path = parse_url($url, PHP_URL_PATH);
        $filename = basename($path);

        $DownloadedFile = new DownloadedFile($tmp_file_path, $filename);
        $Validator = new DownloadValidator($DownloadedFile);
        $Retval = $this->importFile($albumId, $DownloadedFile, $Validator);
        return $Retval->response();
    }

    private function importFile(int $albumId, File $File, Validator $Validator): Retval
    {
        $Retval = new Retval();
        try {
            $Validator->check();
        } catch (\Exception $e) {
            return $Retval->message($e->getMessage());
        }

        $Album = Album::findFirst($albumId);
        if ($Album == null) {
            return $Retval->message("The album to upload into, doesn't exist.");
        }

        $Photo = new Photo();
        $Photo->original_filename = $File->getName();
        $Photo->mime_type = $File->getType();
        $Photo->filesize = $File->getSize();
        [$width, $height] = getimagesize($File->getTempName());
        $Photo->width = $width;
        $Photo->height = $height;

        $Name = new Name($File);
        $originalPath = $Name->getName();
        $originalFullPath = $this->config->dirs->file->photo . $originalPath;
        $dirPath = dirname($originalFullPath);
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        $File->moveTo($originalFullPath);
        $Photo->path = $originalPath;

        $Image = new Image($originalFullPath);

        foreach ($this->config->image->versions as $version) {
            $path = $Name->getName($version->suffix);
            $fullPath = $this->config->dirs->file->photo . $path;
            $Image->resize($fullPath, $version->width, $version->height, $version->quality);
            [$resizedWidth, $resizedHeight] = getimagesize($fullPath);

            $Photo->{$version->type . "_path"} = $path;
            $Photo->{$version->type . "_width"} = $resizedWidth;
            $Photo->{$version->type . "_height"} = $resizedHeight;

            if ($version->type == "thumb") {
                $Photo->phash = Image::getPHash($fullPath);
            }
        }

        $Photo->create();

        $AlbumPhoto = new AlbumPhoto();
        $AlbumPhoto->album_id = $Album->id;
        $AlbumPhoto->photo_id = $Photo->id;
        $AlbumPhoto->save();

        $this->view->photo = $Photo;
        $this->view->Album = $Album;
        $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
        $this->view->start();
        $this->view->render("photo", "new");
        $Retval->content($this->view->getContent());

        return $Retval->success(true);
    }
}
