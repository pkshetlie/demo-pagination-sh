<?php

use Doctrine\DBAL\DriverManager;
use Pkshetlie\PaginationDbal\Service\PaginationManager;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

include '../vendor/autoload.php';

$loader = new FilesystemLoader(__DIR__ . '/../src/template');
$_GET['_route'] = 'index.php';
$_GET['_route_params'] = [];
$request = Request::createFromGlobals();

$twig = new Environment($loader, [
    'cache'       => false,
    'auto_reload' => true,
    'debug'       => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addExtension(new \App\Twig\RouterExtension(new \App\Routing\UrlGenerator(),[],null));
$twig->addGlobal('app', [
    'request'=> $request
]);

$dbal = DriverManager::getConnection([
    'url' => 'mysql://root:@127.0.0.1:3307/ragnacustoms?serverVersion=mariadb-10.6.5&charset=utf8mb4',
]);

$qb = $dbal->createQueryBuilder('s')->select('*')->from('song','s')->setFirstResult(0)->setMaxResults(2);

$paginationManager = new PaginationManager($twig);

$pagination = $paginationManager->process($qb, $request);

if($pagination->isPartial()){
    echo $twig->render('partial/rows.html.twig', [
        'songs' => $pagination
    ]);
}else {
    echo $twig->render('index.html.twig', [
        'songs' => $pagination
    ]);
}
