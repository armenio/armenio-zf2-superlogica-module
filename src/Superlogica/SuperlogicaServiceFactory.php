<?php
/**
 * Rafael Armenio <rafael.armenio@gmail.com>
 *
 * @link http://github.com/armenio for the source repository
 */
 
namespace Armenio\Superlogica;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 *
 * SuperlogicaServiceFactory
 * @author Rafael Armenio <rafael.armenio@gmail.com>
 *
 *
 */
class SuperlogicaServiceFactory implements FactoryInterface
{
    /**
     * zend-servicemanager v2 factory for creating Superlogica instance.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @returns Superlogica
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $superlogica = new Superlogica();
        return $superlogica;
    }
}
