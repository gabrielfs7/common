<?php
namespace Lcobucci\Common\DependencyInjection;

use \Symfony\Component\Config\FileLocator;
use \Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ContainerBuilder
{
    /**
     * @var string
     */
    private $baseClass;

    /**
     * @var string
     */
    private $cacheDirectory;

	/**
	 * @param string $file
	 * @param array $path
	 * @return \Symfony\Component\DependencyInjection\ContainerBuilder
	 */
	public static function build(
        $file,
        array $path = array(),
        $baseClass = null,
        $cacheDirectory = null
    ) {
		$builder = new self();
	    $builder->cacheDirectory = $cacheDirectory ?: sys_get_temp_dir();
	    $builder->cacheDirectory = rtrim($builder->cacheDirectory, '/');

		if ($baseClass !== null) {
		    $builder->baseClass = $baseClass;
		}

		return $builder->getContainer(realpath($file), $path);
	}

	/**
	 * @param string $file
	 * @param array $path
	 * @return \Symfony\Component\DependencyInjection\ContainerBuilder
	 */
	public function getContainer($file, array $path = array())
	{
		$dumpClass = $this->createDumpClassName($file);

		if ($this->hasToCreateDumpClass($file, $dumpClass)) {
			$container = new ContainerBuilder();

			$this->getLoader($container, $path)->load($file);
			$this->createDump($container, $dumpClass);
		}

		return $this->loadFromDump($dumpClass);
	}

	/**
	 * @param string $file
	 * @return string
	 */
	protected function createDumpClassName($file)
	{
		return 'Project' . md5($file) . 'ServiceContainer';
	}

	/**
	 * @param string $className
	 * @return string
	 */
	protected function getDumpFileName($className)
	{
		return $this->cacheDirectory . '/' . $className . '.php';
	}

	/**
	 * @param string $file
	 * @param string $className
	 * @return boolean
	 */
	protected function hasToCreateDumpClass($file, $className)
	{
		$dumpFile = $this->getDumpFileName($className);

		if (file_exists($dumpFile) && filemtime($dumpFile) >= filemtime($file)) {
			return false;
		}

		return true;
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @param string $className
	 */
	protected function createDump(ContainerBuilder $container, $className)
	{
	    $config = array('class' => $className);

	    if ($this->baseClass !== null) {
	        $config['base_class'] = $this->baseClass;
	    }

		$dumper = new PhpDumper($container);
		$dumpFile = $this->getDumpFileName($className);

		file_put_contents(
			$dumpFile,
			$dumper->dump($config)
		);

		chmod($dumpFile, 0777);
	}

	/**
	 * @param string $className
	 * @return \Symfony\Component\DependencyInjection\Container
	 */
	protected function loadFromDump($className)
	{
		require_once $this->getDumpFileName($className);
		$className = '\\' . $className;

		return new $className();
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @return \Symfony\Component\DependencyInjection\Loader\XmlFileLoader
	 */
	protected function getLoader(ContainerBuilder $container, array $path)
	{
		return new XmlFileLoader(
			$container,
			new FileLocator($path)
		);
	}
}