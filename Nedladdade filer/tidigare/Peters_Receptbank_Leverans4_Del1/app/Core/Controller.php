<?php
/**
 * Peters Receptbank
 * app/Core/Controller.php
 * Basklass för alla controllers.
 */

declare(strict_types=1);

abstract class Controller
{
    /**
     * Ladda en vy.
     *
     * @param string $view
     * @param array $data
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $file = APP_PATH . '/Views/' . $view . '.php';

        if (!file_exists($file)) {
            throw new Exception('Vyn kunde inte hittas: ' . $view);
        }

        require $file;
    }

    /**
     * Omdirigera till annan sida.
     *
     * @param string $url
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Kontrollera om användaren är inloggad.
     *
     * @return bool
     */
    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Kräver inloggning.
     */
    protected function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('index.php?page=login');
        }
    }
}
