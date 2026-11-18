<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function show(Request $request, $purpose, Member $member)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->member?->id === $member->id, 403);
        
        $amounts = [
            'admission' => $member->membership_type === 'business' ? 16000 : 8000, // example amounts
            'annual_renewal' => $member->membership_type === 'business' ? 6000 : 3000,
        ];
        
        $amount = $amounts[$purpose] ?? 0;

        return Inertia::render('SLFA/Payment', [
            'member' => $member,
            'purpose' => $purpose,
            'amount' => $amount,
            'razorpayKey' => config('services.razorpay.key'),
        ]);
    }

    public function createOrder(Request $request)
    {
        // Placeholder for Razorpay API call
        // Normally we'd use the Razorpay PHP SDK here
        
        return response()->json([
            'order_id' => 'order_' . uniqid(),
            'amount' => $request->amount * 100, // in paise
        ]);
    }

    public function verify(Request $request)
    {
        // Verify Razorpay signature
        // Placeholder logic
        
        $member = Member::findOrFail($request->member_id);
        
        Payment::create([
            'member_id' => $member->id,
            'amount' => $request->amount,
            'purpose' => $request->purpose,
            'razorpay_order_id' => $request->razorpay_order_id,
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'status' => 'paid'
        ]);
        
        if ($request->purpose === 'admission' && $member->status === 'pending') {
            $member->update([
                'status' => 'active',
                'joined_at' => now(),
                'member_id_string' => Member::generateMemberId(),
                'renewed_at' => now(),
            ]);
        } elseif ($request->purpose === 'annual_renewal') {
            $member->update(['renewed_at' => now()]);
        }
        
        return redirect()->route('home')->with('success', 'Payment successful!');
    }
}
