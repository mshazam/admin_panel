<?php
/**
 * Created by IntelliJ IDEA.
 * User: vds
 * Date: 11/07/2016
 * Time: 09.31
 */

namespace Ssslim\Libraries {

    use Ssslim\Controllers\Admin;
    use Ssslim\Controllers\PublicSite;
    use Ssslim\Core\Libraries\Router\Route;
    use Ssslim\Core\Libraries\Router\Router;
    use Ssslim\Core\Libraries\Router\RouterException;

    class Routes
    {
        private $router;
        private $load;
        /** @var  Route */
        private $route;

        public function __construct(\Loader $load)
        {
            $this->load = $load;
        }

        private function standardMethodInvoke($controllerInstance, $args)
        {
            $segs = explode('/', trim($args, '/'));

            if (!method_exists($controllerInstance, $segs[0])) throw(new RouterException());
            else return call_user_func_array([$controllerInstance, $segs[0]], array_slice($segs, 1));
        }

        public function doRoute()
        {
            $this->router = new Router();
            $this->router->basePath("/vfs_hr_dev/");

            $this->router->bind("/", function ($route) {
                $c = $this->load->getPublicSite();
                $c->home();
            });

            $this->router->bind("clear-cookie", function ($route) {
                $c = $this->load->getPublicSite();
                $c->clearCookie();
            });

            $this->router->bind("/dashboard[/{args:.*}]", function ($route) {
                $c = $this->load->getAdmin();
                $this->standardMethodInvoke($c, $route->params['args'] ?: 'dashboard');
            });

            // command line execution
            if (isset($_SERVER['argv']) && !isset($_SERVER['SERVER_NAME'])) {
                $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // CLI, fake server address
                $args = $_SERVER['argv'];
                $this->route = $this->router->route('/'.implode('/', array_slice($args, 1)), 'GET');
            }
            else $this->route = $this->router->route($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']); // web server execution

            try {
                $this->route->dispatch();
            } catch (\Exception $e) {
                show_404();
            }
        }
    }
}

namespace {
    // HELPER FUNCTIONS

    function site_url($uri = '')
    {
        return getLoader()->getConfig()->site_url($uri);
    }

// ------------------------------------------------------------------------

    function base_url()
    {
        return getLoader()->getConfig()->slash_item('base_url');
    }
}
