<?php
require_once 'vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony' => __DIR__.'/vendor',
	'Monolog' => __DIR__.'/vendor/monolog/src',
	'D2E' => __DIR__.'/src',
));
$loader->register();
