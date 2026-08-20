<?php

require_once APP_ROOT . '/app/core/Controller.php';

class PengurusController extends Controller
{
    public function index(): void
    {
        $this->guard(['Administrator']);
        redirect('profil');
    }
}
