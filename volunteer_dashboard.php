<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header("Location: login.php");
    exit();
}
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
        <h2 class="text-3xl font-bold text-[#2c3e50]">Welcome, <?=htmlspecialchars($_SESSION['user_name'])?> (Volunteer)</h2>
        <a href="logout.php" class="text-[#e74c3c] font-semibold hover:underline transition">Logout</a>
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
const currentVolunteerId = <?=json_encode($_SESSION['user_id'])?>;
let volunteerLocation = null;

// Get volunteer geolocation
function getVolunteerLocation(){
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
            pos => { 
                volunteerLocation = {lat: pos.coords.latitude, lng: pos.coords.longitude}; 
                fetchNotifications(); 
            },
            err => { alert("Location denied. Showing all reports."); fetchNotifications(); }
        );
    } else { alert("Geolocation not supported."); fetchNotifications(); }
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

    // Render maps for each report
    reports.forEach((report,index)=>{
        const map = new google.maps.Map(document.getElementById(`map-${index}`),{
            center:{lat:parseFloat(report.latitude), lng:parseFloat(report.longitude)},
            zoom:15,
            disableDefaultUI:true,
            styles:[
                {featureType:"all",elementType:"geometry",stylers:[{color:"#efe7dd"}]},
                {featureType:"poi",elementType:"labels.text.fill",stylers:[{color:"#6b705c"}]}
            ]
        });
        new google.maps.Marker({
            position:{lat:parseFloat(report.latitude), lng:parseFloat(report.longitude)},
            map: map,
            icon: "https://maps.google.com/mapfiles/ms/icons/orange-dot.png"
        });
    });
}

// Toggle report map size
function toggleMapSize(index){
    const mapDiv = document.getElementById(`map-${index}`);
    mapDiv.classList.toggle("h-48");
    mapDiv.classList.toggle("h-96");
}

// Mark as saved
function markSaved(index, reportId){
    fetch('update_report_status.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({report_id:reportId,status:'saved'})})
    .then(r=>r.json()).then(res=>{if(res.success) document.getElementById(`report-${index}`).remove(); else alert("Failed to update report.");});
}

// Mark on the way
function markOnTheWay(reportId){
    fetch('update_report_status.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({report_id:reportId,status:'on_the_way'})})
    .then(r=>r.json()).then(res=>{if(res.success){alert("Marked On The Way"); getVolunteerLocation();}else alert("Failed to update report.");});
}

// Feedback
function openFeedback(reportId){
    const feedback = prompt("Enter your feedback:");
    if(feedback && feedback.trim()!==""){
        fetch('submit_feedback.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({report_id:reportId, feedback})})
        .then(r=>r.json()).then(res=>{if(res.success) alert("Feedback submitted!"); else alert("Failed: "+res.message);}).catch(err=>alert("Error: "+err));
    }
}

// Distance calculation
function getDistance(lat1,lon1,lat2,lon2){
    const R = 6371;
    const dLat = deg2rad(lat2-lat1);
    const dLon = deg2rad(lon2-lon1);
    const a = Math.sin(dLat/2)**2 + Math.cos(deg2rad(lat1))*Math.cos(deg2rad(lat2))*Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}
function deg2rad(deg){return deg*(Math.PI/180);}

// Sidebar toggle
document.getElementById('sidebar-toggle').addEventListener('click', () => {
  const sidebar = document.getElementById('sidebar');
  if (sidebar.classList.contains('hidden')) {
    sidebar.classList.remove('hidden');
    loadPendingMap();
  } else {
    sidebar.classList.add('hidden');
  }
});


// Load pending map function
async function loadPendingMap() {
  const res = await fetch('fetch_reports.php');
  const reports = await res.json();
  const pendingReports = reports.filter(r => r.status === 'pending');

  const map = new google.maps.Map(document.getElementById('pendingMap'), {
    zoom: 8,
    center: { lat: 23.6850, lng: 90.3563 },
    styles: [
      {
        "featureType": "all",
        "elementType": "geometry",
        "stylers": [{ "color": "#efe7dd" }]
      },
      {
        "featureType": "poi",
        "elementType": "labels.text.fill",
        "stylers": [{ "color": "#6b705c" }]
      }
    ]
  });

  pendingReports.forEach(report => {
    const marker = new google.maps.Marker({
      position: { lat: parseFloat(report.latitude), lng: parseFloat(report.longitude) },
      map: map,
      title: report.description,
      icon: {
        url: "https://maps.google.com/mapfiles/ms/icons/green-dot.png"
      }
    });

    const content = `
      <div style="max-width: 250px; font-family: Arial, sans-serif; color:#2c3e50;">
        <p class="font-semibold">${report.description}</p>
        <p><em>Lat:</em> ${report.latitude}<br/><em>Lng:</em> ${report.longitude}</p>
        <img src="${report.photo_path}" alt="Animal Photo" width="200" style="border-radius: 5px; border: 1px solid #a1866f;" onerror="this.alt='No image'; this.style.border='2px solid #e74c3c';" />
        <br/><br/>
        <button onclick="markOnTheWayFromMap(${report.id})" 
          style="background-color: #2a9d8f; color: white; padding: 6px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
          Accept Report
        </button>
      </div>
    `;

    const infoWindow = new google.maps.InfoWindow({ content });

    marker.addListener('click', () => {
      infoWindow.open(map, marker);
    });
  });
}
getVolunteerLocation();
setInterval(getVolunteerLocation,15000);

</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCoviL0XngK8i6h2YLD7xOS5DFOG84m2dw"></script>
</body>
</html>
