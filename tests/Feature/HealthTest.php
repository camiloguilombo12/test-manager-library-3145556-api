<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_ok(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_unknown_route_returns_contract_error_body(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertExactJson([
                'code' => 'NOT_FOUND',
                'message' => 'El recurso no existe.',
                'details' => [],
            ]);
    }

    public function test_wrong_method_returns_contract_error_body(): void
    {
        $this->postJson('/api/v1/health')
            ->assertStatus(405)
            ->assertJsonPath('code', 'METHOD_NOT_ALLOWED')
            ->assertJsonPath('details', []);
    }
}
