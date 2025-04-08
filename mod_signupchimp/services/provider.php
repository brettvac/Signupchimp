<?php
/**
 * @package    Sign Up Chimp Module
 * @license    GNU General Public License version 2
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\Module as ModuleServiceProvider;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory as ModuleDispatcherFactoryServiceProvider;
use Joomla\CMS\Extension\Service\Provider\HelperFactory as HelperFactoryServiceProvider;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class() implements ServiceProviderInterface {
    public function register(Container $container): void  {
        $container->registerServiceProvider(new ModuleDispatcherFactoryServiceProvider('\\Naftee\\Module\\Signupchimp'));
        $container->registerServiceProvider(new HelperFactoryServiceProvider('\\Naftee\\Module\\Signupchimp\\Site\\Helper'));
        $container->registerServiceProvider(new ModuleServiceProvider());
    }
};