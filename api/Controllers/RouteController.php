<?php
namespace PhpDraft\Controllers;
				
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Silex\Application;
# .......

/**
 * @Route("profile", service="application_frontend.controller.profile")
 */
class RouteController
{

function ListRoutes(Application $app) {

        $output = new ConsoleOutput();
    
    $table = new Table($output);
    $table->setStyle('borderless');
    $table->setHeaders(array(
        'methods',
        'path'
    ));
    foreach ($app['routes'] as $route) {
        $table->addRow(array(
            implode('|', $route->getMethods()),
            $route->getPath(),
        ));
    }
    $table->render();
}

}