<template>
    <!-- Incoming Call Modal -->
    <div
        v-if="chatStore.incomingCall"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="text-center">
                <div
                    class="w-16 h-16 bg-indigo-500 rounded-full flex items-center justify-center text-white text-2xl mx-auto mb-4"
                >
                    📞
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Incoming Video Call
                </h3>
                <p class="text-gray-600 mb-4">
                    {{ chatStore.incomingCall.caller?.name || "Someone" }} is
                    calling you...
                </p>
                <div class="flex space-x-3 justify-center">
                    <button
                        @click="acceptCall"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-full flex items-center space-x-2"
                    >
                        <span>Accept</span>
                    </button>
                    <button
                        @click="rejectCall"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full flex items-center space-x-2"
                    >
                        <span>Decline</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Call Interface -->
    <div v-if="chatStore.isCallActive" class="fixed inset-0 bg-gray-900 z-50">
        <!-- Hidden audio element for remote audio -->
        <audio
            ref="remoteAudio"
            autoplay
            playsinline
            @loadedmetadata="onRemoteAudioMetadataLoaded"
            @playing="onRemoteAudioPlaying"
            @error="onRemoteAudioError"
        ></audio>

        <!-- Video Grid -->
        <div class="h-full flex flex-col">
            <!-- Remote Video -->
            <div class="flex-1 bg-black relative">
                <video
                    v-show="chatStore.remoteStream"
                    ref="remoteVideo"
                    autoplay
                    playsinline
                    muted
                    class="w-full h-full object-cover"
                    @loadedmetadata="onRemoteMetadataLoaded"
                    @play="onRemoteVideoPlaying"
                    @error="onRemoteVideoError"
                ></video>

                <!-- Remote Video Placeholder -->
                <div
                    v-if="!chatStore.remoteStream"
                    class="absolute inset-0 flex items-center justify-center"
                >
                    <div class="text-center text-white">
                        <div
                            class="w-24 h-24 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4"
                        >
                            <span class="text-2xl">👤</span>
                        </div>
                        <p>Waiting for remote video...</p>
                        <p class="text-sm text-gray-400 mt-2">
                            {{ getOtherParticipantName() }}
                        </p>
                    </div>
                </div>

                <!-- Call Info -->
                <div
                    class="absolute top-4 left-4 bg-black bg-opacity-50 text-white px-3 py-2 rounded-lg"
                >
                    <div class="flex items-center space-x-2">
                        <div
                            v-if="connectionStatus === 'connected'"
                            class="w-3 h-3 bg-green-500 rounded-full animate-pulse"
                        ></div>
                        <div
                            v-else
                            class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"
                        ></div>
                        <span>Live</span>
                        <span>{{ formatCallDuration }}</span>
                    </div>
                    <div class="text-sm mt-1">
                        {{ getOtherParticipantName() }}
                    </div>
                </div>

                <!-- Connection Status -->
                <div
                    v-if="connectionStatus !== 'connected'"
                    class="absolute top-4 right-4 bg-black bg-opacity-70 text-white px-3 py-2 rounded-lg"
                >
                    <div class="flex items-center space-x-2">
                        <div
                            class="w-2 h-2 bg-white rounded-full animate-pulse"
                        ></div>
                        <span class="text-sm">{{
                            getConnectionStatusText()
                        }}</span>
                    </div>

                    <!-- Connection Progress Steps -->
                    <div
                        v-if="showConnectionSteps"
                        class="mt-2 text-xs text-gray-300"
                    >
                        <div class="flex items-center space-x-1 mb-1">
                            <div
                                :class="[
                                    'w-2 h-2 rounded-full',
                                    connectionStep >= 1
                                        ? 'bg-green-500'
                                        : 'bg-gray-500',
                                ]"
                            ></div>
                            <span>Establishing connection...</span>
                        </div>
                        <div class="flex items-center space-x-1 mb-1">
                            <div
                                :class="[
                                    'w-2 h-2 rounded-full',
                                    connectionStep >= 2
                                        ? 'bg-green-500'
                                        : 'bg-gray-500',
                                ]"
                            ></div>
                            <span>Exchanging media info...</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div
                                :class="[
                                    'w-2 h-2 rounded-full',
                                    connectionStep >= 3
                                        ? 'bg-green-500'
                                        : 'bg-gray-500',
                                ]"
                            ></div>
                            <span>Finalizing connection...</span>
                        </div>
                    </div>
                </div>

                <!-- Audio Level Indicator -->
                <div
                    v-if="
                        remoteAudioLevel > 0 && connectionStatus === 'connected'
                    "
                    class="absolute bottom-4 left-4 bg-black bg-opacity-50 text-white px-3 py-2 rounded-lg"
                >
                    <div class="flex items-center space-x-2">
                        <span class="text-sm">🔊 Remote Audio</span>
                        <div
                            class="w-20 h-2 bg-gray-700 rounded-full overflow-hidden"
                        >
                            <div
                                class="h-full bg-green-500 transition-all"
                                :style="{ width: remoteAudioLevel + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Local Video (Picture-in-Picture) -->
            <div
                class="absolute bottom-4 right-4 w-48 h-36 bg-black rounded-lg overflow-hidden border-2 border-white shadow-lg"
            >
                <video
                    v-show="chatStore.localStream"
                    ref="localVideo"
                    autoplay
                    playsinline
                    muted
                    class="w-full h-full object-cover"
                    @loadedmetadata="onLocalMetadataLoaded"
                    @play="onLocalVideoPlaying"
                    @error="onLocalVideoError"
                ></video>

                <!-- Local Video Placeholder -->
                <div
                    v-show="!chatStore.localStream"
                    class="w-full h-full bg-gray-800 flex items-center justify-center"
                >
                    <div class="text-white text-center">
                        <div class="text-2xl mb-2">📹</div>
                        <div class="text-xs">Local Camera</div>
                    </div>
                </div>
            </div>

            <!-- Call Controls -->
            <div
                class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-4"
            >
                <button
                    @click="toggleVideo"
                    :class="[
                        'p-3 rounded-full text-white transition-colors hover:scale-110 transform',
                        isVideoEnabled
                            ? 'bg-gray-600 hover:bg-gray-700'
                            : 'bg-red-600 hover:bg-red-700',
                    ]"
                    :title="
                        isVideoEnabled ? 'Turn off camera' : 'Turn on camera'
                    "
                >
                    {{ isVideoEnabled ? "📹" : "❌" }}
                </button>

                <button
                    @click="toggleAudio"
                    :class="[
                        'p-3 rounded-full text-white transition-colors hover:scale-110 transform',
                        isAudioEnabled
                            ? 'bg-gray-600 hover:bg-gray-700'
                            : 'bg-red-600 hover:bg-red-700',
                    ]"
                    :title="
                        isAudioEnabled ? 'Mute microphone' : 'Unmute microphone'
                    "
                >
                    {{ isAudioEnabled ? "🎤" : "🔇" }}
                </button>

                <button
                    @click="endCall"
                    class="p-3 bg-red-600 hover:bg-red-700 rounded-full text-white transition-colors hover:scale-110 transform"
                    title="End call"
                >
                    📞
                </button>
            </div>
        </div>
    </div>

    <!-- Call Initiation Button in Conversation Header -->
    <button
        v-if="
            effectiveConversationId &&
            !chatStore.isCallActive &&
            !chatStore.incomingCall
        "
        @click="initiateVideoCall"
        class="ml-4 p-2 bg-green-500 hover:bg-green-600 text-white rounded-full transition-colors"
        title="Start Video Call"
    >
        📹
    </button>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from "vue";
import { useChatStore } from "../stores/chat";
import { useAuthStore } from "../stores/auth";
import axios from "axios";

const chatStore = useChatStore();
const authStore = useAuthStore();

const localVideo = ref(null);
const remoteVideo = ref(null);
const remoteAudio = ref(null);
const isVideoEnabled = ref(true);
const isAudioEnabled = ref(true);
const callStartTime = ref(null);
const callDuration = ref(0);
const callInterval = ref(null);
const connectionStatus = ref("disconnected");
const pendingWebRTCEvents = ref([]);
const isProcessingWebRTC = ref(false);
const callTimeoutRef = ref(null);
const maxCallWaitTime = 60000;
const callState = ref("idle");
const remoteAudioLevel = ref(0);
const audioContext = ref(null);
const analyser = ref(null);
const processedEventIds = ref(new Set());
const connectionStep = ref(0);
const showConnectionSteps = ref(false);
const connectionStepsTimeout = ref(null);

const videoDebugInfo = ref({
    local: {},
    remote: {},
});

const isCaller = computed(() => {
    return chatStore.videoCall?.caller_id === authStore.user.id;
});

const effectiveConversationId = computed(() => {
    return (
        chatStore.currentConversation?.id ||
        chatStore.videoCall?.conversation_id ||
        null
    );
});

const formatCallDuration = computed(() => {
    const minutes = Math.floor(callDuration.value / 60);
    const seconds = callDuration.value % 60;
    return `${minutes.toString().padStart(2, "0")}:${seconds
        .toString()
        .padStart(2, "0")}`;
});

// Watch connection status changes to show progress steps
watch(
    () => connectionStatus.value,
    (newStatus, oldStatus) => {
        console.log(
            `📄 Connection status changed: ${oldStatus} → ${newStatus}`
        );

        if (newStatus === "connecting" && oldStatus === "disconnected") {
            showConnectionSteps.value = true;
            connectionStep.value = 1;

            // Auto-hide connection steps after 15 seconds if not connected
            if (connectionStepsTimeout.value) {
                clearTimeout(connectionStepsTimeout.value);
            }
            connectionStepsTimeout.value = setTimeout(() => {
                if (connectionStatus.value !== "connected") {
                    showConnectionSteps.value = false;
                }
            }, 15000);
        }

        if (newStatus === "connected") {
            connectionStep.value = 3;
            showConnectionSteps.value = false;
            if (connectionStepsTimeout.value) {
                clearTimeout(connectionStepsTimeout.value);
            }
        }

        // Update connection steps based on WebRTC events
        if (newStatus === "connecting") {
            // Simulate progress steps with delays
            setTimeout(() => {
                if (connectionStatus.value === "connecting") {
                    connectionStep.value = 2;
                }
            }, 1000);

            setTimeout(() => {
                if (connectionStatus.value === "connecting") {
                    connectionStep.value = 3;
                }
            }, 3000);
        }
    }
);

// Watch for local stream changes
watch(
    () => chatStore.localStream,
    (newStream) => {
        if (newStream && localVideo.value) {
            console.log("📹 Assigning local stream to video element");
            localVideo.value.srcObject = newStream;

            nextTick(() => {
                if (localVideo.value && !localVideo.value.srcObject) {
                    localVideo.value.srcObject = newStream;
                }
                safePlayVideo(localVideo.value, "local");
            });

            console.log("✅ Local video element updated");
        }
    }
);

// Watch for remote stream changes
watch(
    () => chatStore.remoteStream,
    (newStream) => {
        if (newStream) {
            console.log("📹 Remote stream received");
            console.log(
                "🎤 Remote stream has audio tracks:",
                newStream.getAudioTracks().length
            );

            // Attach audio to audio element for playback
            if (remoteAudio.value) {
                remoteAudio.value.srcObject = newStream;
                safePlayAudio(remoteAudio.value);
                console.log("✅ Remote audio stream attached to audio element");
            }

            // Attach video to video element
            if (remoteVideo.value) {
                remoteVideo.value.srcObject = newStream;
                safePlayVideo(remoteVideo.value, "remote");
                console.log("✅ Remote video stream attached");
            }

            // Setup audio analysis
            setupAudioAnalysis(newStream);
        }
    }
);

// Safe video playback with retry logic
function safePlayVideo(videoElement, type) {
    if (!videoElement) return;

    const playPromise = videoElement.play();

    if (playPromise !== undefined) {
        playPromise
            .then(() => {
                console.log(`✅ ${type} video is playing`);
            })
            .catch((error) => {
                console.warn(`⚠️ ${type} video play failed:`, error.message);
                // Retry after a short delay
                setTimeout(() => {
                    if (videoElement.srcObject) {
                        safePlayVideo(videoElement, type);
                    }
                }, 500);
            });
    }
}

// Safe audio playback with retry logic
function safePlayAudio(audioElement) {
    if (!audioElement) return;

    const playPromise = audioElement.play();

    if (playPromise !== undefined) {
        playPromise
            .then(() => {
                console.log("✅ Remote audio is playing");
            })
            .catch((error) => {
                console.warn("⚠️ Remote audio play failed:", error.message);
                // Retry after a short delay
                setTimeout(() => {
                    if (audioElement.srcObject) {
                        safePlayAudio(audioElement);
                    }
                }, 500);
            });
    }
}

function setupAudioAnalysis(stream) {
    try {
        const audioTracks = stream.getAudioTracks();
        if (audioTracks.length === 0) {
            console.warn("⚠️ No audio tracks in remote stream for analysis");
            return;
        }

        // Create audio context if not exists
        if (!audioContext.value) {
            audioContext.value = new (window.AudioContext ||
                window.webkitAudioContext)();
            console.log("✅ Audio context created");
        }

        // Create analyser if not exists
        if (!analyser.value) {
            const audioSource =
                audioContext.value.createMediaStreamSource(stream);
            analyser.value = audioContext.value.createAnalyser();
            analyser.value.fftSize = 256;
            audioSource.connect(analyser.value);
            console.log("✅ Audio analyser setup complete");
        }

        // Start monitoring audio levels
        monitorAudioLevel();
    } catch (error) {
        console.warn("⚠️ Could not setup audio analysis:", error.message);
    }
}

function monitorAudioLevel() {
    if (!analyser.value) return;

    const dataArray = new Uint8Array(analyser.value.frequencyBinCount);

    const checkLevel = () => {
        analyser.value.getByteFrequencyData(dataArray);

        // Calculate average frequency
        const average = dataArray.reduce((a, b) => a + b) / dataArray.length;
        remoteAudioLevel.value = Math.min(100, Math.round(average * 0.5));

        if (callState.value === "connected") {
            requestAnimationFrame(checkLevel);
        }
    };

    checkLevel();
}

onMounted(() => {
    console.log("VideoCall component mounted for user:", authStore.user.id);
    setupWebSocketListeners();
});

onUnmounted(() => {
    console.log("VideoCall component unmounted - cleaning up");
    if (callInterval.value) {
        clearInterval(callInterval.value);
    }
    if (callTimeoutRef.value) {
        clearTimeout(callTimeoutRef.value);
    }
    if (connectionStepsTimeout.value) {
        clearTimeout(connectionStepsTimeout.value);
    }
    cleanupCall();
});

function setupWebSocketListeners() {
    console.log(
        "🔧 Setting up WebSocket listeners for user:",
        authStore.user.id
    );

    if (!authStore.user?.id) {
        console.error(
            "❌ Cannot setup WebSocket listeners: User not authenticated"
        );
        return;
    }

    const userChannel = `user.${authStore.user.id}`;
    console.log("📡 Subscribing to channel:", userChannel);

    // Clean up any existing listeners first
    window.Echo.leave(userChannel);

    window.Echo.private(userChannel)
        .listen(".CallInitiated", (e) => {
            console.log("🎯 CALL INITIATED EVENT RECEIVED:", e);
            handleIncomingCall(e);
        })
        .listen(".CallAccepted", (e) => {
            console.log("✅ Call accepted event received:", e);
            handleCallAccepted(e);
        })
        .listen(".CallRejected", (e) => {
            console.log("❌ Call rejected event:", e);
            handleCallRejected(e);
        })
        .listen(".CallEnded", (e) => {
            console.log("📞 Call ended event:", e);
            handleCallEnded(e);
        })
        .listen(".WebRTCEvent", (e) => {
            console.log("📡 WebRTC signal received:", e.type);
            handleWebRTCEvent(e);
        });

    console.log("✅ Listeners set up for user channel");
}

async function initiateVideoCall() {
    if (!effectiveConversationId.value) {
        console.error("No conversation ID available");
        return;
    }

    try {
        const otherParticipant =
            chatStore.currentConversation?.participants?.find(
                (p) => p.id !== authStore.user.id
            );

        if (!otherParticipant) {
            alert("No other user found in this conversation to call.");
            return;
        }

        console.log("📞 Initiating call to:", otherParticipant.name);
        callState.value = "ringing";
        connectionStatus.value = "new";
        connectionStep.value = 0;
        showConnectionSteps.value = false;

        const response = await axios.post("/api/video-call/initiate", {
            conversation_id: effectiveConversationId.value,
            receiver_id: otherParticipant.id,
        });

        console.log("Call initiated successfully:", response.data);

        chatStore.setVideoCall(response.data.call);
        chatStore.setInCall(true);
        chatStore.setCallActive(true);

        await getLocalMedia();
        setupCallTimeout();

        console.log(
            "✅ Call initiation completed - waiting for receiver to accept"
        );
    } catch (error) {
        console.error("Error initiating call:", error);
        alert(
            "Failed to initiate video call: " +
                (error.response?.data?.message || error.message)
        );
        cleanupCall();
    }
}

async function getLocalMedia() {
    try {
        console.log("🎥 Requesting local media...");
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
                sampleRate: 44100,
                sampleSize: 16,
                channelCount: 1,
            },
        });

        chatStore.setLocalStream(stream);

        await nextTick();

        if (localVideo.value) {
            localVideo.value.srcObject = stream;
            localVideo.value.muted = true;
            safePlayVideo(localVideo.value, "local");
        }

        const allTracks = stream.getTracks();
        console.log(
            "✅ Local media stream obtained, total tracks:",
            allTracks.length
        );

        allTracks.forEach((track, index) => {
            console.log(`Track ${index}: ${track.kind}`, {
                enabled: track.enabled,
                readyState: track.readyState,
                label: track.label,
            });
        });

        const audioTracks = stream.getAudioTracks();
        if (audioTracks.length > 0) {
            console.log("🎤 Audio track found:", audioTracks[0].label);
            if (!audioTracks[0].enabled) {
                console.warn("⚠️ Audio track disabled, enabling...");
                audioTracks[0].enabled = true;
                isAudioEnabled.value = true;
            }
        } else {
            console.warn("⚠️ No audio track found in stream!");
        }

        setTimeout(verifyCameraStream, 500);
        setTimeout(checkVideoElementStatus, 1000);

        return stream;
    } catch (error) {
        console.error("❌ Camera/microphone access denied:", error);
        throw new Error("Camera/microphone access required for video calls");
    }
}

function checkVideoElementStatus() {
    if (localVideo.value) {
        console.log("📹 Local Video Element Status:", {
            srcObject: !!localVideo.value.srcObject,
            readyState: localVideo.value.readyState,
            videoWidth: localVideo.value.videoWidth,
            videoHeight: localVideo.value.videoHeight,
        });
    }

    if (remoteVideo.value) {
        console.log("📹 Remote Video Element Status:", {
            srcObject: !!remoteVideo.value.srcObject,
            readyState: remoteVideo.value.readyState,
            videoWidth: remoteVideo.value.videoWidth,
            videoHeight: remoteVideo.value.videoHeight,
        });
    }

    if (remoteAudio.value) {
        console.log("🎤 Remote Audio Element Status:", {
            srcObject: !!remoteAudio.value.srcObject,
            readyState: remoteAudio.value.readyState,
            paused: remoteAudio.value.paused,
        });
    }
}

function verifyCameraStream() {
    if (chatStore.localStream) {
        const videoTracks = chatStore.localStream.getVideoTracks();
        const audioTracks = chatStore.localStream.getAudioTracks();

        console.log("🎥 Stream Verification:", {
            videoTracks: videoTracks.length,
            audioTracks: audioTracks.length,
            videoEnabled: videoTracks[0]?.enabled,
            audioEnabled: audioTracks[0]?.enabled,
        });

        if (videoTracks.length > 0) {
            const videoTrack = videoTracks[0];
            console.log("📹 Video Track Details:", {
                label: videoTrack.label,
                enabled: videoTrack.enabled,
                readyState: videoTrack.readyState,
            });
        }

        if (audioTracks.length > 0) {
            const audioTrack = audioTracks[0];
            console.log("🎤 Audio Track Details:", {
                label: audioTrack.label,
                enabled: audioTrack.enabled,
                readyState: audioTrack.readyState,
            });
        }
    }
}

function setupCallTimeout() {
    if (callTimeoutRef.value) {
        clearTimeout(callTimeoutRef.value);
    }

    console.log("⏰ Setting up call timeout (60 seconds)");

    callTimeoutRef.value = setTimeout(() => {
        if (callState.value === "ringing") {
            console.warn("⏰ Call timeout - no answer from receiver");
            alert("Call timeout. The other user didn't answer.");
            endCall();
        } else if (
            callState.value === "connecting" &&
            connectionStatus.value === "new"
        ) {
            console.warn("⏰ Call timeout - connection didn't establish");
            alert("Call timeout. Connection could not be established.");
            endCall();
        }
    }, maxCallWaitTime);
}

async function acceptCall() {
    try {
        const callId = chatStore.incomingCall.call.call_id;
        console.log("✅ Accepting incoming call:", callId);

        const callData = chatStore.incomingCall.call;
        chatStore.setVideoCall(callData);
        chatStore.setInCall(true);
        chatStore.setCallActive(true);

        chatStore.clearIncomingCall();

        callState.value = "connecting";
        connectionStatus.value = "new";
        connectionStep.value = 0;
        console.log("📄 Call state set to: connecting");

        await getLocalMedia();

        const response = await axios.post(`/api/video-call/${callId}/accept`);
        console.log("✅ Call accepted on server:", response.data);

        chatStore.setVideoCall(response.data.call);

        await initializeWebRTC();

        console.log(
            "✅ Call accepted and WebRTC initialized - ready for offer"
        );
    } catch (error) {
        console.error("Error accepting call:", error);
        alert("Failed to accept call: " + error.message);
        cleanupCall();
    }
}

async function rejectCall() {
    try {
        const callId = chatStore.incomingCall.call.call_id;
        await axios.post(`/api/video-call/${callId}/reject`);
        chatStore.clearIncomingCall();
    } catch (error) {
        console.error("Error rejecting call:", error);
    }
}

function handleIncomingCall(e) {
    console.log("📞 Handling incoming call event:", e);

    const callData = e.call || e;
    const receiverId = callData.receiver_id || callData.receiver?.id;
    const caller = e.caller || callData.caller;

    if (receiverId == authStore.user.id) {
        console.log("✅ This call is for me! Showing incoming call modal.");
        callState.value = "ringing";

        chatStore.setIncomingCall({
            call: callData,
            caller: caller,
        });
    }
}

async function handleCallAccepted(e) {
    console.log("✅ Call accepted by receiver - initializing WebRTC as caller");

    if (!chatStore.videoCall) {
        console.error("❌ No video call in store when CallAccepted received");
        return;
    }

    if (chatStore.videoCall.call_id === e.call.call_id) {
        console.log("✅ Our call was accepted");

        if (callTimeoutRef.value) {
            clearTimeout(callTimeoutRef.value);
            callTimeoutRef.value = null;
        }

        chatStore.setVideoCall(e.call);
        callState.value = "connecting";

        try {
            await initializeWebRTC();
            console.log("✅ WebRTC initialization completed after acceptance");
        } catch (error) {
            console.error(
                "❌ Failed to initialize WebRTC after acceptance:",
                error
            );
            alert("Failed to establish video connection");
            endCall();
        }
    }
}

async function initializeWebRTC() {
    try {
        console.log("📄 Initializing WebRTC...");

        const configuration = {
            iceServers: [
                { urls: "stun:stun.l.google.com:19302" },
                { urls: "stun:stun1.l.google.com:19302" },
                { urls: "stun:stun2.l.google.com:19302" },
                { urls: "stun:stun3.l.google.com:19302" },
                { urls: "stun:stun4.l.google.com:19302" },
            ],
        };

        const pc = new RTCPeerConnection(configuration);
        chatStore.setPeerConnection(pc);

        console.log("✅ Peer connection created");

        if (chatStore.localStream) {
            const audioTracks = chatStore.localStream.getAudioTracks();
            const videoTracks = chatStore.localStream.getVideoTracks();

            console.log(
                `🎥 Adding tracks - Video: ${videoTracks.length}, Audio: ${audioTracks.length}`
            );

            chatStore.localStream.getTracks().forEach((track) => {
                pc.addTrack(track, chatStore.localStream);
                console.log(
                    `✅ Added ${track.kind} track - enabled: ${track.enabled}`
                );
            });

            if (audioTracks.length > 0) {
                const audioTrack = audioTracks[0];
                if (!audioTrack.enabled) {
                    console.warn("⚠️ Audio track is disabled, enabling it now");
                    audioTrack.enabled = true;
                    isAudioEnabled.value = true;
                }
            }
        }

        setupPeerConnectionHandlers(pc);

        if (isCaller.value) {
            console.log("🤝 Caller: Creating offer");
            await createAndSendOffer(pc);
        } else {
            console.log(
                "👂 Receiver: Ready for offer, waiting for WebRTC events"
            );
            // Process any pending events immediately
            setTimeout(() => {
                processPendingWebRTCEvents();
            }, 300);
        }

        console.log("✅ WebRTC initialization completed");
    } catch (error) {
        console.error("❌ Error initializing WebRTC:", error);
        throw error;
    }
}

async function handleWebRTCEvent(event) {
    console.log("📡 WebRTC event received:", event.type);

    // Create unique event ID to avoid duplicates
    const eventId = `${event.type}-${
        event.data?.sdp?.substring(0, 50) ||
        event.data?.candidate ||
        Math.random()
    }`;

    if (processedEventIds.value.has(eventId)) {
        console.log("🔄 Duplicate WebRTC event detected, skipping:", eventId);
        return;
    }

    processedEventIds.value.add(eventId);
    pendingWebRTCEvents.value.push(event);

    if (
        chatStore.peerConnection &&
        callState.value !== "idle" &&
        callState.value !== "ringing"
    ) {
        console.log("✅ Ready to process WebRTC event immediately");
        await processPendingWebRTCEvents();
    } else {
        console.log(
            "⏳ Storing WebRTC event - State:",
            callState.value,
            "Has PC:",
            !!chatStore.peerConnection
        );
    }
}

async function processPendingWebRTCEvents() {
    if (isProcessingWebRTC.value) {
        console.log("⏳ Already processing WebRTC events, skipping...");
        return;
    }

    if (pendingWebRTCEvents.value.length === 0) {
        console.log("✅ No pending WebRTC events to process");
        return;
    }

    if (!chatStore.peerConnection) {
        console.log("⏳ No peer connection yet, keeping events pending");
        return;
    }

    isProcessingWebRTC.value = true;
    console.log(
        `📄 Starting to process ${pendingWebRTCEvents.value.length} pending WebRTC events`
    );

    try {
        const eventsToProcess = [...pendingWebRTCEvents.value];
        pendingWebRTCEvents.value = [];

        for (const event of eventsToProcess) {
            try {
                await processWebRTCEvent(event);
                await new Promise((resolve) => setTimeout(resolve, 50)); // Small delay between events
            } catch (error) {
                console.error(
                    "❌ Error processing individual WebRTC event:",
                    error
                );
                // Only requeue if it's a state error and not a duplicate
                if (
                    (error.name === "InvalidStateError" ||
                        error.message.includes("state")) &&
                    !error.message.includes("already")
                ) {
                    console.log("⏳ State error, re-queuing event for retry");
                    pendingWebRTCEvents.value.push(event);
                } else if (error.message.includes("already")) {
                    console.log("🔄 Event already processed, skipping");
                } else {
                    console.error("❌ Fatal WebRTC error, not re-queuing");
                }
            }
        }
        console.log("✅ All pending WebRTC events processed");
    } catch (error) {
        console.error("❌ Error in processPendingWebRTCEvents:", error);
    } finally {
        isProcessingWebRTC.value = false;

        // Clean up old processed event IDs to prevent memory leaks
        if (processedEventIds.value.size > 100) {
            processedEventIds.value = new Set();
        }

        if (pendingWebRTCEvents.value.length > 0) {
            console.log(
                `⏳ ${pendingWebRTCEvents.value.length} events still pending, scheduling retry`
            );
            setTimeout(() => {
                processPendingWebRTCEvents();
            }, 1000);
        }
    }
}

async function processWebRTCEvent(event) {
    const pc = chatStore.peerConnection;
    if (!pc) {
        console.warn("❌ No peer connection for processing event");
        return;
    }

    try {
        console.log(
            `📄 Processing WebRTC event: ${event.type}, Signaling state: ${pc.signalingState}`
        );

        switch (event.type) {
            case "offer":
                console.log("🔥 Processing WebRTC offer");

                if (!event.data || !event.data.sdp) {
                    console.error("❌ Invalid offer: missing SDP data");
                    return;
                }

                if (pc.signalingState !== "stable") {
                    console.log(
                        "⏳ Waiting for stable state, current:",
                        pc.signalingState
                    );
                    pendingWebRTCEvents.value.unshift(event);
                    return;
                }

                try {
                    console.log("📄 Setting remote description from offer...");

                    const cleanedSDP = cleanSDP(event.data.sdp);

                    if (
                        !cleanedSDP.includes("v=0") ||
                        !cleanedSDP.includes("m=")
                    ) {
                        console.error("❌ Invalid SDP after cleaning");
                        throw new Error("Invalid SDP format after cleaning");
                    }

                    const offerData = {
                        type: event.data.type,
                        sdp: cleanedSDP,
                    };

                    await pc.setRemoteDescription(
                        new RTCSessionDescription(offerData)
                    );
                    console.log("✅ Remote description set from offer");

                    await createAndSendAnswer(pc);
                } catch (error) {
                    console.error("❌ Error processing offer:", error);
                    throw error;
                }
                break;

            case "answer":
                console.log("🔥 Processing WebRTC answer");

                // Check if we're in the right state for an answer
                if (pc.signalingState !== "have-local-offer") {
                    console.warn(
                        `⚠️ Unexpected signaling state for answer: ${pc.signalingState}, expected: have-local-offer`
                    );

                    // If we're already stable, the answer was probably already processed
                    if (pc.signalingState === "stable") {
                        console.log("🔄 Answer already processed, skipping");
                        return;
                    }

                    pendingWebRTCEvents.value.unshift(event);
                    return;
                }

                try {
                    console.log("📄 Setting remote description from answer...");

                    const cleanedAnswerSDP = cleanSDP(event.data.sdp);

                    let fixedData = {
                        type: event.data.type,
                        sdp: cleanedAnswerSDP,
                    };
                    if (fixedData.sdp) {
                        fixedData.sdp = fixAnswerSDP(fixedData.sdp);
                    }

                    await pc.setRemoteDescription(
                        new RTCSessionDescription(fixedData)
                    );
                    console.log("✅ Remote description set from answer");
                    connectionStatus.value = "connected";
                } catch (error) {
                    console.error("❌ Error processing answer:", error);
                    throw error;
                }
                break;

            case "candidate":
                console.log("🔥 Processing ICE candidate");
                if (event.data) {
                    if (pc.remoteDescription) {
                        try {
                            const candidateData = {
                                candidate: event.data.candidate,
                                sdpMLineIndex: event.data.sdpMLineIndex,
                                sdpMid: event.data.sdpMid,
                            };

                            await pc.addIceCandidate(
                                new RTCIceCandidate(candidateData)
                            );
                            console.log("✅ ICE candidate added successfully");
                        } catch (err) {
                            // Ignore duplicate candidate errors
                            if (err.message.includes("already")) {
                                console.log("🔄 ICE candidate already added");
                            } else {
                                console.warn(
                                    "⚠️ Failed to add ICE candidate:",
                                    err.message
                                );
                            }
                        }
                    } else {
                        console.log(
                            "⏳ No remote description yet, re-queuing ICE candidate"
                        );
                        pendingWebRTCEvents.value.push(event);
                    }
                }
                break;

            default:
                console.warn("❌ Unknown WebRTC event type:", event.type);
        }
    } catch (error) {
        console.error("❌ Error processing WebRTC event:", error);

        if (
            error.name === "InvalidStateError" ||
            error.message.includes("state")
        ) {
            console.log("⏳ State error, re-queuing event for retry");
            pendingWebRTCEvents.value.push(event);
        } else if (
            error.message.includes("SDP") ||
            error.message.includes("parse")
        ) {
            console.error("❌ SDP parsing error - cannot recover, ending call");
            alert(
                "Video connection failed due to incompatible media formats. Please try again."
            );
            endCall();
        } else if (error.message.includes("already")) {
            console.log("🔄 Event already processed, skipping");
        } else {
            console.error("❌ Unexpected WebRTC error:", error);
        }
    }
}

function cleanSDP(sdpString) {
    if (!sdpString || typeof sdpString !== "string") {
        console.warn("⚠️ Invalid SDP string");
        return sdpString;
    }

    try {
        const lines = sdpString.split("\r\n");
        const cleanedLines = [];

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();

            if (!line) {
                continue;
            }

            if (line.startsWith("a=ssrc:") && line.includes("msid:")) {
                const ssrcMatch = line.match(/^a=ssrc:(\d+)\s+(.*)/);
                if (ssrcMatch) {
                    const ssrcId = ssrcMatch[1];
                    const rest = ssrcMatch[2];

                    if (rest.includes("msid:")) {
                        const msidParts = rest.split(/\s+/);
                        let msidValue = null;

                        for (let j = 0; j < msidParts.length; j++) {
                            if (msidParts[j].startsWith("msid:")) {
                                msidValue = msidParts[j].substring(5);
                                break;
                            }
                        }

                        if (msidValue) {
                            const newLine = `a=ssrc:${ssrcId} msid:${msidValue}`;
                            cleanedLines.push(newLine);
                        } else {
                            cleanedLines.push(line);
                        }
                    } else {
                        cleanedLines.push(line);
                    }
                } else {
                    cleanedLines.push(line);
                }
            } else if (
                line.startsWith("a=msid:") &&
                line.split(/\s+/).length > 2
            ) {
                const parts = line.split(/\s+/);
                if (parts.length >= 2) {
                    cleanedLines.push(`${parts[0]} ${parts[1]}`);
                } else {
                    cleanedLines.push(line);
                }
            } else if (line.startsWith("a=ssrc-group:")) {
                if (line.includes("FID")) {
                    const parts = line.split(/\s+/);
                    if (parts.length >= 4) {
                        cleanedLines.push(
                            `a=ssrc-group:${parts[1]} ${parts[2]} ${parts[3]}`
                        );
                    } else {
                        cleanedLines.push(line);
                    }
                } else {
                    cleanedLines.push(line);
                }
            } else {
                cleanedLines.push(line);
            }
        }

        return cleanedLines.join("\r\n") + "\r\n";
    } catch (error) {
        console.error("⚠️ Error cleaning SDP:", error);
        return sdpString;
    }
}

function fixAnswerSDP(sdpString) {
    if (!sdpString || typeof sdpString !== "string") {
        console.warn("⚠️ Invalid SDP string");
        return sdpString;
    }

    return sdpString
        .split("\r\n")
        .map((line) => {
            if (line.includes("a=setup:")) {
                return "a=setup:active";
            }
            return line;
        })
        .join("\r\n");
}

function setupPeerConnectionHandlers(pc) {
    pc.ontrack = (event) => {
        console.log("🎥 Remote track received:", event.track.kind);

        if (event.track.kind === "audio") {
            console.log("🎤 Audio track received");
        }

        if (event.streams && event.streams[0]) {
            const remoteStream = event.streams[0];
            chatStore.setRemoteStream(remoteStream);

            console.log("🎥 Remote stream set with tracks:", {
                audioTracks: remoteStream.getAudioTracks().length,
                videoTracks: remoteStream.getVideoTracks().length,
            });

            const remoteAudioTracks = remoteStream.getAudioTracks();
            if (remoteAudioTracks.length > 0) {
                console.log("🎤 Remote audio track found:", {
                    label: remoteAudioTracks[0].label,
                    enabled: remoteAudioTracks[0].enabled,
                });
                if (!remoteAudioTracks[0].enabled) {
                    remoteAudioTracks[0].enabled = true;
                }
            }

            nextTick(() => {
                if (remoteVideo.value) {
                    remoteVideo.value.srcObject = remoteStream;
                    safePlayVideo(remoteVideo.value, "remote");
                }
                if (remoteAudio.value) {
                    remoteAudio.value.srcObject = remoteStream;
                    safePlayAudio(remoteAudio.value);
                    console.log("✅ Remote audio attached");
                }
            });

            console.log("✅ Remote streams set");

            if (!callStartTime.value) {
                startCallTimer();
            }

            setTimeout(checkVideoElementStatus, 500);
        }
    };

    pc.onicecandidate = (event) => {
        if (event.candidate) {
            console.log("❄️ ICE candidate generated:", event.candidate.type);
            const targetUserId = isCaller.value
                ? chatStore.videoCall.receiver_id
                : chatStore.videoCall.caller_id;

            const candidateData = {
                candidate: event.candidate.candidate,
                sdpMLineIndex: event.candidate.sdpMLineIndex,
                sdpMid: event.candidate.sdpMid,
            };

            axios
                .post(`/api/video-call/${chatStore.videoCall.call_id}/signal`, {
                    type: "candidate",
                    data: candidateData,
                    target_user_id: targetUserId,
                })
                .catch((error) => {
                    console.error("❌ Failed to send ICE candidate:", error);
                });
        }
    };

    pc.onconnectionstatechange = () => {
        console.log("📄 Peer connection state:", pc.connectionState);
        connectionStatus.value = pc.connectionState;

        switch (pc.connectionState) {
            case "connected":
                console.log("✅ WebRTC connection established!");

                const senders = pc.getSenders();
                const audioSenders = senders.filter(
                    (s) => s.track?.kind === "audio"
                );
                console.log("🎤 Audio senders:", audioSenders.length);
                audioSenders.forEach((sender) => {
                    if (sender.track) {
                        console.log("🎤 Audio sender track:", {
                            enabled: sender.track.enabled,
                            readyState: sender.track.readyState,
                        });
                    }
                });

                callState.value = "connected";
                chatStore.setCallActive(true);
                if (!callStartTime.value) {
                    startCallTimer();
                }
                break;
            case "connecting":
                console.log("📄 WebRTC connecting...");
                callState.value = "connecting";
                break;
            case "failed":
                console.error("❌ WebRTC connection failed");
                alert("Video connection failed. Please try again.");
                endCall();
                break;
            case "disconnected":
                console.warn("⚠️ WebRTC disconnected");
                break;
            case "closed":
                console.log("📞 WebRTC connection closed");
                break;
        }
    };

    pc.onsignalingstatechange = () => {
        console.log("📢 Signaling state changed:", pc.signalingState);
    };

    pc.oniceconnectionstatechange = () => {
        console.log("❄️ ICE connection state:", pc.iceConnectionState);
    };
}

async function createAndSendOffer(pc) {
    try {
        const offerOptions = {
            offerToReceiveAudio: true,
            offerToReceiveVideo: true,
            voiceActivityDetection: false,
        };

        console.log("📄 Creating offer...");
        const offer = await pc.createOffer(offerOptions);

        console.log("📄 Setting local description...");
        await pc.setLocalDescription(offer);
        console.log("✅ Local description set (offer)");

        console.log("🤝 Sending offer to receiver...");

        const offerData = {
            type: offer.type,
            sdp: offer.sdp,
        };

        console.log("📋 Offer SDP length:", offerData.sdp.length);

        await axios.post(
            `/api/video-call/${chatStore.videoCall.call_id}/signal`,
            {
                type: "offer",
                data: offerData,
                target_user_id: chatStore.videoCall.receiver_id,
            }
        );

        console.log("✅ WebRTC offer sent to receiver");
    } catch (error) {
        console.error("❌ Error creating/sending offer:", error);
        throw new Error("Failed to create offer: " + error.message);
    }
}

async function createAndSendAnswer(pc) {
    try {
        console.log("📄 Creating answer...");
        const answerOptions = {
            voiceActivityDetection: false,
        };

        const answer = await pc.createAnswer(answerOptions);
        await pc.setLocalDescription(answer);
        console.log("✅ Local description set from answer");

        console.log("🤝 Sending answer to caller...");

        const answerData = {
            type: answer.type,
            sdp: answer.sdp,
        };

        console.log("📋 Answer SDP length:", answerData.sdp.length);

        await axios.post(
            `/api/video-call/${chatStore.videoCall.call_id}/signal`,
            {
                type: "answer",
                data: answerData,
                target_user_id: chatStore.videoCall.caller_id,
            }
        );
        console.log("✅ WebRTC answer sent to caller");

        callState.value = "connecting";
        console.log("📄 Call state updated to: connecting");
    } catch (error) {
        console.error("❌ Error creating/sending answer:", error);
        throw error;
    }
}

async function endCall() {
    try {
        if (chatStore.videoCall) {
            const callId = chatStore.videoCall.call_id;
            console.log("📞 Ending call:", callId);
            await axios.post(`/api/video-call/${callId}/end`);
        }
    } catch (error) {
        console.error("Error ending call:", error);
    } finally {
        cleanupCall();
    }
}

function cleanupCall() {
    console.log("🧹 Cleaning up call");

    if (callTimeoutRef.value) {
        clearTimeout(callTimeoutRef.value);
        callTimeoutRef.value = null;
    }

    if (connectionStepsTimeout.value) {
        clearTimeout(connectionStepsTimeout.value);
        connectionStepsTimeout.value = null;
    }

    if (chatStore.localStream) {
        chatStore.localStream.getTracks().forEach((track) => {
            track.stop();
            console.log(`🛑 Stopped ${track.kind} track`);
        });
    }

    if (chatStore.peerConnection) {
        chatStore.peerConnection.close();
        console.log("🔐 Peer connection closed");
    }

    if (remoteAudio.value) {
        remoteAudio.value.srcObject = null;
    }

    if (audioContext.value) {
        audioContext.value.close();
        audioContext.value = null;
    }

    chatStore.setLocalStream(null);
    chatStore.setRemoteStream(null);
    chatStore.setPeerConnection(null);
    chatStore.setVideoCall(null);
    chatStore.setInCall(false);
    chatStore.setCallActive(false);

    pendingWebRTCEvents.value = [];
    isProcessingWebRTC.value = false;
    processedEventIds.value.clear();

    if (callInterval.value) {
        clearInterval(callInterval.value);
        callInterval.value = null;
    }

    callDuration.value = 0;
    callStartTime.value = null;
    callState.value = "idle";
    connectionStatus.value = "disconnected";
    connectionStep.value = 0;
    showConnectionSteps.value = false;
    remoteAudioLevel.value = 0;
    analyser.value = null;

    console.log("✅ Call cleanup completed");
}

function handleCallRejected(event) {
    console.log("❌ Call was rejected");
    alert("Call was rejected");
    cleanupCall();
}

function handleCallEnded(event) {
    console.log("📞 Call ended by other party");
    alert("Call ended");
    cleanupCall();
}

function getOtherParticipantName() {
    if (!chatStore.videoCall) return "";

    if (chatStore.videoCall.caller_id === authStore.user.id) {
        return chatStore.videoCall.receiver?.name || "Receiver";
    } else {
        return chatStore.videoCall.caller?.name || "Caller";
    }
}

function toggleVideo() {
    if (chatStore.localStream) {
        const videoTrack = chatStore.localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            isVideoEnabled.value = videoTrack.enabled;
            console.log(
                `🎥 Video ${videoTrack.enabled ? "enabled" : "disabled"}`
            );
        }
    }
}

function toggleAudio() {
    if (chatStore.localStream) {
        const audioTrack = chatStore.localStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            isAudioEnabled.value = audioTrack.enabled;
            console.log(
                `🎤 Audio ${audioTrack.enabled ? "enabled" : "disabled"}`
            );
        }
    }
}

function startCallTimer() {
    if (callStartTime.value) return;

    callStartTime.value = new Date();
    callInterval.value = setInterval(() => {
        callDuration.value = Math.floor(
            (new Date() - callStartTime.value) / 1000
        );
    }, 1000);
}

function getConnectionStatusText() {
    if (!chatStore.peerConnection) return "Initializing...";

    switch (connectionStatus.value) {
        case "connecting":
            return "Connecting...";
        case "connected":
            return "Connected";
        case "disconnected":
            return "Disconnected";
        case "failed":
            return "Connection Failed";
        case "new":
            return "Starting...";
        default:
            return connectionStatus.value;
    }
}

function onLocalMetadataLoaded() {
    console.log("✅ Local metadata loaded:", {
        width: localVideo.value.videoWidth,
        height: localVideo.value.videoHeight,
    });
    videoDebugInfo.value.local.metadataLoaded = true;
}

function onLocalVideoPlaying() {
    console.log("✅ Local video is playing");
    videoDebugInfo.value.local.playing = true;
}

function onLocalVideoError(e) {
    console.error("❌ Local video error:", e);
    videoDebugInfo.value.local.error = e.message;
}

function onRemoteMetadataLoaded() {
    console.log("✅ Remote metadata loaded:", {
        width: remoteVideo.value.videoWidth,
        height: remoteVideo.value.videoHeight,
    });
    videoDebugInfo.value.remote.metadataLoaded = true;
}

function onRemoteVideoPlaying() {
    console.log("✅ Remote video is playing");
    videoDebugInfo.value.remote.playing = true;
}

function onRemoteVideoError(e) {
    console.error("❌ Remote video error:", e);
    videoDebugInfo.value.remote.error = e.message;
}

function onRemoteAudioMetadataLoaded() {
    console.log("✅ Remote audio metadata loaded");
}

function onRemoteAudioPlaying() {
    console.log("✅ Remote audio is playing");
}

function onRemoteAudioError(e) {
    console.error("❌ Remote audio error:", e);
}
</script>
