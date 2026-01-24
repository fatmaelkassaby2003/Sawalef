<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Get all active packages
     */
    public function index()
    {
        try {
            $packages = Package::active()
                ->ordered()
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الباقات بنجاح',
                'data' => $packages
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الباقات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single package
     */
    public function show($id)
    {
        try {
            $package = Package::findOrFail($id);

            if (!$package->is_active) {
                return response()->json([
                    'status' => false,
                    'message' => 'هذه الباقة غير متاحة حالياً'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الباقة بنجاح',
                'data' => $package
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'الباقة غير موجودة',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
