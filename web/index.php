<?php
require_once __DIR__.'/../vendor/autoload.php';

/*use \Symfony\Component\Debug\ErrorHandler;
use \Symfony\Component\Debug\ExceptionHandler;

ini_set('display_errors', 1);
error_reporting(-1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ErrorHandler::register();
if ('cli' !== PHP_SAPI) {
	ExceptionHandler::register();
}*/

$app = new Silex\Application();

//$app['debug'] = true;

spl_autoload_register(function ($class_name) {
	$filename = __DIR__ . '/../' . $class_name . '.php';
	if (file_exists($filename)) include $filename;
});

$app->register(new Silex\Provider\DoctrineServiceProvider(), [
	'db.options' => [
		'driver'     => 'pdo_mysql',
		'dbname'     => 'pomodoro',
		'host'       => 'localhost',
		'user'       => 'pomodoro',
		'password'   => '123',
		'charset'    => 'utf8'
	],
]);

$app->register(new Silex\Provider\TwigServiceProvider(), [
	'twig.path' => __DIR__.'/../views',
]);

$app['timers.service'] = new TimersService($app['db']);

$app->get('/', function() use ($app) {
	return $app['twig']->render('index.twig', [
		'days' => $app['timers.service']->getCompletedByDay()
	]);
});

$app->mount('/api', new TimersController($app['timers.service']));

$app->run();