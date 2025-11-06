<script>
let geocoder;

function initMap() {
    geocoder = new google.maps.Geocoder();

    if (navigator.geolocation) {
        const options = {
            enableHighAccuracy: true,
            timeout: 10000,  // 10 seconds max wait
            maximumAge: 0    // Don't use cached position
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const latlng = new google.maps.LatLng(lat, lng);

                // Reverse geocoding for location name
                geocoder.geocode({ location: latlng }, (results, status) => {
                    let locationName = "Unknown Location";
                    if (status === "OK" && results[0]) {
                        locationName = results[0].formatted_address;
                    }
                    document.getElementById('locationName').textContent = locationName;
                });

                document.getElementById('locationCoords').textContent = 
                    `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
            },
            (error) => {
                let errorMsg = 'Location access denied.';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMsg = 'Permission denied. Enable in browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMsg = 'Location unavailable (check GPS/WiFi).';
                        break;
                    case error.TIMEOUT:
                        errorMsg = 'Location timed out. Try again?';
                        break;
                }
                document.getElementById('locationName').textContent = errorMsg;
                document.getElementById('locationCoords').textContent = '';
            },
            options
        );
    } else {
        document.getElementById('locationName').textContent = 'Geolocation not supported.';
        document.getElementById('locationCoords').textContent = '';
    }
}

// Rest of your existing script (modals, form handling, etc.) goes here unchanged...
const regModal = document.getElementById('regModal');
const attendanceModal = document.getElementById('attendanceModal');
const successModal = document.getElementById('successModal');
const closeBtn = document.querySelector('.close');
const form = document.getElementById('attendanceForm');
let savedRoll = localStorage.getItem('student_roll');

if (!savedRoll) {
    regModal.style.display = 'flex';
} else {
    document.getElementById('modalRoll').value = savedRoll;
}

document.getElementById('saveRollBtn').onclick = () => {
    const roll = document.getElementById('regRoll').value.trim();
    const msg = document.getElementById('regMessage');
    msg.style.display = 'none';

    if (!roll || roll < 1 || roll > 100) {
        msg.style.color = '#f87171';
        msg.textContent = 'Enter valid roll (1-100)';
        msg.style.display = 'block';
        return;
    }

    localStorage.setItem('student_roll', roll);
    document.getElementById('modalRoll').value = roll;
    regModal.style.display = 'none';
    msg.style.color = '#34d399';
    msg.textContent = 'Roll saved!';
    msg.style.display = 'block';
    setTimeout(() => msg.style.display = 'none', 2000);
};

document.querySelectorAll('.digit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const digit = btn.getAttribute('data-digit');
        document.getElementById('modalDigit').value = digit;
        attendanceModal.style.display = 'flex';
        document.getElementById('modalSubject').focus();
    });
});

closeBtn.onclick = () => {
    attendanceModal.style.display = 'none';
    document.getElementById('modalMessage').style.display = 'none';
};

window.onclick = (e) => {
    if (e.target === attendanceModal) {
        attendanceModal.style.display = 'none';
        document.getElementById('modalMessage').style.display = 'none';
    }
    if (e.target === successModal) {
        successModal.style.display = 'none';
    }
};

form.onsubmit = async (e) => {
    e.preventDefault();
    const msgDiv = document.getElementById('modalMessage');
    msgDiv.style.display = 'none';

    const formData = new FormData(form);

    try {
        const resp = await fetch('', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            attendanceModal.style.display = 'none';
            document.getElementById('successMsgText').textContent = data.message || 'Attendance marked!';
            successModal.style.display = 'flex';
            setTimeout(() => successModal.style.display = 'none', 3000);
        } else {
            msgDiv.style.color = '#f87171';
            msgDiv.textContent = data.message || 'Failed.';
            msgDiv.style.display = 'block';
        }
    } catch (err) {
        msgDiv.style.color = '#f87171';
        msgDiv.textContent = 'Network error.';
        msgDiv.style.display = 'block';
    }
};

document.getElementById('viewLink').addEventListener('click', (e) => {
    if (savedRoll) {
        e.preventDefault();
        window.location.href = 'view_attendance.php?roll=' + savedRoll;
    }
});
</script>