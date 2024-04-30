<?php

$Container->setShared("viewHelper", function () use ($Container, $Config) {
    return new \Helper\ViewHelper($Container->get("url"), $Config);
});
$Container->setShared("voltService", function (Phalcon\Mvc\View $View) use ($Container, $Config) {
    $Volt = new Phalcon\Mvc\View\Engine\Volt($View, $Container);
    $Volt->setOptions([
        "always" => $Config->view->compileAlways,
        "path" => function (string $path) use ($Config): string {
            $relative_path = substr($path, strlen($Config->dirs->file->views));

            $compile_dir = $Config->dirs->file->viewsCompiled . dirname($relative_path);
            $compile_path = $compile_dir . "/" . basename($path);
            if (!is_dir($compile_dir)) {
                mkdir($compile_dir, 0777, true);
            }
            return $compile_path;
        }
    ]);

    $Compiler = $Volt->getCompiler();
    $Compiler->addFunction("filesize", function ($resolvedArgs, $exprArgs) use ($Compiler) {
        $size = $Compiler->expression($exprArgs[0]['expr']);

        return "\Helper\ViewHelper::filesize(" . $size . ")";
    });

    $Compiler->addFunction("icon", function ($resolvedArgs, $exprArgs) use ($Compiler) {
        $icon = $Compiler->expression($exprArgs[0]['expr']);
        return '$this->viewHelper->icon(' . $icon . ')';
    });

    $Compiler->addFunction("album", function ($resolvedArgs, $exprArgs) use ($Compiler) {
        $albumId = $Compiler->expression($exprArgs[0]['expr']);
        return '$this->viewHelper->albumUrl(' . $albumId . ')';
    });

    $Compiler->addFunction("photo", function ($resolvedArgs, $exprArgs) use ($Compiler) {
        $path = $Compiler->expression($exprArgs[0]['expr']);
        return '$this->viewHelper->photoUrl(' . $path . ')';
    });

    $Compiler->addFunction("formatDatetime", function($resolvedArgs, $exprArgs) use ($Compiler){
        $dateString = $Compiler->expression($exprArgs[0]['expr']);
        return '$this->viewHelper->formatDatetime(' . $dateString . ')';
    });

    return $Volt;
});
$Container->setShared("view", function () use ($Config) {
    $View = new Phalcon\Mvc\View();
    $View->setViewsDir($Config->dirs->file->views);
    $View->registerEngines(
        [
            ".phtml" => "voltService"
        ]
    );
    return $View;
});
