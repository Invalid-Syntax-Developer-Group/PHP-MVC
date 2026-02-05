<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Core\Application;
use PhpMVC\Validation\Manager;
use PhpMVC\Validation\Rule\EmailRule;
use PhpMVC\Validation\Rule\MinRule;
use PhpMVC\Validation\Rule\RequiredRule;

/**
 * Class ValidationProvider
 *
 * Service provider responsible for registering the validation
 * subsystem within the application container.
 *
 * This provider binds a shared {@see Manager} instance under the
 * `validator` alias and registers the core validation rules used
 * throughout the application.
 *
 * Each rule is bound into the container individually, allowing
 * rules to be resolved, replaced, or extended independently.
 *
 * @package PhpMVC\Provider
 * @since 1.0
 */
final class ValidationProvider
{
    /**
     * Register the validation manager with the application container.
     *
     * Binds a singleton-style `validator` service that exposes a
     * {@see Manager} instance preconfigured with the default
     * validation rules.
     *
     * @param Application $app The application container instance.
     *
     * @return void
     */
    public function bind(Application $app): void
    {
        $app->bind('validator', function($app) {
            $manager = new Manager();
    
            $this->bindRules($app, $manager);
    
            return $manager;
        });
    }

    /**
     * Bind and register validation rules.
     *
     * Each rule is first bound into the application container
     * under a namespaced alias, then registered with the
     * {@see Manager} using a short rule name.
     *
     * Registered rules:
     *  - required : {@see RequiredRule}
     *  - email    : {@see EmailRule}
     *  - min      : {@see MinRule}
     *
     * @param Application $app     The application container.
     * @param Manager     $manager The validation manager instance.
     *
     * @return void
     */
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
