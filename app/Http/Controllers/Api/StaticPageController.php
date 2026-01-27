<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutApp;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use App\Models\Term;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function getTerms()
    {
        $data = Term::latest()->first();
        return response()->json([
            'status' => true,
            'message' => 'Terms retrieved successfully',
            'data' => $data
        ]);
    }

    public function getPrivacyPolicy()
    {
        $data = PrivacyPolicy::latest()->first();
        return response()->json([
            'status' => true,
            'message' => 'Privacy policy retrieved successfully',
            'data' => $data
        ]);
    }

    public function getAboutApp()
    {
        $data = AboutApp::latest()->first();
        return response()->json([
            'status' => true,
            'message' => 'About app info retrieved successfully',
            'data' => $data
        ]);
    }

    public function getFaqs()
    {
        $data = Faq::all();
        return response()->json([
            'status' => true,
            'message' => 'FAQs retrieved successfully',
            'data' => $data
        ]);
    }
}
