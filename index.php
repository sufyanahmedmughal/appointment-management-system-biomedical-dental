<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = getDB();

// Fetch active services
$stmt = $db->query("SELECT * FROM services WHERE is_active = 1 ORDER BY display_order ASC");
$services = $stmt->fetchAll();

// Fetch active doctors
$stmt = $db->query("SELECT * FROM doctors WHERE is_active = 1 ORDER BY id ASC");
$doctors = $stmt->fetchAll();

// Fetch approved reviews (both manual and Google)
$stmt = $db->query("SELECT * FROM reviews WHERE approved = 1 ORDER BY created_at DESC LIMIT 20");
$reviews = $stmt->fetchAll();

$clinicName = getSetting('clinic_name', 'Bio Medical Dental Care');
$clinicEmail = getSetting('clinic_email', 'info@biomedical.com');
$clinicPhone = getSetting('clinic_phone', '+92-300-1234567');
$clinicAddress = getSetting('clinic_address', 'Lahore, Pakistan');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $clinicName; ?> - Professional Dental Care</title>
    <meta name="description" content="Professional dental care services in Lahore. Expert dentists, modern equipment, affordable prices.">
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="font-sans">
    <!-- Navigation -->
    <nav id="navbar" class="fixed w-full top-0 z-50 bg-white shadow-md transition-all duration-300">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <i class="fas fa-tooth text-blue-600 text-3xl"></i>
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $clinicName; ?></h1>
            </div>
            
            <ul class="hidden md:flex space-x-8 text-gray-700">
                <li><a href="#home" class="hover:text-blue-600 transition">Home</a></li>
                <li><a href="#services" class="hover:text-blue-600 transition">Services</a></li>
                <li><a href="#doctors" class="hover:text-blue-600 transition">Doctors</a></li>
                <li><a href="#reviews" class="hover:text-blue-600 transition">Reviews</a></li>
                <li><a href="#appointment" class="hover:text-blue-600 transition">Book Now</a></li>
            </ul>
            
            <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
        
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <ul class="px-4 py-2 space-y-2">
                <li><a href="#home" class="block py-2 hover:text-blue-600">Home</a></li>
                <li><a href="#services" class="block py-2 hover:text-blue-600">Services</a></li>
                <li><a href="#doctors" class="block py-2 hover:text-blue-600">Doctors</a></li>
                <li><a href="#reviews" class="block py-2 hover:text-blue-600">Reviews</a></li>
                <li><a href="#appointment" class="block py-2 hover:text-blue-600">Book Now</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="relative h-screen flex items-center justify-center bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="absolute inset-0 bg-black opacity-40"></div>
        <div class="container mx-auto px-4 text-center relative z-10 fade-in">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 slide-up">Welcome to <?php echo $clinicName; ?></h1>
            <p class="text-xl md:text-2xl mb-8 slide-up delay-1">Your Smile, Our Priority - Professional Dental Care</p>
            <div class="space-x-4 slide-up delay-2">
                <a href="#appointment" class="bg-white text-blue-600 px-8 py-4 rounded-full font-semibold hover:bg-gray-100 transition transform hover:scale-105 inline-block">
                    Book Appointment
                </a>
                <a href="#services" class="border-2 border-white px-8 py-4 rounded-full font-semibold hover:bg-white hover:text-blue-600 transition inline-block">
                    Our Services
                </a>
            </div>
        </div>
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
            <a href="#services"><i class="fas fa-chevron-down text-4xl"></i></a>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Our Services</h2>
                <div class="w-24 h-1 bg-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600 text-lg">Comprehensive dental care for all your needs</p>
            </div>
            
            <div id="services-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php 
                $visibleCount = 6;
                foreach ($services as $index => $service): 
                    $isHidden = $index >= $visibleCount;
                ?>
                <div class="service-card bg-white rounded-lg shadow-lg p-6 hover:shadow-2xl transition transform hover:-translate-y-2 <?php echo $isHidden ? 'hidden' : ''; ?>" data-service-card>
                    <div class="text-blue-600 mb-4">
                        <i class="fas fa-tooth text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="flex justify-between items-center">
                        <div>
                            <?php if ($service['discount'] > 0): ?>
                                <span class="text-gray-400 line-through"><?php echo formatPrice($service['price']); ?></span>
                                <span class="text-2xl font-bold text-blue-600 ml-2">
                                    <?php echo formatPrice(getDiscountedPrice($service['price'], $service['discount'])); ?>
                                </span>
                                <span class="text-sm text-green-600 ml-2"><?php echo $service['discount']; ?>% OFF</span>
                            <?php else: ?>
                                <span class="text-2xl font-bold text-blue-600">
                                    <?php echo formatPrice($service['price']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="text-gray-500"><i class="far fa-clock"></i> <?php echo $service['duration']; ?> min</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($services) > $visibleCount): ?>
            <div class="text-center mt-12">
                <button id="show-more-services" class="bg-blue-600 text-white px-8 py-3 rounded-full hover:bg-blue-700 transition">
                    <span id="btn-text">Show More Services</span>
                    <i id="btn-icon" class="fas fa-chevron-down ml-2"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Doctors Section -->
    <section id="doctors" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Meet Our Experts</h2>
                <div class="w-24 h-1 bg-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600 text-lg">Experienced and caring dental professionals</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card bg-gray-50 rounded-lg overflow-hidden shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2">
                    <div class="h-64 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                        <?php if ($doctor['picture']): ?>
                            <img src="<?php echo UPLOAD_URL . htmlspecialchars($doctor['picture']); ?>" alt="<?php echo htmlspecialchars($doctor['name']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user-md text-white text-8xl"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                        <p class="text-blue-600 font-semibold mb-2"><i class="fas fa-stethoscope mr-2"></i><?php echo htmlspecialchars($doctor['passion']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section id="reviews" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">What Our Patients Say</h2>
                <div class="w-24 h-1 bg-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600 text-lg">Real feedback from real patients</p>
            </div>
            
            <div class="relative max-w-4xl mx-auto">
                <div id="reviews-carousel" class="overflow-hidden">
                    <div id="reviews-track" class="flex transition-transform duration-500">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-slide flex-shrink-0 w-full px-4">
                            <div class="bg-white rounded-lg shadow-lg p-8">
                                <div class="flex items-center mb-4">
                                    <?php if ($review['source'] === 'google' && $review['google_author_photo']): ?>
                                    <img src="<?php echo htmlspecialchars($review['google_author_photo']); ?>" alt="<?php echo htmlspecialchars($review['name']); ?>" class="w-16 h-16 rounded-full mr-4">
                                    <?php else: ?>
                                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                                        <?php echo strtoupper(substr($review['name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($review['name']); ?></h4>
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i < $review['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if ($review['source'] === 'google'): ?>
                                        <span class="text-xs text-blue-600"><i class="fab fa-google mr-1"></i>Google Review</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-gray-700 text-lg italic">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                                <div class="mt-4 text-sm text-gray-500">
                                    <?php echo formatDate($review['created_at']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button id="prev-review" class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-3 shadow-lg hover:bg-gray-100">
                    <i class="fas fa-chevron-left text-blue-600"></i>
                </button>
                <button id="next-review" class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-3 shadow-lg hover:bg-gray-100">
                    <i class="fas fa-chevron-right text-blue-600"></i>
                </button>
            </div>
            
            <div class="max-w-2xl mx-auto mt-16 bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Share Your Experience</h3>
                <form id="review-form">
                    <div class="mb-4">
                        <input type="text" name="name" placeholder="Your Name *" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                    </div>
                    <div class="mb-4">
                        <input type="email" name="email" placeholder="Your Email (Optional)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                    </div>
                    <div class="mb-4">
                        <textarea name="review_text" rows="4" placeholder="Your Review *" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Submit Review
                    </button>
                </form>
                <div id="review-message" class="mt-4 text-center hidden"></div>
            </div>
        </div>
    </section>

    <!-- Appointment Booking Section -->
    <section id="appointment" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Book Your Appointment</h2>
                <div class="w-24 h-1 bg-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600 text-lg">Schedule your visit today</p>
            </div>
            
            <div class="max-w-4xl mx-auto bg-gray-50 rounded-lg shadow-xl p-8">
                <form id="appointment-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Full Name *</label>
                            <input type="text" name="patient_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Email *</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Phone Number * (e.g., 923001234567)</label>
                            <input type="tel" name="phone" required placeholder="923001234567" pattern="92[0-9]{10}" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                            <p class="text-sm text-gray-500 mt-1">Format: 92 followed by 10 digits (no spaces or dashes)</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Select Service *</label>
                            <select name="service_id" id="service_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                                <option value="">Choose a service</option>
                                <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" data-duration="<?php echo $service['duration']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> - <?php echo formatPrice($service['price']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Select Doctor *</label>
                        <select name="doctor_id" id="doctor_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                            <option value="">Choose a doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['name']); ?> - <?php echo htmlspecialchars($doctor['passion']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Doctor Schedule Display -->
                    <div id="doctor-schedule" class="mb-6 hidden">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Doctor's Available Schedule</h3>
                        <div id="schedule-loading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin text-blue-600 text-2xl"></i>
                            <p class="text-gray-600 mt-2">Loading schedule...</p>
                        </div>
                        <div id="schedule-calendar" class="hidden"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Appointment Date *</label>
                            <input type="date" name="appointment_date" id="appointment_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Select Time Slot *</label>
                            <select name="appointment_time" id="appointment_time" required disabled class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 bg-gray-100">
                                <option value="">First select doctor and date</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-lg font-semibold text-lg hover:bg-blue-700 transition transform hover:scale-105">
                        <i class="fas fa-calendar-check mr-2"></i> Book Appointment
                    </button>
                </form>
                <div id="appointment-message" class="mt-6 text-center hidden"></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-2xl font-bold mb-4"><?php echo $clinicName; ?></h3>
                    <p class="text-gray-400">Professional dental care services with experienced doctors and modern equipment.</p>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#home" class="hover:text-white transition">Home</a></li>
                        <li><a href="#services" class="hover:text-white transition">Services</a></li>
                        <li><a href="#doctors" class="hover:text-white transition">Doctors</a></li>
                        <li><a href="#appointment" class="hover:text-white transition">Book Appointment</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Contact Info</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><i class="fas fa-map-marker-alt mr-2 text-blue-500"></i><?php echo $clinicAddress; ?></li>
                        <li><i class="fas fa-phone mr-2 text-blue-500"></i><?php echo $clinicPhone; ?></li>
                        <li><i class="fas fa-envelope mr-2 text-blue-500"></i><?php echo $clinicEmail; ?></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">Opening Hours</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Monday - Thursday: 9:00 AM - 6:00 PM</li>
                        <li>Friday: 9:00 AM - 4:00 PM</li>
                        <li>Saturday: 10:00 AM - 2:00 PM</li>
                        <li>Sunday: Closed</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $clinicName; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/appointment.js"></script>
</body>
</html>