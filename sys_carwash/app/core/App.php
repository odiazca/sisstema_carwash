<?php
class App {
    public function run(): void {
        $url = trim($_GET['url'] ?? '', '/');
        $parts = $url === '' ? [] : explode('/', $url);
        $name = preg_replace('/[^a-z_]/i', '', $parts[0] ?? '');
        if ($name === '') $name = auth_user() ? 'dashboard' : 'auth';
        $method = preg_replace('/[^a-z_0-9]/i', '', $parts[1] ?? 'index');
        $args = array_slice($parts, 2);

        $class = ucfirst($name) . 'Controller';
        $file = APP_PATH . '/controllers/' . $class . '.php';
        if (!is_file($file)) $this->notFound();
        require_once $file;
        $ctrl = new $class();
        if (!method_exists($ctrl, $method) || str_starts_with($method, '_')) $this->notFound();
        $ref = new ReflectionMethod($ctrl, $method);
        if (!$ref->isPublic()) $this->notFound();
        call_user_func_array([$ctrl, $method], $args);
    }
    private function notFound(): void {
        http_response_code(404);
        (new Controller())->viewPublic404();
    }
}
