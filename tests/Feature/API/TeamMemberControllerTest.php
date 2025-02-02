<?php

namespace Tests\Feature\API;

use App\Models\User;
use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TeamMemberControllerTest extends TestCase
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
        Storage::fake('public');
        
        $imageFile = UploadedFile::fake()->image('profile.jpg', 400, 400);

        $teamMember = [
            'name' => $this->faker->name,
            'role' => $this->faker->jobTitle,
            'email' => $this->faker->unique()->safeEmail,
            'bio' => $this->faker->paragraph,
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'profile_picture' => $imageFile
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/team-members", $teamMember);

        $responseData = $response->json('data');

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'role',
                    'email',
                    'bio',
                    'order',
                    'is_active',
                    'profile_picture',
                    'created_at',
                    'updated_at'
                ]
            ]);

        Storage::disk('public')->assertExists($responseData['profile_picture']);

        $teamMemberForDb = collect($teamMember)->except('profile_picture')->toArray();
        
        $this->assertDatabaseHas('team_members', $teamMemberForDb);
    }

    public function test_get_data_from_endpoint_with_jwt()
    {
        Storage::fake('public');
                
        $imageFile1 = UploadedFile::fake()->image('profile1.jpg', 400, 400);
        $path1 = Storage::disk('public')->putFile('team-members', $imageFile1);
        $teamMember1 = TeamMember::create([
            'name' => $this->faker->name,
            'role' => $this->faker->jobTitle,
            'email' => $this->faker->unique()->safeEmail,
            'bio' => $this->faker->paragraph,
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'profile_picture' => $path1
        ]);

        $imageFile2 = UploadedFile::fake()->image('profile2.jpg', 400, 400);
        $path2 = Storage::disk('public')->putFile('team-members', $imageFile2);
        $teamMember2 = TeamMember::create([
            'name' => $this->faker->name,
            'role' => $this->faker->jobTitle,
            'email' => $this->faker->unique()->safeEmail,
            'bio' => $this->faker->paragraph,
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'profile_picture' => $path2
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/team-members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'role',
                        'email',
                        'bio',
                        'order',
                        'is_active',
                        'profile_picture',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $response->assertJsonCount(2, 'data');

        Storage::disk('public')->assertExists($path1);
        Storage::disk('public')->assertExists($path2);

        $response->assertJson([
            'data' => [
                [
                    'id' => $teamMember1->id,
                    'name' => $teamMember1->name,
                    'email' => $teamMember1->email,
                ],
                [
                    'id' => $teamMember2->id,
                    'name' => $teamMember2->name,
                    'email' => $teamMember2->email,
                ]
            ]
        ]);
    }

    public function test_update_data_to_endpoint_with_jwt()
    {
        Storage::fake('public');
        
        $imageFile = UploadedFile::fake()->image('profile.jpg', 400, 400);

        $teamMember = [
            'name' => $this->faker->name,
            'role' => $this->faker->jobTitle,
            'email' => $this->faker->unique()->safeEmail,
            'bio' => $this->faker->paragraph,
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'profile_picture' => null
        ];

        $data = TeamMember::create($teamMember);

        $updateData = [
            'profile_picture' => $imageFile
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->putJson("/api/team-members/{$data->id}", $updateData);

        $responseData = $response->json('data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'role',
                    'email',
                    'bio',
                    'order',
                    'is_active',
                    'profile_picture',
                    'created_at',
                    'updated_at'
                ]
            ]);

        Storage::disk('public')->assertExists($responseData['profile_picture']);

        $teamMemberForDb = collect($teamMember)
            ->except('profile_picture')
            ->toArray();
        
        $this->assertDatabaseHas('team_members', $teamMemberForDb);
    
        $this->assertDatabaseHas('team_members', [
            'id' => $data->id,
            'profile_picture' => $responseData['profile_picture']
        ]);
    }

    public function test_destroy_data_to_endpoint_with_jwt()
    {
        Storage::fake('public');
        
        $imageFile = UploadedFile::fake()->image('profile.jpg', 400, 400);

        $teamMember = [
            'name' => $this->faker->name,
            'role' => $this->faker->jobTitle,
            'email' => $this->faker->unique()->safeEmail,
            'bio' => $this->faker->paragraph,
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'profile_picture' => $imageFile
        ];

        $data = TeamMember::create($teamMember);

        $storedFilePath = $data->profile_picture;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/team-members/{$data->id}");

        // dd($response->json());

        // $response->assertStatus(200)
        //     ->assertJsonStructure([
        //         'message'
        //     ])
        //     ->assertJson([
        //         'message' => 'Team member deleted successfully'
        //     ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('team_members', ['id' => $data->id]);
        $this->assertDatabaseCount('team_members', 0);
    }
}
