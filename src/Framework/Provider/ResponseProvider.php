<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Application;
use PhpMVC\Framework\Http\Response;

final class ResponseProvider
{
    public function bind(Application $app): void
    {
        $app->bind('response', function($app) {
            return new Response();
        });
    }
}