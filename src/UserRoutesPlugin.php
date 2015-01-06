<?php namespace Prontotype\Plugins\UserRoutes;

use Prontotype\Container;
use League\Event\Event;
use Prontotype\Plugins\AbstractPlugin;
use Prontotype\Plugins\PluginInterface;

class UserRoutesPlugin extends AbstractPlugin implements PluginInterface
{
    public function getConfig()
    {
        return 'config/config.yml';
    }

    public function register()
    {
        $conf = $this->container->make('prontotype.config');
        
        $this->buildUserRoutes($this->container->make('prontotype.http'), $conf->get('routes') ?: array());

        $this->container->make('prontotype.events')->emit(Event::named('userRoutes.registered'));
    }

    public function buildUserRoutes($handler, $routes)
    {
        $translatedRoutes = array();
        foreach($routes as $routeName => $route) {
            $segments = explode('/', trim($route['match'],'/'));
            $cleanSegments = array();
            $params = array();
            foreach($segments as $segment) {
                if ( strpos($segment, '{') === false ) {
                    $cleanSegments[] = $segment;
                } else {
                    @list($name, $assert, $default) = explode(':', str_replace(array('{','}'), '', $segment));
                    $cleanSegments[] = '{' . $name . '}';
                    $params[$name] = array(
                        'name' => $name,
                        'assert' => $assert,
                        'default' => $default
                    );
                }
            }
            $routePath = '/' . implode('/', $cleanSegments);
            $controller = isset($route['controller']) ? $route['controller'] : 'Prontotype\Http\Controllers\DefaultController::catchall';
            $templatePath = isset($route['template']) ? $route['template'] : null;
            $userRoute = $handler->get($routePath, $controller)->name($routeName)->value('templatePath', $templatePath);
            foreach($params as $paramSet) {
                if ($paramSet['assert']) {
                    $userRoute->assert($paramSet['name'], $paramSet['assert']);
                }
                if ($paramSet['default']) {
                    $userRoute->value($paramSet['name'], $paramSet['default']);
                }
            }
        }
    }

}