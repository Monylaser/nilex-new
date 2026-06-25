<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    /**
     * Store a new message and fire the NewMessage broadcast event.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                function ($attribute, $value, $fail) use ($request) {
                    if ((int) $value === (int) $request->user()->id) {
                        $fail(__('server.message.self'));
                    }
                },
            ],
            'body' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'sender_id'   => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'body'        => $validated['body'],
        ]);

        event(new NewMessage($message));

        return response()->json([
            'message' => $message->load('sender'),
        ], 201);
    }
}
