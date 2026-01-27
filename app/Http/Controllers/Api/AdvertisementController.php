<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    /**
     * Get active advertisements
     */
    public function index(): JsonResponse
    {
        $ads = Advertisement::where('is_active', true)->latest()->get();

        $formattedAds = $ads->map(function ($ad) {
            return [
                'id' => $ad->id,
                'image' => $ad->image ? url('uploads/' . $ad->image) : null,
                'text_ar' => $ad->text_ar,
                'text_en' => $ad->text_en,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Advertisements retrieved successfully',
            'data' => $formattedAds
        ]);
    }
}
