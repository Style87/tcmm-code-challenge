<?php

use App\Exceptions\Http404Exception;
use Phalcon\Mvc\Micro\Collection;

// NotesController
$controller = new Collection();
$controller->setHandler('\App\Controllers\NotesController', true);
$controller->setPrefix('/notes');
$controller->get('', 'get');
$controller->get('/{id:[0-9]+}', 'get');
$controller->post('', 'post');
$controller->put('/{id:[0-9]+}', 'put');
$controller->delete('/{id:[0-9]+}', 'delete');
$app->mount($controller);

// TagsController
$controller = new Collection();
$controller->setHandler('\App\Controllers\TagsController', true);
$controller->setPrefix('/tags');
$controller->get('', 'get');
$controller->get('/{id:[0-9]+}', 'get');
$controller->post('', 'post');
$controller->put('/{id:[0-9]+}', 'put');
$controller->delete('/{id:[0-9]+}', 'delete');
$app->mount($controller);

// not found URLs
$app->notFound(
  function () use ($app) {
    trigger_error('URI not found: ' . $app->request->getMethod() . ' ' . $app->request->getURI());
    throw new Http404Exception('URI not found: ' . $app->request->getMethod() . ' ' . $app->request->getURI());
  }
);
