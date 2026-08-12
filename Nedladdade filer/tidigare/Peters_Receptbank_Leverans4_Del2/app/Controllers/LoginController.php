<?php
declare(strict_types=1);

class LoginController extends Controller
{
    public function index(): void
    {
        $this->view('login');
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('index.php?page=login');
    }
}
