<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Profile Controller
 * 
 * Handles rider profile operations.
 * Returns sample JSON responses for development and testing.
 */
class ProfileController extends Controller
{
    /**
     * Get rider profile
     * 
     * GET /api/profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        // In production, get rider data from authenticated user
        return response()->json([
            'success' => true,
            'rider' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'rider@shoetakels.com',
                'phone' => '+63 912 345 6789',
                'address' => 'Metro Manila, Philippines',
                'vehicle_type' => 'Motorcycle',
                'vehicle_plate' => 'ABC 1234',
                'license_number' => 'N01-23-456789',
                'is_verified' => true,
                'is_online' => true,
                'total_deliveries' => 156,
                'rating' => '4.9',
                'total_earnings' => 45680,
                'member_since' => '2024',
                'created_at' => '2024-01-15T08:00:00Z',
                'updated_at' => date('Y-m-d\TH:i:s\Z')
            ]
        ]);
    }
    
    /**
     * Update rider profile
     * 
     * PUT /api/profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // In production, validate and update rider data
        $data = $request->all();
        
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'rider' => array_merge([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'rider@shoetakels.com',
                'phone' => '+63 912 345 6789',
                'address' => 'Metro Manila, Philippines',
                'vehicle_type' => 'Motorcycle',
                'is_verified' => true,
                'total_deliveries' => 156,
                'rating' => '4.9',
                'total_earnings' => 45680,
                'member_since' => '2024',
                'updated_at' => date('Y-m-d\TH:i:s\Z')
            ], $data)
        ]);
    }
}
