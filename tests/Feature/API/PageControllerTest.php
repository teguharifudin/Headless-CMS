<?php

namespace Tests\Feature\API;

use App\Models\Page;
use App\Models\User;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

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
        $page = [
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'banner_media_id' => null,
            'published_at' => now()->tomorrow()->format('Y-m-d H:i:s'),
            'status' => 'draft',
        ];

        $slug = Str::slug($page['title']);
        $originalSlug = $slug;
        $counter = 1;
        
        while (Page::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        $page['slug'] = $slug;
        $page['author_id'] = $this->user->id;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson("/api/pages", $page);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'content',
                    'slug',
                    'banner_media_id',
                    'published_at',
                    'status',
                    'author_id'
                ]
            ])
            ->assertJsonFragment($page);

        $this->assertDatabaseHas('pages', $page);
    }

    public function test_get_data_from_endpoint_with_jwt()
    {
        $title1 = $this->faker->sentence;
        $page1 = Page::create([
            'title' => $title1,
            'content' => $this->faker->paragraph,
            'banner_media_id' => null,
            'published_at' => now()->tomorrow()->format('Y-m-d H:i:s'),
            'status' => 'draft',
            'slug' => Str::slug($title1),
            'author_id' => $this->user->id
        ]);

        $title2 = $this->faker->sentence;
        $page2 = Page::create([
            'title' => $title2,
            'content' => $this->faker->paragraph,
            'banner_media_id' => null,
            'published_at' => now()->tomorrow()->format('Y-m-d H:i:s'),
            'status' => 'published',
            'slug' => Str::slug($title2),
            'author_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->getJson("/api/pages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'content',
                        'slug',
                        'banner_media_id',
                        'published_at',
                        'status',
                        'author_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $response->assertJsonFragment([
            'title' => $page1->title,
            'content' => $page1->content
        ]);

        $response->assertJsonFragment([
            'title' => $page2->title,
            'content' => $page2->content
        ]);

        $this->assertDatabaseCount('pages', 2);
    }

    public function test_update_data_to_endpoint_with_jwt()
    {
        $page = [
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'banner_media_id' => null,
            'published_at' => now()->tomorrow()->format('Y-m-d H:i:s'),
            'status' => 'draft',
        ];

        $slug = Str::slug($page['title']);
        $originalSlug = $slug;
        $counter = 1;
        
        while (Page::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        $page['slug'] = $slug;
        $page['author_id'] = $this->user->id;

        $data = Page::create($page);

        $updateData = [
            'status' => 'published'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->putJson("/api/pages/{$data->id}", $updateData);

        $expectedData = array_merge($page, $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'content',
                    'slug',
                    'banner_media_id',
                    'published_at',
                    'status',
                    'author_id',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonFragment(['status' => 'published']);

        $this->assertDatabaseHas('pages', $expectedData);
    }

    public function test_destroy_data_to_endpoint_with_jwt()
    {
        $page = [
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'banner_media_id' => null,
            'published_at' => now()->tomorrow()->format('Y-m-d H:i:s'),
            'status' => 'draft',
        ];

        $slug = Str::slug($page['title']);
        $originalSlug = $slug;
        $counter = 1;
        
        while (Page::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        $page['slug'] = $slug;
        $page['author_id'] = $this->user->id;

        $data = Page::create($page);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->deleteJson("/api/pages/{$data->id}");
    
        // dd($response->json());

        // $response->assertStatus(200)
        //     ->assertJsonStructure([
        //         'message'
        //     ])
        //     ->assertJson([
        //         'message' => 'Page deleted successfully'
        //     ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pages', ['id' => $data->id]);
        $this->assertDatabaseCount('pages', 0);
    }
}
