<?php
namespace Lcobucci\Common\ClassLoader;

use \Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotationReadyClassLoader extends SplClassLoader
{
    /**
     * @see \Lcobucci\Common\ClassLoader\SplClassLoader::register()
     */
    public function register()
    {
        parent::register();
        AnnotationRegistry::registerLoader(array($this, 'loadClass'));
    }
}
