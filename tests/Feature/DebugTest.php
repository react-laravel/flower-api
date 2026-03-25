<?php
namespace Tests\Feature;
use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
class DebugTest extends TestCase
{
    use RefreshDatabase;
    public function test_debug(): void
    {
        $this->artisan('migrate');
        $flower = Flower::create(['name' => 'Test', 'name_en' => 'Test', 'category' => 'rose', 'price' => 10, 'user_id' => null]);
        
        $response = $this->getJson('/api/flowers');
        $data = $response->json();
        
        error_log(print_r($data, true));
        error_log("Has items key: " . (isset($data['data']['items']) ? 'yes' : 'no'));
        error_log("Keys in data: " . implode(', ', array_keys($data['data'] ?? [])));
        
        $this->assertTrue(true);
    }
}
