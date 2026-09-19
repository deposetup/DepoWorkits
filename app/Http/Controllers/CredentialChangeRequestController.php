<?php

namespace App\Http\Controllers;

use App\Models\CredentialChangeRequest;
use Illuminate\Http\Request;

class CredentialChangeRequestController extends Controller
{
    /**
     * Müşteri: GLN/şifre bilgisinin değiştirilmesi için talep açar.
     * Bilgiyi kendisi değiştiremez, sadece talep bildirebilir.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        CredentialChangeRequest::create([
            'depot_id' => $user->depot_id,
            'requested_by' => $user->id,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Talebiniz alındı, admin onayı bekleniyor.');
    }

    /**
     * Admin: talebi onaylar veya reddeder.
     */
    public function review(Request $request, CredentialChangeRequest $changeRequest)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $changeRequest->update([
            'status' => $data['decision'],
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Talep güncellendi.');
    }
}
