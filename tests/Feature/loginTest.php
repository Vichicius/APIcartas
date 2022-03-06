<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class loginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_login_correcto()
    {
        $response = $this->putJson('/api/login', ['nickname' => 'luis', 'password' => 'Luis123$']);

        $response->assertStatus(200)
        ->assertJson([
            'status' => 1,
        ]);
    }

    public function test_login_usuario_mal()
    {
        $response = $this->putJson('/api/login', ['nickname' => 'eduardo289', 'password' => 'Luis123$']);

        $response->assertStatus(200)
        ->assertJson([
            'status' => 0,
        ]);
    }

    public function test_login_contrasena_mal()
    {
        $response = $this->putJson('/api/login', ['nickname' => 'admin', 'password' => 'unaquenoeXiste3$']);

        $response->assertStatus(200)
        ->assertJson([
            'status' => 0,
        ]);
    }
}
