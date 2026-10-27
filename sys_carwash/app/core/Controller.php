<?php
class Controller {
    protected function view(string $view, array $data = [], string $layout = 'main'): void {
        $data['view'] = $view;
        extract($data);
        $viewFile = APP_PATH . '/views/' . $view . '.php';
        if ($layout === 'none') { require $viewFile; return; }
        require APP_PATH . '/views/layout/' . $layout . '.php';
    }
    protected function redirect(string $path): void { header('Location: ' . url($path)); exit; }
    protected function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    protected function requireLogin(): void {
        if (!auth_user()) $this->redirect('auth/login');
    }
    protected function requireModule(string $mod): void {
        $this->requireLogin();
        if (!can($mod)) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
    }
    public function viewPublic404(): void { $this->view('errors/404', ['title' => 'No encontrado'], auth_user() ? 'main' : 'none'); exit; }
    protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
}
