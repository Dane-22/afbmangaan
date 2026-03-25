/**
 * Attendance AJAX Handler
 * AFB Mangaan Attendance System
 */

(function() {
    'use strict';

    // Live search for attendees
    function initLiveSearch() {
        const searchInput = document.getElementById('attendeeSearch');
        const searchResults = document.getElementById('searchResults');
        
        if (!searchInput) return;

        let debounceTimer;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(debounceTimer);
            
            if (query.length < 2) {
                if (searchResults) searchResults.innerHTML = '';
                return;
            }
            
            debounceTimer = setTimeout(() => {
                searchAttendees(query);
            }, 300);
        });

        // Hide results on outside click
        document.addEventListener('click', function(e) {
            if (searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.innerHTML = '';
            }
        });
    }

    // Search attendees via AJAX
    function searchAttendees(query) {
        const searchResults = document.getElementById('searchResults');
        
        fetch(`/afb_mangaan_php/api/search_attendees.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.attendees) {
                    displaySearchResults(data.attendees);
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }

    // Display search results
    function displaySearchResults(attendees) {
        const searchResults = document.getElementById('searchResults');
        if (!searchResults) return;
        
        if (attendees.length === 0) {
            searchResults.innerHTML = '<div class="search-result-item">No members found</div>';
            return;
        }
        
        searchResults.innerHTML = attendees.map(attendee => `
            <div class="search-result-item" data-id="${attendee.id}" data-name="${attendee.fullname}">
                <div class="result-name">${attendee.fullname}</div>
                <div class="result-meta">${attendee.category} • ID: ${attendee.member_id || 'N/A'}</div>
            </div>
        `).join('');
        
        // Bind click handlers
        searchResults.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', function() {
                const attendeeId = this.dataset.id;
                const attendeeName = this.dataset.name;
                selectAttendee(attendeeId, attendeeName);
            });
        });
    }

    // Select attendee for attendance
    function selectAttendee(id, name) {
        const hiddenInput = document.getElementById('selectedAttendeeId');
        const displayInput = document.getElementById('attendeeSearch');
        const searchResults = document.getElementById('searchResults');
        
        if (hiddenInput) hiddenInput.value = id;
        if (displayInput) displayInput.value = name;
        if (searchResults) searchResults.innerHTML = '';
        
        // Show selected state
        showToast(`Selected: ${name}`, 'info');
    }

    // Record attendance
    function recordAttendance(eventId, attendeeId, status = 'Present', method = 'Manual') {
        const formData = new FormData();
        formData.append('event_id', eventId);
        formData.append('attendee_id', attendeeId);
        formData.append('status', status);
        formData.append('method', method);
        
        return fetch('/afb_mangaan_php/api/record_attendance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Attendance recorded successfully!', 'success');
                
                // Trigger confetti effect for successful attendance
                triggerConfetti();
                
                // Refresh attendance list if on attendance page
                refreshAttendanceList(eventId);
            } else {
                showToast(data.message || 'Failed to record attendance', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('Attendance error:', error);
            showToast('An error occurred while recording attendance', 'error');
            throw error;
        });
    }

    // Refresh attendance list
    function refreshAttendanceList(eventId) {
        const listContainer = document.getElementById('attendanceList');
        if (!listContainer) return;
        
        fetch(`/afb_mangaan_php/api/get_attendance.php?event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateAttendanceTable(data.attendance);
                }
            })
            .catch(error => console.error('Refresh error:', error));
    }

    // Update attendance table
    function updateAttendanceTable(attendance) {
        const tbody = document.querySelector('#attendanceTable tbody');
        if (!tbody) return;
        
        if (attendance.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No attendance records yet</td></tr>';
            return;
        }
        
        tbody.innerHTML = attendance.map((record, index) => `
            <tr class="animate__animated animate__fadeIn" style="animation-delay: ${index * 0.05}s">
                <td>${record.fullname}</td>
                <td><span class="badge badge-${record.status === 'Present' ? 'success' : 'danger'}">${record.status}</span></td>
                <td>${record.method}</td>
                <td>${record.log_time}</td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteAttendance(${record.id})">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // Delete attendance record
    function deleteAttendance(attendanceId) {
        if (!confirm('Are you sure you want to delete this attendance record?')) return;
        
        fetch(`/afb_mangaan_php/api/delete_attendance.php?id=${attendanceId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Attendance record deleted', 'success');
                    // Refresh the list
                    const eventId = document.getElementById('eventId')?.value;
                    if (eventId) refreshAttendanceList(eventId);
                } else {
                    showToast(data.message || 'Failed to delete', 'error');
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                showToast('An error occurred', 'error');
            });
    }

    // Confetti effect for successful attendance
    function triggerConfetti() {
        const colors = ['#6366f1', '#22c55e', '#f59e0b', '#3b82f6', '#ef4444'];
        
        for (let i = 0; i < 30; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.top = '50%';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDuration = (Math.random() * 0.5 + 0.5) + 's';
            confetti.style.animationDelay = Math.random() * 0.2 + 's';
            
            document.body.appendChild(confetti);
            
            setTimeout(() => confetti.remove(), 1500);
        }
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initLiveSearch();
    });

    // Expose API
    window.AttendanceAjax = {
        search: searchAttendees,
        record: recordAttendance,
        refresh: refreshAttendanceList,
        delete: deleteAttendance
    };
})();
