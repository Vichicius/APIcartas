<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class crearCarta extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_crearCarta_no_autorizado()
    {
        $response = $this->putJson('/api/crearCarta',
            [
                'api_token' => '$2y$10$phLmyR6wRtU1hy3zwydXPuEAVzpKT67IYu5tM6X3R1pkR9ci0Efzm',
                'name' => 'mana rojo',
                'description' => 'da 2 de mana',
                'collection' => 1
            ]);

        $response->assertStatus(200)
        ->assertJson([
            'status' => 0,
        ]);
    }
    public function test_crearCarta_datos_vacios()
    {
        $response = $this->putJson('/api/crearCarta',
            [
                'api_token' => '$2y$10$5AP7AiGrSYpdcOIEuBv5yOc6gAL.WhdgSmlbi029TPleUkk6bUX4i',
                'name' => '',
                'description' => '',
                'collection' => ''
            ]);

        $response->assertStatus(200)
        ->assertJson([
            'status' => 0,
        ]);
    }
    public function test_crearCarta_coleccion_mal()
    {
        $response = $this->putJson('/api/crearCarta',
            [
                'api_token' => '$2y$10$5AP7AiGrSYpdcOIEuBv5yOc6gAL.WhdgSmlbi029TPleUkk6bUX4i',
                'name' => 'mana rojo',
                'description' => 'da 2 de mana',
                'collection' => 5454
            ]);

        $response->assertStatus(200)
        ->assertJson([
            'status' => 0,
        ]);
    }
    public function test_crearCarta_correcto()
    {
        $response = $this->putJson('/api/crearCarta',
            [
                'api_token' => '$2y$10$5AP7AiGrSYpdcOIEuBv5yOc6gAL.WhdgSmlbi029TPleUkk6bUX4i',
                'name' => 'mana rojo',
                'description' => 'da 2 de mana',
                'collection' => 1
            ]);

        $response->assertStatus(200)
        ->assertJson([
            'status' => 1,
        ]);
    }
}
