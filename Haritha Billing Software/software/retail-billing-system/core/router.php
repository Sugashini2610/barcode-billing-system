<?php
/**
 * Router - PHP 5.6 compatible
 */
class Router
{
    private $routes = array();

    public function get($path, $view)
    {
        $this->routes['GET'][$path] = $view;
    }

    public function post($path, $handler)
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $url = trim($url, '/');

        // Handle logout
        if ($url === 'logout') {
            Auth::logout();
            redirect(BASE_URL . '/login');
            return;
        }

        // Handle POST login
        if ($url === 'login' && $method === 'POST') {
            $this->handleLogin();
            return;
        }

        // Check authentication for all non-login routes
        if ($url !== 'login') {
            Auth::requireLogin();
        } else {
            Auth::requireGuest();
        }

        $view = isset($this->routes['GET'][$url]) ? $this->routes['GET'][$url] : '404';
        $this->renderView($view);
    }

    private function handleLogin()
    {
        $username = sanitize(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (Auth::attempt($username, $password)) {
            Session::flash('success', 'Welcome back, ' . $username . '!');
            redirect(BASE_URL . '/dashboard');
        } else {
            Session::flash('error', 'Invalid username or password.');
            redirect(BASE_URL . '/login');
        }
    }

    private function renderView($view)
    {
        $viewPath = BASE_PATH . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            $this->render404();
            return;
        }
        include $viewPath;
    }

    private function render404()
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html><body><h1>404 - Page Not Found</h1></body></html>';
    }
}
