<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Test 9: Real-time Chat
 *   - Two authenticated verified users can send messages.
 *   - Message saved in database correctly.
 *   - NewMessage event is broadcast.
 */

use App\Events\NewMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ═══════════════════════════════════════════════════════════════════════════
// Test 9 – Real-time Chat
// ═══════════════════════════════════════════════════════════════════════════

describe('Real-time Chat', function () {

    beforeEach(function () {
        $this->sender   = User::factory()->create(['is_phone_verified' => true]);
        $this->receiver = User::factory()->create(['is_phone_verified' => true]);
    });

    // ── Core send ───────────────────────────────────────────────────────────

    it('two authenticated verified users can send messages', function () {
        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => 'مرحباً، هل السيارة لا تزال متاحة؟',
            ])
            ->assertCreated()
            ->assertJsonPath('message.body', 'مرحباً، هل السيارة لا تزال متاحة؟')
            ->assertJsonPath('message.sender_id', $this->sender->id);
    });

    it('message is saved in the database correctly', function () {
        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => 'كم السعر النهائي؟',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('messages', [
            'sender_id'   => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'body'        => 'كم السعر النهائي؟',
        ]);

        expect(Message::count())->toBe(1);
    });

    it('NewMessage event is broadcast when a message is sent', function () {
        Event::fake([NewMessage::class]);

        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => 'أريد معرفة التفاصيل.',
            ])
            ->assertCreated();

        Event::assertDispatched(NewMessage::class, function (NewMessage $event): bool {
            return $event->message->sender_id   === $this->sender->id
                && $event->message->receiver_id === $this->receiver->id
                && $event->message->body        === 'أريد معرفة التفاصيل.';
        });
    });

    it('NewMessage event broadcasts on the correct private channel', function () {
        Event::fake([NewMessage::class]);

        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => 'اختبار القناة.',
            ])
            ->assertCreated();

        Event::assertDispatched(NewMessage::class, function (NewMessage $event): bool {
            $channels = $event->broadcastOn();

            return count($channels) === 1
                && $channels[0]->name === 'private-chat.' . $this->receiver->id;
        });
    });

    it('NewMessage broadcast payload contains the expected fields', function () {
        Event::fake([NewMessage::class]);

        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => 'رسالة اختبار الـ payload.',
            ])
            ->assertCreated();

        Event::assertDispatched(NewMessage::class, function (NewMessage $event): bool {
            $payload = $event->broadcastWith();

            return array_key_exists('id', $payload)
                && array_key_exists('sender_id', $payload)
                && array_key_exists('receiver_id', $payload)
                && array_key_exists('body', $payload)
                && array_key_exists('sent_at', $payload);
        });
    });

    // ── Validation & Authorization ───────────────────────────────────────────

    it('unauthenticated users cannot send messages and receive 401', function () {
        $this->postJson(route('messages.store'), [
            'receiver_id' => $this->receiver->id,
            'body'        => 'رسالة بدون تسجيل دخول',
        ])->assertUnauthorized();

        $this->assertDatabaseEmpty('messages');
    });

    it('message body is required', function () {
        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    });

    it('receiver_id is required', function () {
        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'body' => 'رسالة بدون مستلم',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_id']);
    });

    it('receiver must exist in the users table', function () {
        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => 99999,
                'body'        => 'رسالة لمستخدم وهمي',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_id']);
    });

    it('user cannot send a message to themselves', function () {
        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->sender->id,
                'body'        => 'رسالة لنفسي؟',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receiver_id']);
    });

    it('message body cannot exceed 1000 characters', function () {
        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => str_repeat('ا', 1001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    });

    it('NewMessage event is NOT dispatched when validation fails', function () {
        Event::fake([NewMessage::class]);

        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                // missing body
            ])
            ->assertUnprocessable();

        Event::assertNotDispatched(NewMessage::class);
    });

    it('rate limits authenticated users to 5 messages per minute', function () {
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->sender)
                ->postJson(route('messages.store'), [
                    'receiver_id' => $this->receiver->id,
                    'body'        => "Message {$i}",
                ])
                ->assertCreated();
        }

        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => 'Message 6 — should be blocked',
            ])
            ->assertStatus(429)
            ->assertJsonPath('message', __('ui.messages.rate_limit_exceeded', ['seconds' => 60]));

        expect(Message::count())->toBe(5);
    });

    it('does not count failed validation toward the message rate limit', function () {
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($this->sender)
                ->postJson(route('messages.store'), [
                    'receiver_id' => $this->receiver->id,
                ])
                ->assertUnprocessable();
        }

        $this->actingAs($this->sender)
            ->postJson(route('messages.store'), [
                'receiver_id' => $this->receiver->id,
                'body'        => 'Valid after failed attempts',
            ])
            ->assertCreated();
    });
});
