// Appointment Booking with Calendar Integration

document.addEventListener('DOMContentLoaded', function() {
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const scheduleDiv = document.getElementById('doctor-schedule');
    const scheduleLoading = document.getElementById('schedule-loading');
    const scheduleCalendar = document.getElementById('schedule-calendar');
    
    let selectedDoctorId = null;
    let doctorSchedule = null;
    let bookedSlots = [];
    
    // When doctor is selected, load schedule
    if (doctorSelect) {
        doctorSelect.addEventListener('change', async function() {
            selectedDoctorId = this.value;
            
            console.log('Doctor selected:', selectedDoctorId);
            
            if (!selectedDoctorId) {
                scheduleDiv.classList.add('hidden');
                timeSelect.disabled = true;
                timeSelect.innerHTML = '<option value="">First select doctor and date</option>';
                dateInput.disabled = true;
                return;
            }
            
            // Show loading
            scheduleDiv.classList.remove('hidden');
            scheduleLoading.classList.remove('hidden');
            scheduleCalendar.classList.add('hidden');
            
            try {
                // Fetch doctor schedule with longer timeout
                console.log('Fetching schedule from API...');
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
                
                const response = await fetch(`api/get_doctor_schedule.php?doctor_id=${selectedDoctorId}`, {
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('API Response:', data);
                
                if (data.success) {
                    doctorSchedule = data.schedule;
                    bookedSlots = data.booked_slots || [];
                    
                    console.log('Schedule loaded:', doctorSchedule);
                    console.log('Booked slots:', bookedSlots);
                    
                    // Check if schedule exists
                    if (!doctorSchedule || doctorSchedule.length === 0) {
                        scheduleLoading.classList.add('hidden');
                        scheduleCalendar.innerHTML = `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-3xl mb-3"></i>
                                <h4 class="font-bold text-red-800 mb-2">Doctor Schedule Not Available</h4>
                                <p class="text-red-600">This doctor has not set up their schedule yet.</p>
                                <p class="text-red-600 text-sm mt-2">Please select another doctor or contact admin.</p>
                            </div>
                        `;
                        scheduleCalendar.classList.remove('hidden');
                        dateInput.disabled = true;
                        return;
                    }
                    
                    // Display schedule - give it time to render
                    setTimeout(() => {
                        displayDoctorSchedule(doctorSchedule);
                        scheduleLoading.classList.add('hidden');
                        scheduleCalendar.classList.remove('hidden');
                        
                        // Enable date selection
                        dateInput.disabled = false;
                        
                        console.log('Schedule displayed successfully');
                    }, 100);
                    
                } else {
                    throw new Error(data.message || 'Failed to load schedule');
                }
            } catch (error) {
                console.error('Error loading schedule:', error);
                scheduleLoading.classList.add('hidden');
                scheduleCalendar.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-3xl mb-3"></i>
                        <h4 class="font-bold text-red-800 mb-2">Error Loading Schedule</h4>
                        <p class="text-red-600">${error.message}</p>
                        <p class="text-red-600 text-sm mt-2">Please try again or select another doctor.</p>
                    </div>
                `;
                scheduleCalendar.classList.remove('hidden');
                dateInput.disabled = true;
            }
        });
    }
    
    // When date is selected, load available time slots
    if (dateInput) {
        dateInput.addEventListener('change', async function() {
            const selectedDate = this.value;
            
            console.log('Date selected:', selectedDate);
            
            if (!selectedDate || !selectedDoctorId) {
                return;
            }
            
            // Get day of week
            const date = new Date(selectedDate + 'T00:00:00');
            const dayOfWeek = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
            
            console.log('Day of week:', dayOfWeek);
            
            // Check if doctor works on this day
            const daySchedule = doctorSchedule.find(s => s.day_of_week === dayOfWeek && (s.is_available === '1' || s.is_available === 1));
            
            console.log('Day schedule:', daySchedule);
            
            if (!daySchedule) {
                timeSelect.innerHTML = '<option value="">Doctor not available on this day</option>';
                timeSelect.disabled = true;
                alert('Doctor is not available on ' + dayOfWeek.charAt(0).toUpperCase() + dayOfWeek.slice(1) + '. Please select another date.');
                return;
            }
            
            // Generate time slots
            const slots = generateTimeSlots(daySchedule.start_time, daySchedule.end_time, selectedDate);
            
            console.log('Generated slots:', slots);
            
            // Populate time select
            timeSelect.innerHTML = '<option value="">Select a time slot</option>';
            
            if (slots.length === 0) {
                timeSelect.innerHTML = '<option value="">No available slots for this date</option>';
                timeSelect.disabled = true;
            } else {
                slots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.time;
                    
                    if (slot.booked) {
                        option.textContent = slot.label + ' ❌ Already Booked';
                        option.disabled = true;
                        option.style.color = '#dc2626';
                    } else {
                        option.textContent = slot.label + ' ✅ Available';
                        option.style.color = '#16a34a';
                    }
                    
                    timeSelect.appendChild(option);
                });
                timeSelect.disabled = false;
            }
        });
    }
    
    // Display doctor schedule
    function displayDoctorSchedule(schedule) {
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayNames = {
            'monday': 'Monday',
            'tuesday': 'Tuesday',
            'wednesday': 'Wednesday',
            'thursday': 'Thursday',
            'friday': 'Friday',
            'saturday': 'Saturday',
            'sunday': 'Sunday'
        };
        
        console.log('Displaying schedule...');
        
        let html = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">';
        html += '<h4 class="font-bold text-blue-800 mb-3"><i class="fas fa-calendar-alt mr-2"></i>Doctor\'s Weekly Availability</h4>';
        html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
        
        days.forEach(day => {
            const daySlots = schedule.filter(s => s.day_of_week === day && (s.is_available === '1' || s.is_available === 1));
            
            if (daySlots.length > 0) {
                html += `
                    <div class="bg-white border border-green-200 rounded-lg p-3">
                        <h5 class="font-bold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            ${dayNames[day]}
                        </h5>
                        <div class="space-y-1">
                `;
                
                daySlots.forEach(slot => {
                    html += `
                        <div class="text-sm text-gray-700 flex items-center">
                            <i class="far fa-clock mr-2 text-blue-600"></i>
                            <span class="font-medium">${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</span>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div class="bg-gray-100 border border-gray-200 rounded-lg p-3 opacity-60">
                        <h5 class="font-bold text-gray-500 mb-2 flex items-center">
                            <i class="fas fa-times-circle text-gray-400 mr-2"></i>
                            ${dayNames[day]}
                        </h5>
                        <div class="text-sm text-gray-500">Not available</div>
                    </div>
                `;
            }
        });
        
        html += '</div></div>';
        
        const availableDays = schedule.filter(s => s.is_available === '1' || s.is_available === 1).length;
        
        if (availableDays === 0) {
            html = '<div class="text-center text-red-600 py-4"><i class="fas fa-exclamation-triangle mr-2"></i>Doctor has not set up their schedule yet.</div>';
        }
        
        scheduleCalendar.innerHTML = html;
        
        console.log('Schedule HTML rendered');
    }
    
    // Generate time slots
    function generateTimeSlots(startTime, endTime, selectedDate) {
        const slots = [];
        const slotDuration = 30; // 30 minutes per slot
        
        // Parse start and end times
        const [startHour, startMin] = startTime.split(':').map(Number);
        const [endHour, endMin] = endTime.split(':').map(Number);
        
        let currentHour = startHour;
        let currentMin = startMin;
        
        while (currentHour < endHour || (currentHour === endHour && currentMin < endMin)) {
            const timeStr = `${String(currentHour).padStart(2, '0')}:${String(currentMin).padStart(2, '0')}:00`;
            const displayTime = formatTime(timeStr);
            
            // Check if slot is already booked
            const isBooked = bookedSlots.some(booked => {
                return booked.date === selectedDate && booked.time === timeStr;
            });
            
            slots.push({
                time: timeStr,
                label: displayTime,
                booked: isBooked
            });
            
            // Increment time
            currentMin += slotDuration;
            if (currentMin >= 60) {
                currentHour += 1;
                currentMin = 0;
            }
        }
        
        return slots;
    }
    
    // Format time to 12-hour format
    function formatTime(time24) {
        const [hour, minute] = time24.split(':').map(Number);
        const period = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${String(minute).padStart(2, '0')} ${period}`;
    }
    
    // Handle form submission
    const appointmentForm = document.getElementById('appointment-form');
    const appointmentMessage = document.getElementById('appointment-message');
    
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(appointmentForm);
            
            // Additional validation
            const phone = formData.get('phone');
            const phoneRegex = /^92[0-9]{10}$/;
            
            if (!phoneRegex.test(phone)) {
                appointmentMessage.classList.remove('hidden');
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Phone format must be: 92 followed by 10 digits (e.g., 923001234567)';
                return;
            }
            
            // Check if time slot is selected and available
            const selectedTime = formData.get('appointment_time');
            if (!selectedTime || selectedTime === '') {
                appointmentMessage.classList.remove('hidden');
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Please select an available time slot';
                return;
            }
            
            // Show loading
            appointmentMessage.classList.remove('hidden');
            appointmentMessage.className = 'mt-6 text-center';
            appointmentMessage.innerHTML = '<div class="spinner"></div><p class="mt-2">Booking your appointment...</p>';
            
            try {
                const response = await fetch('api/book_appointment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    appointmentMessage.className = 'mt-6 text-center message-success';
                    appointmentMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    appointmentForm.reset();
                    
                    // Reset form state
                    scheduleDiv.classList.add('hidden');
                    timeSelect.disabled = true;
                    timeSelect.innerHTML = '<option value="">First select doctor and date</option>';
                    dateInput.disabled = true;
                    
                    // Scroll to message
                    appointmentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Auto-hide message after 10 seconds
                    setTimeout(() => {
                        appointmentMessage.classList.add('hidden');
                    }, 10000);
                } else {
                    appointmentMessage.className = 'mt-6 text-center message-error';
                    appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                }
            } catch (error) {
                console.error('Booking error:', error);
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred. Please try again.';
            }
        });
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
    
    // When date is selected, load available time slots
    dateInput.addEventListener('change', async function() {
        const selectedDate = this.value;
        
        if (!selectedDate || !selectedDoctorId) {
            return;
        }
        
        // Get day of week
        const date = new Date(selectedDate + 'T00:00:00');
        const dayOfWeek = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
        
        // Check if doctor works on this day
        const daySchedule = doctorSchedule.find(s => s.day_of_week === dayOfWeek && s.is_available === '1');
        
        if (!daySchedule) {
            timeSelect.innerHTML = '<option value="">Doctor not available on this day</option>';
            timeSelect.disabled = true;
            return;
        }
        
        // Generate time slots
        const slots = generateTimeSlots(daySchedule.start_time, daySchedule.end_time, selectedDate);
        
        // Populate time select
        timeSelect.innerHTML = '<option value="">Select a time slot</option>';
        
        if (slots.length === 0) {
            timeSelect.innerHTML = '<option value="">No available slots for this date</option>';
            timeSelect.disabled = true;
        } else {
            slots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.time;
                option.textContent = slot.label + (slot.booked ? ' (Already Booked)' : ' (Available)');
                option.disabled = slot.booked;
                option.className = slot.booked ? 'text-red-600' : 'text-green-600';
                timeSelect.appendChild(option);
            });
            timeSelect.disabled = false;
        }
    });
    
    // Display doctor schedule
    function displayDoctorSchedule(schedule) {
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const dayNames = {
            'monday': 'Monday',
            'tuesday': 'Tuesday',
            'wednesday': 'Wednesday',
            'thursday': 'Thursday',
            'friday': 'Friday',
            'saturday': 'Saturday',
            'sunday': 'Sunday'
        };
        
        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        
        days.forEach(day => {
            const daySlots = schedule.filter(s => s.day_of_week === day && s.is_available === '1');
            
            if (daySlots.length > 0) {
                html += `
                    <div class="border border-gray-300 rounded-lg p-4 bg-white">
                        <h4 class="font-bold text-gray-800 mb-2">${dayNames[day]}</h4>
                        <div class="space-y-1">
                `;
                
                daySlots.forEach(slot => {
                    html += `
                        <div class="text-sm text-gray-600">
                            <i class="far fa-clock mr-2 text-blue-600"></i>
                            ${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
        });
        
        html += '</div>';
        
        if (schedule.filter(s => s.is_available === '1').length === 0) {
            html = '<p class="text-center text-gray-500 py-4">No schedule available</p>';
        }
        
        scheduleCalendar.innerHTML = html;
    }
    
    // Generate time slots
    function generateTimeSlots(startTime, endTime, selectedDate) {
        const slots = [];
        const slotDuration = 30; // 30 minutes per slot
        
        // Parse start and end times
        const [startHour, startMin] = startTime.split(':').map(Number);
        const [endHour, endMin] = endTime.split(':').map(Number);
        
        let currentHour = startHour;
        let currentMin = startMin;
        
        while (currentHour < endHour || (currentHour === endHour && currentMin < endMin)) {
            const timeStr = `${String(currentHour).padStart(2, '0')}:${String(currentMin).padStart(2, '0')}:00`;
            const displayTime = formatTime(timeStr);
            
            // Check if slot is already booked
            const isBooked = bookedSlots.some(booked => {
                return booked.date === selectedDate && booked.time === timeStr;
            });
            
            slots.push({
                time: timeStr,
                label: displayTime,
                booked: isBooked
            });
            
            // Increment time
            currentMin += slotDuration;
            if (currentMin >= 60) {
                currentHour += 1;
                currentMin = 0;
            }
        }
        
        return slots;
    }
    
    // Format time to 12-hour format
    function formatTime(time24) {
        const [hour, minute] = time24.split(':').map(Number);
        const period = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${String(minute).padStart(2, '0')} ${period}`;
    }
    
    // Handle form submission
    const appointmentForm = document.getElementById('appointment-form');
    const appointmentMessage = document.getElementById('appointment-message');
    
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(appointmentForm);
            
            // Additional validation
            const phone = formData.get('phone');
            const phoneRegex = /^92[0-9]{10}$/;
            
            if (!phoneRegex.test(phone)) {
                appointmentMessage.classList.remove('hidden');
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Phone format must be: 92 followed by 10 digits (e.g., 923001234567)';
                return;
            }
            
            // Check if time slot is selected and available
            const selectedTime = formData.get('appointment_time');
            if (!selectedTime || selectedTime === '') {
                appointmentMessage.classList.remove('hidden');
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Please select an available time slot';
                return;
            }
            
            // Show loading
            appointmentMessage.classList.remove('hidden');
            appointmentMessage.className = 'mt-6 text-center';
            appointmentMessage.innerHTML = '<div class="spinner"></div><p class="mt-2">Booking your appointment...</p>';
            
            try {
                const response = await fetch('api/book_appointment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    appointmentMessage.className = 'mt-6 text-center message-success';
                    appointmentMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    appointmentForm.reset();
                    
                    // Reset form state
                    scheduleDiv.classList.add('hidden');
                    timeSelect.disabled = true;
                    timeSelect.innerHTML = '<option value="">First select doctor and date</option>';
                    
                    // Scroll to message
                    appointmentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Auto-hide message after 10 seconds
                    setTimeout(() => {
                        appointmentMessage.classList.add('hidden');
                    }, 10000);
                } else {
                    appointmentMessage.className = 'mt-6 text-center message-error';
                    appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                }
            } catch (error) {
                console.error('Booking error:', error);
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred. Please try again.';
            }
        });
    }

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}