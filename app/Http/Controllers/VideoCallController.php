<?php

namespace App\Http\Controllers;

use App\Events\CallAccepted;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\CallRejected;
use App\Events\WebRTCEvent;
use App\Models\Conversation;
use App\Models\VideoCall;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoCallController extends Controller
{
    /**
     * Initiate a video call
     */
    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'receiver_id' => 'required|exists:users,id|different:caller_id',
        ]);

        try {
            $conversation = Conversation::findOrFail($validated['conversation_id']);

            // Verify caller is participant
            if (! $conversation->participants()->where('user_id', auth()->id())->exists()) {
                return response()->json(['error' => 'Not a participant'], 403);
            }

            // Verify receiver is participant
            if (! $conversation->participants()->where('user_id', $validated['receiver_id'])->exists()) {
                return response()->json(['error' => 'Receiver not in conversation'], 403);
            }

            // Check for existing active call (but cleanup stuck calls older than 2 minutes)
            $twoMinutesAgo = now()->subMinutes(2);

            // Delete stuck calls (ringing/active for more than 2 minutes)
            VideoCall::where('conversation_id', $conversation->id)
                ->whereIn('status', ['ringing', 'active'])
                ->where('created_at', '<', $twoMinutesAgo)
                ->delete();

            // Check for existing active call (recent ones only)
            $existingCall = VideoCall::where('conversation_id', $conversation->id)
                ->whereIn('status', ['ringing', 'active'])
                ->where('created_at', '>=', $twoMinutesAgo)
                ->first();

            if ($existingCall) {
                return response()->json(['error' => 'Call already in progress'], 409);
            }

            // Create video call record
            $call = VideoCall::create([
                'call_id' => (string) Str::uuid(),
                'conversation_id' => $conversation->id,
                'caller_id' => auth()->id(),
                'receiver_id' => $validated['receiver_id'],
                'status' => 'ringing',
            ]);

            \Log::info('📞 Video call initiated', [
                'call_id' => $call->call_id,
                'caller_id' => $call->caller_id,
                'receiver_id' => $call->receiver_id,
            ]);

            // Load relationships for response
            $call->load(['caller', 'receiver', 'conversation']);

            // Broadcast call initiated event
            broadcast(new CallInitiated($call))->toOthers();

            return response()->json([
                'success' => true,
                'call_id' => $call->call_id,
                'call' => $call,
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error initiating call: '.$e->getMessage());

            return response()->json(['error' => 'Failed to initiate call'], 500);
        }
    }

    /**
     * Accept a video call
     */
    public function accept($callId, Request $request)
    {
        try {
            $call = VideoCall::where('call_id', $callId)->firstOrFail();

            // Verify receiver is accepting
            if ($call->receiver_id !== auth()->id()) {
                return response()->json(['error' => 'Not the call receiver'], 403);
            }

            // Verify call is still ringing
            if ($call->status !== 'ringing') {
                return response()->json(['error' => 'Call is no longer ringing'], 409);
            }

            // Update call status
            $call->update([
                'status' => 'active',
                'started_at' => now(),
            ]);

            \Log::info('✅ Video call accepted', [
                'call_id' => $call->call_id,
                'receiver_id' => auth()->id(),
            ]);

            // Load relationships for response
            $call->load(['caller', 'receiver', 'conversation']);

            // Broadcast call accepted event to caller
            broadcast(new CallAccepted($call))->toOthers();

            return response()->json([
                'success' => true,
                'call' => $call,
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error accepting call: '.$e->getMessage());

            return response()->json(['error' => 'Failed to accept call'], 500);
        }
    }

    /**
     * Reject a video call
     */
    public function reject($callId, Request $request)
    {
        try {
            $call = VideoCall::where('call_id', $callId)->firstOrFail();

            // Verify receiver is rejecting
            if ($call->receiver_id !== auth()->id()) {
                return response()->json(['error' => 'Not the call receiver'], 403);
            }

            // Update call status
            $call->update(['status' => 'rejected']);

            \Log::info('❌ Video call rejected', [
                'call_id' => $call->call_id,
                'receiver_id' => auth()->id(),
            ]);

            // Load relationships
            $call->load(['caller', 'receiver', 'conversation']);

            // Broadcast rejection event
            broadcast(new CallRejected($call))->toOthers();

            return response()->json([
                'success' => true,
                'call' => $call,
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error rejecting call: '.$e->getMessage());

            return response()->json(['error' => 'Failed to reject call'], 500);
        }
    }

    /**
     * End a video call
     */
    public function end($callId, Request $request)
    {
        try {
            $call = VideoCall::where('call_id', $callId)->firstOrFail();

            // Verify user is participant in the call
            if ($call->caller_id !== auth()->id() && $call->receiver_id !== auth()->id()) {
                return response()->json(['error' => 'Not a call participant'], 403);
            }

            // Calculate call duration if started
            $duration = null;
            if ($call->started_at) {
                $duration = $call->started_at->diffInSeconds(now());
            }

            // Update call status
            $call->update([
                'status' => 'ended',
                'ended_at' => now(),
                'duration' => $duration,
            ]);

            \Log::info('📞 Video call ended', [
                'call_id' => $call->call_id,
                'duration_seconds' => $duration,
            ]);

            // Load relationships
            $call->load(['caller', 'receiver', 'conversation']);

            // Broadcast call ended event
            broadcast(new CallEnded($call))->toOthers();

            return response()->json([
                'success' => true,
                'call' => $call,
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error ending call: '.$e->getMessage());

            return response()->json(['error' => 'Failed to end call'], 500);
        }
    }

    /**
     * Handle WebRTC signaling events (offer, answer, ICE candidates)
     */
    public function signal($callId, Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:offer,answer,candidate',
                'data' => 'required|array',
                'target_user_id' => 'required|exists:users,id',
            ]);

            $call = VideoCall::where('call_id', $callId)->firstOrFail();

            // Verify user is participant in the call
            if ($call->caller_id !== auth()->id() && $call->receiver_id !== auth()->id()) {
                return response()->json(['error' => 'Not a call participant'], 403);
            }

            // Verify target user is the other participant
            $targetUserId = $validated['target_user_id'];
            if ($targetUserId !== $call->caller_id && $targetUserId !== $call->receiver_id) {
                return response()->json(['error' => 'Invalid target user'], 403);
            }

            // Validate SDP data for offer and answer
            if (in_array($validated['type'], ['offer', 'answer'])) {
                $this->validateSDP($validated['data']);
            }

            \Log::info('📡 WebRTC signal received', [
                'call_id' => $callId,
                'type' => $validated['type'],
                'from_user' => auth()->id(),
                'to_user' => $targetUserId,
            ]);

            // Broadcast WebRTC event to the target user
            broadcast(new WebRTCEvent(
                call: $call,
                type: $validated['type'],
                data: $validated['data'],
                fromUserId: auth()->id(),
                toUserId: $targetUserId
            ))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Signal sent',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('⚠️ Validation error in signal:', $e->errors());

            return response()->json(['error' => 'Invalid signal data', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('❌ Error processing signal: '.$e->getMessage());

            return response()->json(['error' => 'Failed to process signal'], 500);
        }
    }

    /**
     * Validate SDP format
     */
    private function validateSDP($data)
    {
        if (! isset($data['sdp']) || ! is_string($data['sdp'])) {
            throw new \Exception('Invalid SDP: missing or invalid sdp field');
        }

        if (! isset($data['type']) || ! in_array($data['type'], ['offer', 'answer'])) {
            throw new \Exception('Invalid SDP: missing or invalid type field');
        }

        $sdp = $data['sdp'];

        // Check for required SDP version line
        if (strpos($sdp, 'v=0') === false) {
            throw new \Exception('Invalid SDP: missing v=0 line');
        }

        // Check for required origin line
        if (strpos($sdp, 'o=') === false) {
            throw new \Exception('Invalid SDP: missing o= (origin) line');
        }

        // Check for session description
        if (strpos($sdp, 's=') === false) {
            throw new \Exception('Invalid SDP: missing s= (session) line');
        }

        // Check for at least one media section
        if (strpos($sdp, 'm=') === false) {
            throw new \Exception('Invalid SDP: missing m= (media) section');
        }

        // Validate setup attribute for answer
        if ($data['type'] === 'answer') {
            $lines = explode("\r\n", $sdp);
            $hasValidSetup = false;

            foreach ($lines as $line) {
                // Answerer must have 'active' or 'passive', not 'actpass'
                if (strpos($line, 'a=setup:') === 0) {
                    if (preg_match('/a=setup:(active|passive)/', $line)) {
                        $hasValidSetup = true;
                        break;
                    } elseif (strpos($line, 'a=setup:actpass') === 0) {
                        \Log::warning('⚠️ Answer has setup:actpass, should be active/passive');
                        // This will be fixed on client side, but log it
                    }
                }
            }
        }

        \Log::info('✅ SDP validation passed', [
            'type' => $data['type'],
            'sdp_length' => strlen($sdp),
        ]);
    }

    /**
     * Get active call for a conversation
     */
    public function getActiveCall($conversationId)
    {
        try {
            $call = VideoCall::where('conversation_id', $conversationId)
                ->whereIn('status', ['ringing', 'active'])
                ->with(['caller', 'receiver', 'conversation'])
                ->first();

            if (! $call) {
                return response()->json(['call' => null]);
            }

            return response()->json(['call' => $call]);
        } catch (\Exception $e) {
            \Log::error('❌ Error getting active call: '.$e->getMessage());

            return response()->json(['error' => 'Failed to get call'], 500);
        }
    }

    /**
     * Get call history for a conversation
     */
    public function history($conversationId)
    {
        try {
            $calls = VideoCall::where('conversation_id', $conversationId)
                ->where('status', 'ended')
                ->with(['caller', 'receiver'])
                ->orderByDesc('ended_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $calls,
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error getting call history: '.$e->getMessage());

            return response()->json(['error' => 'Failed to get history'], 500);
        }
    }
}
