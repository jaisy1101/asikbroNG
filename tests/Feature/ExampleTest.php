<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman aplikasi hanya untuk user yang sudah login.
     */
    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_halaman_login_dapat_dibuka(): void
    {
        $this->get('/login')->assertOk();
    }
}
