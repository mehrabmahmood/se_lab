<?php
require 'config.php';
require_login();
if ($_SESSION['user_type'] !== 'expert') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expert Dashboard - Agri Consultancy</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-green-700">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Expert)</h1>
            <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">Logout</a>
        </div>

        <!-- Pending Farmer Issues -->
        <div class="bg-white p-8 rounded-xl shadow-lg mb-10">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Pending Farmer Issues</h2>
            <div id="issuesList" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <p class="text-gray-500 text-center py-10">Waiting for farmer issues...</p>
            </div>
        </div>

        <!-- Chat & Video Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Chat Section -->
            <div class="bg-white p-8 rounded-xl shadow-lg">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Chat with Farmer</h2>
                <div id="chatMessages" class="h-80 overflow-y-auto border border-gray-200 p-5 mb-6 bg-gray-50 rounded-lg"></div>
                <div class="flex">
                    <input type="text" id="chatInput" placeholder="Type your reply..." class="flex-1 p-4 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button onclick="sendChatMessage()" class="bg-green-600 text-white px-8 rounded-r-lg hover:bg-green-700 font-semibold">Send</button>
                </div>
            </div>

            <!-- Video Consultation Section -->
            <div class="bg-white p-8 rounded-xl shadow-lg">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Video Consultation</h2>

                <div id="callNotification" class="text-lg font-semibold text-purple-700 mb-6 min-h-[3rem]"></div>

                <button id="acceptButton" onclick="acceptCall()" class="bg-green-600 text-white px-8 py-4 rounded-lg hover:bg-green-700 mb-6 font-semibold hidden w-full">
                    Accept Call
                </button>

                <button id="endCallButton" onclick="endCall()" class="bg-red-600 text-white px-8 py-4 rounded-lg hover:bg-red-800 mb-6 font-semibold hidden flex items-center justify-center gap-3 w-full">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6.62 3.79c-.43-.4-1.08-.4-1.5 0l-2.2 2.2c-.43.43-.43 1.08 0 1.5l3.6 3.6c.43.43 1.08.43 1.5 0l2.2-2.2c.43-.43.43-1.08 0-1.5l-3.6-3.6zM17.5 14.9l-3.6-3.6c-.43-.43-1.08-.43-1.5 0l-2.2 2.2c-.43.43-.43 1.08 0 1.5l3.6 3.6c.43.43 1.08.43 1.5 0l2.2-2.2c.43-.43.43-1.08 0-1.5z"/>
                    </svg>
                    End Call
                </button>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600 mb-2 font-medium">Your Video</p>
                        <video id="localVideo" autoplay playsinline muted class="w-full rounded-lg border bg-black aspect-video"></video>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-2 font-medium">Farmer Video</p>
                        <video id="remoteVideo" autoplay playsinline class="w-full rounded-lg border bg-black aspect-video"></video>
                    </div>
                </div>

                <div class="mt-8 flex justify-center gap-6">
                    <button id="muteAudioBtn" onclick="toggleAudio()" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-800 hidden flex items-center gap-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4z"/>
                            <path d="M5.5 9.5a.5.5 0 01.5.5v1a4 4 0 008 0v-1a.5.5 0 011 0v1a5 5 0 11-10 0v-1a.5.5 0 01.5-.5z"/>
                        </svg>
                        Mute Mic
                    </button>
                    <button id="muteVideoBtn" onclick="toggleVideo()" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-800 hidden flex items-center gap-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v1l4-2v12l-4-2v1a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/>
                        </svg>
                        Camera Off
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const userType = 'expert';
        const userId = <?php echo $_SESSION['user_id']; ?>;
        let ws = new WebSocket('ws://localhost:8080');
        let localStream = null;
        let peerConnection = null;
        let currentRoomId = null;
        let isAudioMuted = false;
        let isVideoMuted = false;

        ws.onopen = () => {
            console.log('WebSocket connected (expert)');
            ws.send(JSON.stringify({ type: 'join', userType, userId, roomId: 'lobby' }));
        };

        ws.onclose = () => console.log('WebSocket closed (expert)');
        ws.onerror = (err) => console.error('WebSocket error (expert):', err);

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            console.log('Expert received:', data);

            if (data.type === 'new-issue') {
                displayIssue(data.issue);
                currentRoomId = data.roomId;
                ws.send(JSON.stringify({ type: 'join', userType, userId, roomId: currentRoomId }));
            }
            else if (data.type === 'chat') {
                document.getElementById('chatMessages').innerHTML += 
                    `<p class="text-left bg-gray-100 p-3 rounded-lg mb-3 max-w-[80%]">${data.message}</p>`;
                document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
            }
            else if (data.type === 'call-request') {
                document.getElementById('callNotification').innerText = 'Incoming call from farmer!';
                document.getElementById('acceptButton').classList.remove('hidden');
            }
            else if (data.type === 'offer') {
                handleOffer(data.offer);
            }
            else if (data.type === 'answer') {
                handleAnswer(data.answer);
            }
            else if (data.type === 'candidate') {
                handleCandidate(data.candidate);
            }
            else if (data.type === 'call-ended') {
                endCall();
            }
        };

        function displayIssue(issue) {
            const list = document.getElementById('issuesList');
            list.innerHTML = '';
            list.innerHTML += `
                <div class="border border-gray-200 p-6 rounded-lg bg-gray-50 shadow-sm">
                    <img src="${issue.imagePath}" alt="Crop Issue" class="w-full max-w-xs rounded-lg mb-4 mx-auto">
                    <p class="text-gray-800">${issue.description}</p>
                </div>
            `;
        }

        function sendChatMessage() {
            if (!currentRoomId) return alert('Wait for a farmer issue first');
            const message = document.getElementById('chatInput').value.trim();
            if (!message) return;

            ws.send(JSON.stringify({ type: 'chat', message, roomId: currentRoomId }));
            document.getElementById('chatMessages').innerHTML += 
                `<p class="text-right bg-blue-100 p-3 rounded-lg mb-3 max-w-[80%] ml-auto">${message}</p>`;
            document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
            document.getElementById('chatInput').value = '';
        }

        async function acceptCall() {
            document.getElementById('acceptButton').classList.add('hidden');
            document.getElementById('callNotification').innerText = 'Call connected';

            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('localVideo').srcObject = localStream;

                peerConnection = new RTCPeerConnection({
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' }
                    ]
                });

                console.log('Expert: peerConnection created successfully');

                peerConnection.ontrack = (event) => {
                    console.log('Expert: Remote track received!', event);
                    console.log('Expert: Remote streams:', event.streams);
                    if (event.streams && event.streams[0]) {
                        document.getElementById('remoteVideo').srcObject = event.streams[0];
                        document.getElementById('remoteVideo').play().catch(e => console.error('Expert: Remote play error:', e));
                    } else {
                        console.warn('Expert: No remote stream in ontrack event');
                    }
                };

                localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

                peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        console.log('Expert: Sending ICE candidate:', event.candidate);
                        ws.send(JSON.stringify({ type: 'candidate', candidate: event.candidate, roomId: currentRoomId }));
                    }
                };

                document.getElementById('endCallButton').classList.remove('hidden');
                document.getElementById('muteAudioBtn').classList.remove('hidden');
                document.getElementById('muteVideoBtn').classList.remove('hidden');

            } catch (err) {
                console.error('Expert: Failed to accept call:', err);
                alert('Cannot access camera or microphone. Please allow permission.');
            }
        }

        function endCall() {
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }

            document.getElementById('localVideo').srcObject = null;
            document.getElementById('remoteVideo').srcObject = null;
            document.getElementById('callNotification').innerText = '';

            document.getElementById('endCallButton').classList.add('hidden');
            document.getElementById('muteAudioBtn').classList.add('hidden');
            document.getElementById('muteVideoBtn').classList.add('hidden');

            ws.send(JSON.stringify({ type: 'call-ended', roomId: currentRoomId }));
        }

        function toggleAudio() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    isAudioMuted = !isAudioMuted;
                    audioTrack.enabled = !isAudioMuted;
                    document.getElementById('muteAudioBtn').innerHTML = isAudioMuted 
                        ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4z"/><path d="M5.5 9.5a.5.5 0 01.5.5v1a4 4 0 008 0v-1a.5.5 0 011 0v1a5 5 0 11-10 0v-1a.5.5 0 01.5-.5z"/></svg> Unmute Mic'
                        : '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4z"/><path d="M5.5 9.5a.5.5 0 01.5.5v1a4 4 0 008 0v-1a.5.5 0 011 0v1a5 5 0 11-10 0v-1a.5.5 0 01.5-.5z"/></svg> Mute Mic';
                }
            }
        }

        function toggleVideo() {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    isVideoMuted = !isVideoMuted;
                    videoTrack.enabled = !isVideoMuted;
                    document.getElementById('muteVideoBtn').innerHTML = isVideoMuted 
                        ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v1l4-2v12l-4-2v1a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/></svg> Camera On'
                        : '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v1l4-2v12l-4-2v1a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/></svg> Camera Off';
                }
            }
        }

        async function handleOffer(offer) {
            console.log('Expert: Received offer from farmer → starting to create answer');
            console.log('Offer details:', offer);

            if (!peerConnection) {
                console.error('Expert: No peerConnection exists when handling offer!');
                return;
            }

            try {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
                console.log('Expert: Successfully set remote description (offer)');

                const answer = await peerConnection.createAnswer();
                console.log('Expert: Answer created →', answer);

                await peerConnection.setLocalDescription(answer);
                console.log('Expert: Local description set to answer');

                console.log('Expert: Now sending answer back to farmer');
                ws.send(JSON.stringify({ 
                    type: 'answer', 
                    answer: peerConnection.localDescription, 
                    roomId: currentRoomId 
                }));
                console.log('Expert: Answer message sent via WebSocket');
            } catch (err) {
                console.error('Expert: Error while handling offer:', err);
            }
        }

        async function handleAnswer(answer) {
            console.log('Expert: Received answer (should not happen on expert side)');
            if (!peerConnection) return;
            await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
        }

        async function handleCandidate(candidate) {
            console.log('Expert: Handling ICE candidate from farmer:', candidate);
            if (!peerConnection) return;
            await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
        }
    </script>
</body>
</html>