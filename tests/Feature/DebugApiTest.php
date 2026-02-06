<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_unauthenticated(): void
    {
        $this->withoutExceptionHandling();

        try {
            $response = $this->getJson('/api/orders');
            echo "\nStatus: ".$response->status()."\n";
            echo 'Content: '.$response->content()."\n";
        } catch (\Exception $e) {
            echo "\nException: ".get_class($e)."\n";
            echo 'Message: '.$e->getMessage()."\n";
            echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
        }

        $this->assertTrue(true);
    }
}
