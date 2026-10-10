<?php

namespace App\Controllers;

use App\Router\Request;
use App\Support\Auth;
use App\Support\Flash;

class AuthController
{
    /** GET /login — show the form */
    public function showLogin(Request $request): void {
        if (Auth::check()) {
            redirect(url('/'));
        }

        view('auth/login', [
            'pageTitle'   => 'Sign in',
            'currentPage' => 'login',
            'errors'      => [],
            'old'         => [],
        ]);
    }

    /** POST /login — process credentials */
    public function login(Request $request): void {
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if (!$errors && !Auth::attempt($email, $password)) {
            // Deliberately vague — don't reveal whether the email exists
            $errors['_global'] = 'Invalid email or password.';
        }

        if ($errors) {
            // Keep the email, drop the password
            $old = ['email' => $email];

            view('auth/login', [
                'pageTitle'   => 'Sign in',
                'currentPage' => 'login',
                'errors'      => $errors,
                'old'         => $old,
            ]);
            return;
        }

        // Success — go where they were headed, or home
        $intended = $_SESSION['_intended'] ?? null;
        unset($_SESSION['_intended']);

        Flash::set('success', 'Welcome back.');
        redirect($intended ?: url('/'));
    }

    /** POST /logout */
    public function logout(Request $request): void
    {
        Auth::logout();
        Flash::set('success', 'You have been signed out.');
        redirect(url('/'));
    }
}