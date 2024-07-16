<?php

namespace Helper;

class IniHelper
{
    /**
     * Get the integer equivalent of the size of the largest file that can be uploaded, according to php.ini
     *
     * @return int
     */
    public static function getUploadMaxFilesize(): int
    {
        $uploadIni = trim(ini_get("upload_max_filesize"));
        // source: https://stackoverflow.com/a/19570313/251859
        $uploadInt = intval($uploadIni) *
            (['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10 ][strtolower(substr($uploadIni, -1))] ?: 1);

        $postIni = trim(ini_get("post_max_size"));
        // source: https://stackoverflow.com/a/19570313/251859
        $postInt = intval($postIni) *
            (['g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10 ][strtolower(substr($uploadIni, -1))] ?: 1);

        return min($uploadInt, $postInt);
    }
}
