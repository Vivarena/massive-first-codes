<?php
/**
* Created by Slava Basko
* Email: basko.slava@gmail.com
* Date: 5/13/13
* Time: 4:22 PM
*/

App::import('Utility', 'Cache');

class MegaRouter {

    public $controllers = array();

    public $cache = null;

    private $routes = array();

    public function __construct() {
        $c_routes = Cache::read('routes');
        if(!$c_routes) {
            foreach (new \GlobIterator(ROOT.DS.APP_DIR.DS.'controllers/*.php') as $file) {
                $data = $this->reflection($file->getPathname());
                foreach ($data as $method => $node) {
                    if (isset($node['route'])) {
                        $route = explode('-->', $node['route'][0]);
                        $defaults = $this->toRouteData($route[1]);
                        $options = array();
                        $this->routes[trim($route[0])]['defaults'] = $route[1];
                        if(isset($route[2])) {
                            $options = $this->toRouteData($route[2]);
                            $this->routes[trim($route[0])]['options'] = $route[2];
                        }
                        Router::connect(trim($route[0]), $defaults, $options);
                        $this->AfterInsert();
                    }
                }
            }
            Cache::write('routes', $this->routes);
        }else {
            foreach ($c_routes as $route => $params) {
                $defaults = $this->toRouteData($params['defaults']);
                $options = array();
                if(isset($params['options'])) {
                    $options = $this->toRouteData($params['options']);
                }
                Router::connect($route, $defaults, $options);
                $this->AfterInsert();
            }
        }
    }

    private function AfterInsert() {
        $inst =& Router::getInstance();
        $last_route = end($inst->routes);
        $tmp = array_pop($inst->routes);
        array_unshift($inst->routes, $last_route);
        return true;
    }

    private function toRouteData($data) {
        $arr = json_decode(trim($data), true);
        return $arr;
    }

    public function reflection($file)
    {
        if (true) {
            $tmp = substr(end(explode(DS, $file)), 0, -4);
            $tmp = explode('_', $tmp);
            $class = '';
            foreach ($tmp as $node) {
                $class .= ucfirst($node);
            }

            if(!class_exists('AppController')) {
                include ROOT.DS.APP_DIR.DS.'app_controller.php';
            }

            if(!class_exists($class)) {
                include $file;
            }

            $reflection = new \ReflectionClass($class);

            $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC |  ReflectionMethod::IS_PROTECTED);

            $methods = json_decode(json_encode($methods), true);

            // init data
            $data = array();

            foreach ($methods as $key => $node) {
                $reflection_method = new \ReflectionMethod($class, $node['name']);
                // check and normalize params by doc comment
                $docComment = $reflection_method->getDocComment();
                preg_match_all('/\s*\*\s*\@param\s+(bool|boolean|int|integer|float|string|array)\s+\$([a-z0-9_]+)/i', $docComment, $matches);

                // rebuild array
                $data[$node['name']]['types'] = array();
                foreach ($matches[1] as $i => $type) {
                    $data[$node['name']]['types'][$matches[2][$i]] = $type;
                }

                // get params and convert it to simple array
                $params = $reflection_method->getParameters();
                $values = array();
                foreach ($params as $key => $param) {
                    $params[$key] = $param->getName();
                    if ($param->isOptional()) {
                        $values[$key] = $param->getDefaultValue();
                    }
                }
                $data[$node['name']]['params'] = $params;
                $data[$node['name']]['values'] = $values;

                // check routers
                if (preg_match_all('/\s*\*\s*\@route\s+(.*)\s*/i', $docComment, $matches)) {
                    $data[$node['name']]['route'] = $matches[1];
                }
            }
            return $data;
        }
        return false;
    }

}