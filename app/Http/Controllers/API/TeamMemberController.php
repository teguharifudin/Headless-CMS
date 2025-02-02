<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TeamMemberController extends Controller
{
    public function index()
    {
        try {
            $teamMembers = TeamMember::with(['createdBy'])
                ->select(['id', 'name', 'role', 'email', 'bio', 'profile_picture', 'order', 'is_active', 'created_by', 'created_at', 'updated_at'])
                ->orderBy('order')
                ->get();

            if ($teamMembers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No data',
                    'data' => []
                ], 200);
            }

            $teamMembers->transform(function ($member) {
                if ($member->profile_picture) {
                    $member->profile_picture_url = Storage::disk('public')->url($member->profile_picture);
                }
                return $member;
            });

            return response()->json([
                'success' => true,
                'message' => 'Team members fetched successfully',
                'data' => $teamMembers
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch team members', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team members',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'role' => 'required|string|max:255',
                'bio' => 'nullable|string',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'email' => 'required|email|unique:team_members',
                'order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }

                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                
                $uploadPath = 'team-members';
                if (!Storage::disk('public')->exists($uploadPath)) {
                    Storage::disk('public')->makeDirectory($uploadPath);
                }

                $path = $file->storeAs($uploadPath, $fileName, 'public');
                
                if (!$path) {
                    throw new \Exception('Failed to store profile picture');
                }

                $validated['profile_picture'] = $path;
            }

            $validated['order'] = $validated['order'] ?? 0;
            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['created_by'] = auth()->id();

            $teamMember = TeamMember::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Team member created successfully',
                'data' => $teamMember
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'data' => null
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create team member', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->except(['profile_picture']),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create team member',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $teamMember = TeamMember::with(['createdBy'])
                ->select(['id', 'name', 'role', 'email', 'bio', 'profile_picture', 'order', 'is_active', 'created_by', 'created_at', 'updated_at'])
                ->findOrFail($id);

            if ($teamMember->profile_picture) {
                $teamMember->profile_picture_url = Storage::disk('public')->url($teamMember->profile_picture);
            }

            return response()->json([
                'success' => true,
                'message' => 'Team member retrieved successfully',
                'data' => $teamMember
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found',
                'errors' => ['id' => ['Team member with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve team member', [
                'error' => $e->getMessage(),
                'team_member_id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team member',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $teamMember = TeamMember::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'role' => 'sometimes|required|string|max:255',
                'bio' => 'nullable|string',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'email' => 'sometimes|required|email|unique:team_members,email,' . $id,
                'order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }

                if ($teamMember->profile_picture && Storage::disk('public')->exists($teamMember->profile_picture)) {
                    Storage::disk('public')->delete($teamMember->profile_picture);
                }

                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $uploadPath = 'team-members';
                
                $path = $file->storeAs($uploadPath, $fileName, 'public');
                
                if (!$path) {
                    throw new \Exception('Failed to store profile picture');
                }

                $validated['profile_picture'] = $path;
            }

            if (isset($validated['is_active'])) {
                $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            $teamMember->update($validated);

            Cache::forget("team_member_{$id}");

            if ($teamMember->profile_picture) {
                $teamMember->profile_picture_url = Storage::disk('public')->url($teamMember->profile_picture);
            }

            return response()->json([
                'success' => true,
                'message' => 'Team member updated successfully',
                'data' => $teamMember->fresh()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found',
                'errors' => ['id' => ['Team member with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'data' => null
            ], 422);

        } catch (\Exception $e) {
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('Failed to update team member', [
                'error' => $e->getMessage(),
                'team_member_id' => $id,
                'request_data' => $request->except(['profile_picture']),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update team member',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $teamMember = TeamMember::findOrFail($id);

            if ($teamMember->profile_picture) {
                Storage::disk('public')->delete($teamMember->profile_picture);
            }

            $teamMember->delete();

            return response()->json([
                'success' => true,
                'message' => 'Team member deleted successfully',
                'data' => null
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found',
                'errors' => ['id' => ['Page with ID ' . $id . ' not found']],
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete page',
                'errors' => ['server' => ['An unexpected error occurred']],
                'data' => null
            ], 500);
        }
    }
}
