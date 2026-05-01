<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    private const SESSION_TIMEOUT = 1800;

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ($session->get('logged_in') !== true) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $lastActivity = (int) $session->get('last_activity');
        if ($lastActivity > 0 && (time() - $lastActivity) > self::SESSION_TIMEOUT) {
            $session->destroy();

            return redirect()->to('/login')->with('error', 'Session berakhir. Silakan login kembali.');
        }

        $session->set('last_activity', time());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed.
    }
}
