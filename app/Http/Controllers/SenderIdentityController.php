<?php

namespace App\Http\Controllers;

use App\Models\SenderIdentity;
use App\Services\SesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SenderIdentityController extends Controller
{
    protected $ses;

    public function __construct(SesService $ses)
    {
        $this->ses = $ses;
    }

    public function index(Request $request)
    {
        $identities = SenderIdentity::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->get();

        return response()->json($identities);
    }

    public function store(Request $request)
    {
        $request->validate([
            'identity_value' => 'required|string|max:255',
            'identity_type' => 'required|in:email,domain',
        ]);

        $tenantId = $request->user()->tenant_id;
        $value = $request->identity_value;
        $type = $request->identity_type;

        // Check if exists
        $exists = SenderIdentity::where('tenant_id', $tenantId)
            ->where('identity_value', $value)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Identity already exists.'], 422);
        }

        // Domain Policy Check
        if ($type === 'email') {
            $domain = substr(strrchr($value, "@"), 1);
            $verifiedDomain = SenderIdentity::where('tenant_id', $tenantId)
                ->where('identity_type', 'domain')
                ->where('identity_value', $domain)
                ->where('status', 'success')
                ->exists();

            if (!$verifiedDomain) {
                return response()->json(['message' => "You must verify the domain '{$domain}' before adding email addresses for it."], 422);
            }
        }

        try {
            if ($type === 'email') {
                $this->ses->verifyEmailIdentity($value);
                $dkimAttributes = null;
            } else {
                $result = $this->ses->verifyDomainIdentity($value);
                $dkimAttributes = $result['dkim_tokens'] ?? null;
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'SES Error: ' . $e->getMessage()], 500);
        }

        $identity = SenderIdentity::create([
            'tenant_id' => $tenantId,
            'identity_type' => $type,
            'identity_value' => $value,
            'dkim_attributes' => $dkimAttributes,
            'status' => 'pending',
        ]);

        return response()->json($identity);
    }

    public function check(Request $request, SenderIdentity $identity)
    {
        if ($identity->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        \Log::info("Checking identity: {$identity->identity_value} ({$identity->identity_type})");

        $status = 'failed';
        $attributesToUpdate = [];

        if ($identity->identity_type === 'email') {
            $status = $this->ses->getIdentityStatus($identity->identity_value);
        } else {
            // For domains, we first try to get existing attributes
            $dkimData = $this->ses->getDkimAttributes($identity->identity_value);
            \Log::info("Existing DKIM Data: " . json_encode($dkimData));
            
            // If tokens are missing from SES response OR missing from our DB, 
            // force a re-verification call (VerifyDomainDkim) which guarantees token return.
            if (empty($dkimData['DkimTokens']) || empty($identity->dkim_attributes)) {
                 \Log::info("Tokens missing. Attempting re-verification.");
                 try {
                     $verifyResult = $this->ses->verifyDomainIdentity($identity->identity_value);
                     \Log::info("Re-verification Result: " . json_encode($verifyResult));
                     
                     if (!empty($verifyResult['dkim_tokens'])) {
                         $attributesToUpdate['dkim_attributes'] = $verifyResult['dkim_tokens'];
                     }
                 } catch (\Exception $e) {
                     // Log but continue to check status
                     \Log::error("Failed to re-verify domain for tokens: " . $e->getMessage());
                 }
                 
                 // Refresh status after re-verify
                 $dkimData = $this->ses->getDkimAttributes($identity->identity_value);
                 \Log::info("Refreshed DKIM Data: " . json_encode($dkimData));
            }
            
            if ($dkimData) {
                 $status = $dkimData['DkimVerificationStatus'] ?? 'failed';
                 // Update tokens if we found them in the status check (and didn't already set them)
                 if (isset($dkimData['DkimTokens']) && !isset($attributesToUpdate['dkim_attributes'])) {
                     $attributesToUpdate['dkim_attributes'] = $dkimData['DkimTokens'];
                 }
            }
        }

        $attributesToUpdate['status'] = strtolower($status);
        \Log::info("Updating Identity {$identity->id} with: " . json_encode($attributesToUpdate));
        $identity->update($attributesToUpdate);

        return response()->json($identity);
    }

    public function destroy(Request $request, SenderIdentity $identity)
    {
        if ($identity->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $identity->delete();
        // Optionally clean up from SES too, but often safe to keep or delete manual.
        
        return response()->json(['message' => 'Deleted']);
    }
}
