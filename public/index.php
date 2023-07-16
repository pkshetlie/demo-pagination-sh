<?php

use App\Routing\UrlGenerator;
use App\Twig\RouterExtension;
use Doctrine\DBAL\DriverManager;
use Pkshetlie\PaginationDbal\Models\OrderModel;
use Pkshetlie\PaginationDbal\Service\PaginationManager;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

include '../vendor/autoload.php';

$loader = new FilesystemLoader(__DIR__.'/../src/template');
$_GET['_route'] = 'index.php';
$_GET['_route_params'] = [];
$request = Request::createFromGlobals();

$twig = new Environment($loader, [
    'cache' => false,
    'auto_reload' => true,
    'debug' => true,
]);
$twig->addExtension(new DebugExtension());
$urlGenerator = new UrlGenerator();
$twig->addExtension(new RouterExtension($urlGenerator, [], null));
$twig->addGlobal('app', [
    'request' => $request
]);

$dbal = DriverManager::getConnection([
    'url' => 'mysql://root:@127.0.0.1:3307/ragnacustoms?serverVersion=mariadb-10.6.5&charset=utf8mb4',
]);
$paginationManager = new PaginationManager($twig, $request, $urlGenerator);

# region real part of code we are going tu put in controller
$qb = $dbal->createQueryBuilder('s')->select('*')->from('song', 's')->setFirstResult(0)->setMaxResults(2);
try {
    $pagination = $paginationManager
        ->setQb($qb)
        ->ordering(
            new OrderModel([
                'title' => 's.name',
                'mapper' => 's.level_author_name'
            ])
        )
        ->process();

    if ($pagination->isPartial()) {
        echo $twig->render('partial/rows.html.twig', [
            'songs' => $pagination
        ]);
    } else {
        echo $twig->render('index.html.twig', [
            'songs' => $pagination
        ]);
    }
}catch(\Exception $O_o){
    echo $O_o->getMessage();
}
#endregion
