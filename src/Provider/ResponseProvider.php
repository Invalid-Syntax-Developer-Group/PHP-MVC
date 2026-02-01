<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Application;
use PhpMVC\Http\Response;

final class ResponseProvider
{
    public function bind(Application $app): void
    {
        $app->bind('response', function($app) {
            return new Response();
        });
    }
}