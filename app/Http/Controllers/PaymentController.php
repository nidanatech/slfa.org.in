<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function show(Request $request, $purpose, Member $member)
    {
        abort(503, 'Online payment is not available yet.');
    }

    public function createOrder(Request $request)
    {
        abort(503, 'Online payment is not available yet.');
    }

    public function verify(Request $request)
    {
        abort(503, 'Online payment is not available yet.');
    }
}
