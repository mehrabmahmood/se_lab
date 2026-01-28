<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Must use shared auth/session system (prevents redirect loop)
$user = require_user_access();

if (is_admin()) {
    redirect_path(APP_BASE_URL . '/admin/dashboard.php');
    exit;
}

if ((string)($user['role'] ?? '') !== 'volunteer') {
    // If not volunteer, send them to router
    redirect_path(APP_BASE_URL . '/index.php');
    exit;
}

$displayName = (string)($user['full_name'] ?? 'Volunteer');
$volunteerId = (int)($user['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Volunteer Dashboard - Pet Rescue</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body { background-color: #efe7dd; }
</style>
</head>
<body class="min-h-screen font-sans text-gray-700">

<!-- Sidebar Toggle Button -->
<button id="sidebar-toggle" 
    class="fixed top-5 left-5 z-50 p-3 rounded-full bg-[#2c3e50] text-white shadow-lg hover:bg-[#1f2a3a] transition">
    📍
</button>

<!-- Sidebar Map Panel -->
<div id="sidebar" class="fixed top-20 left-5 w-80 h-[400px] bg-white shadow-2xl rounded-lg overflow-hidden hidden z-40">
    <div id="pendingMap" class="w-full h-full flex items-center justify-center text-gray-400 select-none">
        Loading pending reports...
    </div>
</div>

<!-- Main Content -->
<div class="max-w-5xl mx-auto px-6 pt-16 pb-12">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-[#2c3e50]">
            Welcome, <?= h($displayName) ?> (Volunteer)
        </h2>
        <a href="<?= h(APP_BASE_URL) ?>/logout.php" class="text-[#e74c3c] font-semibold hover:underline transition">Logout</a>
    </div>

    <div class="mb-6">
        <a href="saved_reports.php" class="inline-block bg-[#a1866f] hover:bg-[#8b6f56] text-white font-semibold py-2 px-5 rounded-lg shadow-md transition">
            View My Saved Reports
        </a>
    </div>

    <h3 class="text-2xl font-semibold mb-4 text-[#2c3e50]">Nearest Farmar Help Requests</h3>
    <div id="notifications" class="space-y-8 text-gray-800">Fetching location...</div>
</div>

<script>
const currentVolunteerId = <?= json_encode($volunteerId) ?>;
let volunteerLocation = null;

// Get volunteer geolocation
function getVolunteerLocation(){
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
            pos => { 
                volunteerLocation = {lat: pos.coords.latitude, lng: pos.coords.longitude}; 
                fetchNotifications(); 
            },
            err => { 
                alert("Location denied. Showing all reports."); 
                fetchNotifications(); 
            }
        );
    } else { 
        alert("Geolocation not supported."); 
        fetchNotifications(); 
    }
}

// Fetch and display notifications
async function fetchNotifications(){
    const res = await fetch('fetch_reports.php');
    let reports = await res.json();

    if(volunteerLocation){
        reports.sort((a,b)=>getDistance(volunteerLocation.lat,volunteerLocation.lng,a.latitude,a.longitude)-getDistance(volunteerLocation.lat,volunteerLocation.lng,b.latitude,b.longitude));
    }

    let html = '';
    if(reports.length===0){
        html = '<p class="text-center text-lg font-medium text-gray-600">No new reports available.</p>';
    } else {
        reports.forEach((report,index)=>{
            html+=`
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition flex flex-col md:flex-row gap-6" id="report-${index}">
                <div class="flex-shrink-0 w-full md:w-1/3 cursor-pointer" onclick="toggleMapSize(${index})">
                    <div id="map-${index}" class="rounded-lg h-48 w-full"></div>
                </div>
                <div class="flex-grow">
                    <p class="text-gray-900 font-semibold text-lg mb-1">${report.description}</p>
                    <p class="text-sm text-gray-600 mb-1"><strong>Location:</strong> Lat: ${report.latitude}, Lng: ${report.longitude}</p>
                    ${volunteerLocation?`<p class="text-sm text-gray-600 mb-3"><strong>Distance:</strong> ${getDistance(volunteerLocation.lat,volunteerLocation.lng,report.latitude,report.longitude).toFixed(2)} km</p>`:''}
                    <img src="${report.photo_path}" alt="Animal Photo" class="rounded-lg border border-gray-300 max-w-full max-h-48 object-cover" onerror="this.alt='Image not found'; this.style.border='2px solid #e74c3c';"/>
                    <p class="mt-2 text-xs text-gray-500 italic">Reported at: ${report.created_at}</p>

                    <div class="mt-4 space-x-3">
            `;

            if(report.status==='on_the_way'){
                if(parseInt(report.volunteer_id)===parseInt(currentVolunteerId)){
                    html+=`
                        <button onclick="markSaved(${index},${report.id})" class="bg-[#6b705c] hover:bg-[#5a5c4f] text-white font-semibold py-2 px-4 rounded-lg transition shadow">Mark as Saved</button>
                        <button onclick="openFeedback(${report.id})" class="bg-[#f4a261] hover:bg-[#e09b3e] text-white font-semibold py-2 px-4 rounded-lg transition shadow">Feedback</button>
                        <span class="inline-block text-[#f4a261] font-semibold ml-3">You are on the way</span>
                    `;
                } else {
                    html+=`<p class="inline-block text-[#2a9d8f] font-semibold">Assigned: ${report.volunteer_name} is on the way</p>`;
                }
            } else {
                html+=`
                    <button onclick="markSaved(${index},${report.id})" class="bg-[#6b705c] hover:bg-[#5a5c4f] text-white font-semibold py-2 px-4 rounded-lg transition shadow">Mark as Saved</button>
                    <button onclick="markOnTheWay(${report.id})" class="bg-[#2a9d8f] hover:bg-[#21767a] text-white font-semibold py-2 px-4 rounded-lg transition shadow">On The Way</button>
                    <button onclick="openFeedback(${report.id})" class="bg-[#f4a261] hover:bg-[#e09b3e] text-white font-semibold py-2 px-4 rounded-lg transition shadow">Feedback</button>
                `;
            }

            html+=`</div></div></div>`;
        });
    }

    document.getElementById('notifications').innerHTML = html;

    // Initialize maps after rendering
    if(typeof google !== 'undefined' && google.maps){
        reports.forEach((report,index)=>{
            initMap(index, parseFloat(report.latitude), parseFloat(report.longitude));
        });
    }
}

// Distance calculation in KM
function getDistance(lat1, lon1, lat2, lon2){
    const R = 6371;
    const dLat = (lat2-lat1) * Math.PI/180;
    const dLon = (lon2-lon1) * Math.PI/180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Mark report saved
async function markSaved(index, reportId){
    const res = await fetch('mark_saved.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `report_id=${encodeURIComponent(reportId)}`
    });
    const data = await res.json();
    if(data.success){
        alert("Marked as saved!");
        document.getElementById(`report-${index}`).remove();
    } else {
        alert(data.message || "Failed to mark saved");
    }
}

// Mark on the way
async function markOnTheWay(reportId){
    const res = await fetch('mark_on_the_way.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `report_id=${encodeURIComponent(reportId)}`
    });
    const data = await res.json();
    if(data.success){
        alert("You are assigned to this report!");
        fetchNotifications();
    } else {
        alert(data.message || "Failed to assign");
    }
}

// Feedback
function openFeedback(reportId){
    window.location.href = `feedback.php?report_id=${encodeURIComponent(reportId)}`;
}

// Sidebar toggle
document.getElementById('sidebar-toggle').addEventListener('click', ()=>{
    document.getElementById('sidebar').classList.toggle('hidden');
});

// Google map init
let maps = {};
function initMap(index, lat, lng){
    const map = new google.maps.Map(document.getElementById(`map-${index}`), {
        center: {lat: lat, lng: lng},
        zoom: 12
    });
    new google.maps.Marker({position: {lat: lat, lng: lng}, map: map});
    maps[index] = map;
}

// Toggle map size
function toggleMapSize(index){
    const mapDiv = document.getElementById(`map-${index}`);
    if(mapDiv.classList.contains('h-48')){
        mapDiv.classList.remove('h-48');
        mapDiv.classList.add('h-96');
    } else {
        mapDiv.classList.remove('h-96');
        mapDiv.classList.add('h-48');
    }
    if(maps[index]){
        google.maps.event.trigger(maps[index], "resize");
    }
}

// Load Google Maps + start
function loadGoogleMaps(){
    const script = document.createElement('script');
    script.src = "https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY";
    script.async = true;
    script.defer = true;
    script.onload = getVolunteerLocation;
    document.head.appendChild(script);
}

loadGoogleMaps();
</script>

</body>
</html>
