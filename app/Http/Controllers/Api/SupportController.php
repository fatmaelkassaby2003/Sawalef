<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IssueType;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    public function getIssueTypes()
    {
        $types = IssueType::all();
        return response()->json([
            'status' => true,
            'message' => 'Issue types retrieved successfully',
            'data' => $types
        ]);
    }

    public function storeTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'issue_type_id' => 'required|exists:issue_types,id',
            'message' => 'required|string',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::create([
            'user_id' => auth('api')->id(),
            'issue_type_id' => $request->issue_type_id,
            'message' => $request->message,
            'name' => $request->name ?? (auth('api')->user() ? auth('api')->user()->name : null),
            'email' => $request->email ?? (auth('api')->user() ? auth('api')->user()->email : null),
            'status' => 'open',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Support ticket submitted successfully',
            'data' => $ticket
        ]);
    }
}
