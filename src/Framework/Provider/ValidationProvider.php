<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Application;
use PhpMVC\Framework\Validation\Manager;
use PhpMVC\Framework\Validation\Rule\EmailRule;
use PhpMVC\Framework\Validation\Rule\MinRule;
use PhpMVC\Framework\Validation\Rule\RequiredRule;

final class ValidationProvider
{
    public function bind(Application $app): void
    {
        $app->bind('validator', function($app) {
            $manager = new Manager();
    
            $this->bindRules($app, $manager);
    
            return $manager;
        });
    }

    private function bindRules(Application $app, Manager $manager): void
    {
        $app->bind('validation.rule.required', fn() => new RequiredRule());
        $app->bind('validation.rule.email', fn() => new EmailRule());
        $app->bind('validation.rule.min', fn() => new MinRule());

        $manager->addRule('required', $app->resolve('validation.rule.required'));
        $manager->addRule('email', $app->resolve('validation.rule.email'));
        $manager->addRule('min', $app->resolve('validation.rule.min'));
    }
}