<?php

namespace Tests\Feature\API;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('admin123')
        ]);
        
        $this->token = auth('api')->attempt([
            'email' => 'test@example.com',
            'password' => 'admin123'
        ]);
    }

    public function test_post_data_to_endpoint_with_jwt()
    {
        Storage::fake('public');

        $fileImage = UploadedFile::fake()->image('test.jpg', 400, 400);
        $fileVideo = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $media = [
            'name' => $this->faker->name,
            'file' => rand(0, 1) ? $fileImage : $fileVideo,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/media", $media);

        $responseData = $response->json();

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'path',
                    'disk',
                    'mime_type',
                    'size',
                    'created_at',
                    'updated_at'
                ]
            ]);

        Storage::disk('public')->assertExists($responseData['data']['path']);

        $this->assertDatabaseHas('media', [
            'id' => $responseData['data']['id'],
            'name' => $media['name'],
            'path' => $responseData['data']['path'],
            'disk' => 'public'
        ]);

        $this->assertEquals($media['name'], $responseData['data']['name']);
        $this->assertNotNull($responseData['data']['path']);
        $this->assertEquals('public', $responseData['data']['disk']);
        $this->assertNotNull($responseData['data']['mime_type']);
        $this->assertNotNull($responseData['data']['size']);
    }

    public function test_get_data_from_endpoint_with_jwt()
    {
        Storage::fake('public');

        $fileImage1 = UploadedFile::fake()->image('test1.jpg', 400, 400);
        $mediaData1 = [
            'name' => $this->faker->name,
            'file' => $fileImage1,
        ];

        $postResponse1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/media", $mediaData1);

        $postResponse1->assertStatus(201);

        $fileImage2 = UploadedFile::fake()->image('test2.jpg', 400, 400);
        $mediaData2 = [
            'name' => $this->faker->name,
            'file' => $fileImage2,
        ];

        $postResponse2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/media", $mediaData2);

        $postResponse2->assertStatus(201);

        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/media");

        $getResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'path',
                        'disk',
                        'mime_type',
                        'size',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $getResponse->assertJsonCount(2, 'data');

        $responseData = $getResponse->json('data');

        $foundMedia1 = collect($responseData)->first(function ($file) use ($mediaData1) {
            return $file['name'] === $mediaData1['name'];
        });

        $foundMedia2 = collect($responseData)->first(function ($file) use ($mediaData2) {
            return $file['name'] === $mediaData2['name'];
        });

        $this->assertNotNull($foundMedia1, 'First uploaded media not found in response');
        $this->assertNotNull($foundMedia2, 'Second uploaded media not found in response');

        Storage::disk('public')->assertExists($foundMedia1['path']);
        Storage::disk('public')->assertExists($foundMedia2['path']);

        foreach ([$foundMedia1, $foundMedia2] as $media) {
            $this->assertEquals('public', $media['disk']);
            $this->assertNotEmpty($media['id']);
            $this->assertNotEmpty($media['path']);
            $this->assertNotNull($media['mime_type']);
            $this->assertNotNull($media['size']);
        }
    }

    public function test_destroy_data_to_endpoint_with_jwt()
    {
        Storage::fake('public');

        $fileImage = UploadedFile::fake()->image('test.jpg', 400, 400);
        $fileVideo = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $media = [
            'name' => $this->faker->name,
            'file' => rand(0, 1) ? $fileImage : $fileVideo,
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/media", $media);

        $createdMedia = $createResponse->json('data');

        Storage::disk('public')->assertExists($createdMedia['path']);

        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/media/{$createdMedia['id']}");

        $deleteResponse->assertStatus(200);

        Storage::disk('public')->assertMissing($createdMedia['path']);

        $this->assertDatabaseMissing('media', [
            'id' => $createdMedia['id']
        ]);
    }
}
