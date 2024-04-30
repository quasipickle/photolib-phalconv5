<?php

namespace Controller;

use Component\Retval;
use Component\Image\File\{File, UploadedFile, DownloadedFile};
use Component\Image\{Image, Path, Validator};
use Component\Image\Validator\{DownloadValidator, UploadValidator};
use Model\{Album, AlbumPhoto, Photo};
use Phalcon\Http\Response;

class PhotoController extends BaseController
{
    /**
     * For viewing a photo by id.  Pretty much a debugging endpoint
     * @param mixed $photoId
     * @return never
     */
    public function indexAction($photoId)
    {
        $Photo = Photo::findFirst($photoId);

        if ($Photo == null) {
            exit("Photo does not exist");
        } else {
            $filePath = $this->config->dirs->file->photo . $Photo->display_path;

            $Response = new Response();
            $Response->setStatusCode(200, "OK");
            $Response->setContentType($Photo->mime_type);
            $Response->setContentLength(filesize($filePath));
            $Response->setContent(file_get_contents($filePath));
            $Response->send();
            exit();
        }
    }

    public function uploadAction()
    {
        $uploadedFiles = $this->request->getUploadedFiles();
        if (count($uploadedFiles) == 0) {
            $Retval = new Retval();
            return $Retval->message("No files were uploaded.")->response();
        }
        $albumId = $this->request->getPost("albumId");
        $File = $uploadedFiles[0];
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

    public function replaceAction()
    {
        $Retval = new Retval();

        $targetPhotoId = $this->request->getPost("targetPhotoId");
        $replacingPhotoId = $this->request->getPost("replacingPhotoId");

        $TargetPhoto = Photo::findFirst($targetPhotoId);
        $ReplacingPhoto = Photo::findFirst($replacingPhotoId);

        if($TargetPhoto == null)
            return $Retval
                ->success(false)
                ->message("Target photo doesn't exist.")
                ->response();
        if($ReplacingPhoto == null)
            return $Retval
                ->success(false)
                ->message("Replacing photo doesn't exist.")
                ->response();

        $TargetPhoto->copyForReplacement($ReplacingPhoto);

        $path = $this->config->dirs->file->photo . DIRECTORY_SEPARATOR . $TargetPhoto->path;
        if(file_exists($path))
            unlink($path);
        foreach ($this->config->image->versions as $version) {
            $path = $this->config->dirs->file->photo . DIRECTORY_SEPARATOR . $TargetPhoto->{$version->type . "_path"};
            if(file_exists($path))
                unlink($path);
        }

        $TargetPhoto->save();
        $ReplacingPhoto->delete();

        return $Retval->success(true)->response();
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

        $Path = new Path($File, $this->config->dirs->file->photo);
        if (!file_exists($Path->getFullDir())) {
            mkdir($Path->getFullDir(), 0777, true);
        }

        if (!$File->moveTo($Path->getFullPath())) {
            return $Retval->message("Unable to move the file to its final location.");
        }

        $Photo->path = $Path->getPath();

        $Image = new Image($Path->getFullPath());

        foreach ($this->config->image->versions as $version) {
            $path = $Path->getPath($version->suffix);
            $fullPath = $Path->getFullPath($version->suffix);
            $Image->resize($fullPath, $version->width, $version->height, $version->quality);
            [$resizedWidth, $resizedHeight] = getimagesize($fullPath);

            $Photo->{$version->type . "_path"} = $path;
            $Photo->{$version->type . "_width"} = $resizedWidth;
            $Photo->{$version->type . "_height"} = $resizedHeight;

            if ($version->type == "thumb") {
                $Photo->phash = Image::getPHash($fullPath);
            }
        }

        if (!$Photo->create()) {
            unlink($Path->getFullPath());
            foreach ($this->config->image->versions as $version) {
                unlink($Path->getFullPath($version->suffix));
            }
            $output = "Error creating the record:";
            $messages = $Photo->getMessages();
            $separator = count($messages) > 1 ? "\n" : " ";
            foreach ($Photo->getMessages() as $error) {
                $output .= $separator . $error;
            }

            return $Retval->message($output);
        }

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
