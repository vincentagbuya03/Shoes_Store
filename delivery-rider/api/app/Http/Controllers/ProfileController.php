<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Sample rider profile
     * In production, this would come from a database
     */
    private function getSampleProfile()
    {
        return [
            'id' => 1,
            'name' => 'John Rider',
            'email' => 'rider@demo.com',
            'phone' => '+63 912 345 6789',
            'status' => 'online',
            'vehicle_type' => 'Motorcycle',
            'plate_number' => 'ABC 1234',
            'license_number' => 'D01-23-456789',
            'rating' => 4.8,
            'total_deliveries' => 156,
            'completed_this_month' => 42,
            'total_earnings' => '12,450',
            'joined_at' => '2024-01-15'
        ];
    }

    /**
     * Get rider profile
     * 
     * GET /api/profile
     */
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'profile' => $this->getSampleProfile()
        ]);
    }

    /**
     * Update rider profile
     * 
     * PUT /api/profile
     */
    public function update(Request $request)
    {
        $profile = $this->getSampleProfile();
        
        // Merge updates
        $updateFields = ['name', 'phone', 'vehicle_type', 'plate_number', 'license_number'];
        foreach ($updateFields as $field) {
            if ($request->has($field)) {
                $profile[$field] = $request->input($field);
            }
        }

        // In production, save to database

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'profile' => $profile
        ]);
    }
}
