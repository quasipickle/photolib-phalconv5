<?php

namespace Helpers;

class ViewHelper
{
    public function __construct(private \Phalcon\Mvc\Url $urlService, private \Phalcon\Config\Config $config)
    {
    }

    public function albumUrl(int $albumId): string
    {
        return $this->urlService->get("/album/" . $albumId);
    }

    public function photoUrl(string $photoPath): string
    {
        return $this->config->dirs->web->photo . $photoPath;
    }

    public function icon(string $classes): string
    {
        return "<i class = \"bi $classes\"></i>";
    }

    public static function filesize(int $size): string
    {
        $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB'); 
        
        $bytes = max($size, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
    
        $bytes /= pow(1024, $pow);

        $precision = $size <= 1_024_000 ? 0 : 2;
    
        return round($bytes, $precision) . ' ' . $units[$pow]; 
    }
}