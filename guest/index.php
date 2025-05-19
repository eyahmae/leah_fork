<?php
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    // Process edit reservation form submission
    $reservation_id = $_POST['reservation_id'] ?? null;
    $room_type_id = $_POST['roomType'] ?? null;
    $room_number = $_POST['roomNumber'] ?? null;
    $check_in = $_POST['checkInDate'] ?? null;
    $check_out = $_POST['checkOutDate'] ?? null;
    $guests = $_POST['guests'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($reservation_id && $room_type_id && $room_number && $check_in && $check_out && $guests && $status) {
        $database = new database();
        $conn = $database->getConnection();
        Model::setConnection($conn);
        Reservation::setConnection($conn);

        // Fetch the reservation to update
        $reservation = Reservation::find($reservation_id);
        if ($reservation) {
            // Update fields
            $reservation->room_type_id = $room_type_id;
            $reservation->room_number = $room_number;
            $reservation->check_in = $check_in;
            $reservation->check_out = $check_out;
            $reservation->guests = $guests;
            $reservation->status = $status;


            if ($reservation->save()) {
                $_SESSION['success_message'] = "Reservation updated successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to update reservation.";
            }
        } else {
            $_SESSION['error_message'] = "Reservation not found.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid form data.";
    }
    // Redirect to accommodation.php to show updated reservation summary
    header("Location: accommodation.php");
    exit;
}

$database = new database();
$conn = $database->getConnection();
Model::setConnection($conn);

Reservation::setConnection($conn);
RoomType::setConnection($conn);
Room::setConnection($conn);
$reservations = Reservation::where('user_id', '=', $_SESSION['user_id']);

// Instantiate Modals here
$modals = new Modals();

// Fetch available rooms
$allAvailableRooms = $guest->getAvailableRooms();

// After fetching $allAvailableRooms
foreach ($allAvailableRooms as &$room) {
    if (empty($room['room_type_name']) && !empty($room['type_id'])) {
        $stmt = $conn->prepare("SELECT name FROM room_types WHERE id = ?");
        $stmt->execute([$room['type_id']]);
        $typeRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $room['room_type_name'] = $typeRow ? $typeRow['name'] : 'Unknown';
    }
    $room['image_path'] = ImagePaths::getRoomTypeImage($room['room_type_name']);
}
unset($room); // break reference

$totalAvailableRooms = count($allAvailableRooms);
$initialDisplayCount = min(3, $totalAvailableRooms);
$displayedRooms = array_slice($allAvailableRooms, 0, $initialDisplayCount);

// Fetch booking history
$allBookingHistoryList = $guest->getBookingHistory();

// Fetch all booking history details for the modal
$allBookingHistoryDetails = $guest->getBookingHistoryDetails();

// Create an associative array for quick lookup of booking details
$bookingDetailsLookup = [];
foreach ($allBookingHistoryDetails as $detail) {
    $bookingDetailsLookup[$detail['reservation_id']] = $detail;
}

// Feedback submission logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($user_id && $rating > 0 && !empty($comment)) {
        // Find latest reservation for this user
        $stmt = $conn->prepare("SELECT id FROM reservations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reservation) {
            $reservation_id = $reservation['id'];
            $stmt = $conn->prepare("INSERT INTO reviews (reservation_id, rating, comment, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$reservation_id, $rating, $comment]);
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

?>

<div class="textcenter">
    <div class="overlay"></div>
    <h1 class="welcome">WELCOME</h1>
    <h2 class="subtext">LET'S BOOK NOW</h2>
</div>

<?php

$upcomingBooking = Dashboard::getUpcomingBooking();
$totalUpcomingBookings = Dashboard::getTotalUpcomingBookings();
$totalBookings = Dashboard::getTotalBookings();
$totalAvailableRooms = Dashboard::getTotalAvailableRooms();

if ($upcomingBooking) {
    $amenities = $upcomingBooking['amenities'] ? explode(',', $upcomingBooking['amenities']) : [];
}
?>

<div class="dashboard-container">
    <div class="dashboard-card">
        <h3 style="margin-left: 30px;">Upcoming Booking</h3>
        <div class="upcoming-booking-wrapper">
            <button class="navigation-button left-arrow">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                     class="bi bi-arrow-left-circle-fill" viewBox="0 0 16 16">
                    <path
                        d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0m3.5 7.5a.5.5 0 0 1 0 1h-5.793l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z"/>
                </svg>
            </button>
            <div class="upcoming-booking-visual">
                <div class="upcoming-booking-image-container">
                    <img src="../images/single_bedroom.jpeg" alt="Upcoming Booking Room">
                </div>
                <?php if ($upcomingBooking): ?>
                    <div class="upcoming-booking-details">
                        <h2><?= htmlspecialchars($upcomingBooking['type_name']) ?></h2>
                        <p>Room ID: <?= htmlspecialchars($upcomingBooking['room_number']) ?></p>
                        <p>Check-in Date: <?= htmlspecialchars($upcomingBooking['check_in']) ?></p>
                        <p>Check-out Date: <?= htmlspecialchars($upcomingBooking['check_out']) ?></p>
                        <p>
                            Amenities:
                            <?php if (!empty($amenities)): ?>
                                <?= htmlspecialchars(implode(', ', $amenities)) ?>
                            <?php else: ?>
                                No Amenities
                            <?php endif; ?>
                        </p>
                        <p>Number of Guests: <?= htmlspecialchars($upcomingBooking['guests']) ?></p>
                        <p>Status: <?= htmlspecialchars($upcomingBooking['status']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="upcoming-booking-details">
                        <p>No upcoming bookings.</p>
                    </div>
                <?php endif; ?>
            </div>
            <button class="navigation-button right-arrow">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                     class="bi bi-arrow-right-circle-fill" viewBox="0 0 16 16">
                    <path
                        d="M8 0a8 8 0 1 1 0 16A8 8 0 1 1 8 0M4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5z"/>
                </svg>
            </button>
            <div class="dashboard-summary">
                <div>
                    <p>Total of Upcoming Booking:</p>
                    <span class="number"><?= htmlspecialchars($totalUpcomingBookings) ?></span>
                </div>
                <div>
                    <p>Total Booking:</p>
                    <span class="number"><?= htmlspecialchars($totalBookings) ?></span>
                </div>
                <br>
                <div>
                    <p>Total of Available Rooms:</p>
                    <span class="number"><?= htmlspecialchars($totalAvailableRooms) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="lastpage">
    <div class="available-rooms-section" style="margin-left: 100px; margin-top: 20px;">
        <h3>Available rooms</h3>
        <div class="room-container" id="available-rooms-container">
            <?php foreach ($displayedRooms as $room): ?>
                <?php
                    $imagePath = $room['image_path'];
                ?>
                <div class="room-card">
                    <?php if (!empty($imagePath)): ?>
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($room['room_type_name']); ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($room['room_type_name']); ?> (Room <?php echo htmlspecialchars($room['room_number']); ?>)</h5>
                        <p><?php echo htmlspecialchars($room['description']); ?></p>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#bookRoomModal" style="text-decoration: none;"
                        onclick="openRoomModal('<?php echo $room['id']; ?>', 'Premium luxury with elegant design and full amenities.', '../images/suite.png')">Details</a>
                        <button class="btn btn-info view-details-btn" data-room-id="<?php echo $room['id']; ?>"
                                 data-bs-toggle="modal" data-bs-target="#bookRoomModal">View Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 20px; margin-left: 40px;">
            Available rooms: <span style="font-weight: bold;"><?php echo $totalAvailableRooms; ?></span>
        </div>
        <?php if ($totalAvailableRooms > 3): ?>
            <div class="navigation-arrows" style="margin-top: 10px; text-align: center;">
                <button id="prev-room" style="padding: 5px 10px; margin: 0 10px;">&lt;</button>
                <button id="next-room" style="padding: 5px 10px; margin: 0 10px;">&gt;</button>
            </div>
        <?php endif; ?>
    </div>

    <div class="booking-history-section" style="margin-top: 20px; margin-bottom: 20px; margin-left: 200px; display: inline-block; vertical-align: top;">
        <h3>Booking history</h3>
        <div class="booking-search">
            <input type="text" id="booking-search-input" placeholder="Date/Room">
            <button id="booking-search-button">&#128269;</button>
        </div>
        <ul class="booking-list" id="booking-history-list">
            <?php if (!empty($allBookingHistoryList)): ?>
                <?php foreach ($allBookingHistoryList as $booking): ?>
                    <li class="booking-item" data-reservation-id="<?php echo $booking['reservation_id']; ?>" style="cursor: pointer;">
                        <?php echo $booking['room_type_name']; ?> (Room <?php echo $booking['room_number']; ?>)
                        <span><?php echo date('m/d/Y', strtotime($booking['check_in'])); ?></span>
                        -
                        <span><?php echo date('m/d/Y', strtotime($booking['check_out'])); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No booking history available.</li>
            <?php endif; ?>
        </ul>
    </div>
   <?php
    echo Modals::layout('bookRoom');

    // Render edit reservation modal with empty data initially
    echo Modals::layout('editReservation', []);

    ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Existing JS code...

    // Add click event listener to booking history list items to open edit reservation modal
    const bookingHistoryList = document.getElementById('booking-history-list');
    bookingHistoryList.addEventListener('click', function(event) {
        const listItem = event.target.closest('.booking-item');
        if (!listItem) return;

        const reservationId = listItem.dataset.reservationId;
        if (!reservationId) return;

        // Find reservation data from bookingDetailsLookupJS
        const reservationData = bookingDetailsLookupJS[reservationId];
        if (!reservationData) {
            alert('Reservation details not found.');
            return;
        }

        // Populate the edit reservation modal form fields
        const modal = new bootstrap.Modal(document.getElementById('editReservationModal'));
        const form = document.getElementById('editReservationForm');
        if (!form) return;

        form.reservation_id.value = reservationData.reservation_id || '';
        form.roomType.value = reservationData.type_id || '';
        form.roomNumber.value = reservationData.room_number || '';
        form.checkInDate.value = reservationData.check_in ? reservationData.check_in.split(' ')[0] : '';
        form.checkOutDate.value = reservationData.check_out ? reservationData.check_out.split(' ')[0] : '';
        form.guests.value = reservationData.guests || '';
        form.status.value = reservationData.reservation_status || '';

        modal.show();
    });
});
</script>

<div style="text-align: center; margin-top: 30px;">
    <a href="accommodation.php" style="text-decoration: none;">
        <button class="btn btn-success btn-lg"
                style="background-color: #00cfff; border: none; cursor: pointer;">BOOK NOW!
        </button>
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Make the PHP arrays available to JavaScript
    const bookingDetailsLookupJS = <?php echo json_encode($bookingDetailsLookup); ?>;
    const allBookingHistoryListJS = <?php echo json_encode($allBookingHistoryList); ?>;
    const allRooms = <?php echo json_encode($allAvailableRooms); ?>; // Make allRooms available

    // Get references to the modal and its elements
    // Remove old roomDetailsModal references and functions
    const bookRoomModal = document.getElementById('bookRoomModal');

    function attachViewDetailsListeners() {
        const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
        viewDetailsButtons.forEach(button => {
            button.addEventListener('click', function () {
                const roomId = this.dataset.roomId;
                // Pre-select room type in booking form
                const roomTypeSelect = document.getElementById('roomType');
                if (roomTypeSelect) {
                    // Find the option with matching room id or name
                    for (let i = 0; i < roomTypeSelect.options.length; i++) {
                        if (roomTypeSelect.options[i].value == roomId) {
                            roomTypeSelect.selectedIndex = i;
                            break;
                        }
                    }
                    // Trigger change event to update image
                    roomTypeSelect.dispatchEvent(new Event('change'));
                }
                // Show the booking modal
                if (bookRoomModal) {
                    var modal = new bootstrap.Modal(bookRoomModal);
                    modal.show();
                }
            });
        });
    }

    attachViewDetailsListeners();

    // --- JavaScript for Booking History ---
    const bookingHistoryList = document.getElementById('booking-history-list');
    const bookingSearchInput = document.getElementById('booking-search-input');
    const bookingSearchButton = document.getElementById('booking-search-button');

    bookingHistoryList.addEventListener('click', (event) => {
        const listItem = event.target.closest('.booking-item'); // Find the closest parent <li>
        if (listItem) {
            const reservationId = listItem.dataset.reservationId;
            displayBookingDetails(reservationId);
        }
    });

    function displayBookingDetails(reservationId) {
        const booking = bookingDetailsLookupJS[reservationId];
        if (booking) {
            alert(`Reservation Details:\n` +
                `Room: ${booking.room_type_name} (Room ${booking.room_number})\n` +
                `Check-in: ${booking.check_in}\n` +
                `Check-out: ${booking.check_out}\n` +
                `Guests: ${booking.guests}\n` +
                `Status: ${booking.reservation_status}`);
        } else {
            alert('Reservation details not found.');
        }
    }

    bookingSearchButton.addEventListener('click', () => {
        const searchTerm = bookingSearchInput.value.toLowerCase();
        const filteredList = allBookingHistoryListJS.filter(booking => {
            return booking.room_number.toLowerCase().includes(searchTerm) ||
                booking.check_in.toLowerCase().includes(searchTerm) ||
                booking.check_out.toLowerCase().includes(searchTerm);
        });
        updateBookingList(filteredList);
    });

    function updateBookingList(bookings) {
        bookingHistoryList.innerHTML = ''; // Clear the current list
        if (bookings.length === 0) {
            bookingHistoryList.innerHTML = '<li>No matching bookings found.</li>';
        } else {
            bookings.forEach(booking => {
                const listItem = document.createElement('li');
                listItem.className = 'booking-item';
                listItem.dataset.reservationId = booking.reservation_id;  // Use reservation_id
                listItem.style.cursor = 'pointer';
                listItem.innerHTML = `${booking.room_type_name} (Room ${booking.room_number}) 
                                     <span>${booking.check_in}</span> - 
                                     <span>${booking.check_out}</span>`;
                bookingHistoryList.appendChild(listItem);
            });
        }
    }

    // --- JavaScript for Available Rooms Carousel ---
    let currentIndex = 0;
    const roomsPerPage = 3;
    let allRoomsCopy = [...allRooms]; // Create a copy to avoid modifying the original

    const roomContainer = document.getElementById('available-rooms-container');
    const prevButton = document.getElementById('prev-room');
    const nextButton = document.getElementById('next-room');

    function displayRooms(rooms) {
        roomContainer.innerHTML = '';
        rooms.forEach(room => {
            const roomCard = document.createElement('div');
            roomCard.className = 'room-card';
            roomCard.innerHTML = `
                ${room.image_path ? `<img src="${room.image_path}" alt="${room.room_type_name}">` : ''}
                <div class="card-body">
                    <h5>${room.room_type_name} (Room ${room.room_number})</h5>
                    <p>${room.description}</p>
                    <button class="btn btn-info view-details-btn" data-room-id="${room.id}" data-toggle="modal" data-target="#bookRoomModal">Book Room</button>
                </div>
            `;
            roomContainer.appendChild(roomCard);
        });
        // Re-attach event listeners to the "View Details" buttons
        attachViewDetailsListeners();
    }

    prevButton.addEventListener('click', () => {
        currentIndex = Math.max(0, currentIndex - roomsPerPage);
        const newRooms = allRoomsCopy.slice(currentIndex, currentIndex + roomsPerPage);
        displayRooms(newRooms);
    });

    nextButton.addEventListener('click', () => {
        currentIndex = Math.min(currentIndex + roomsPerPage, allRoomsCopy.length - roomsPerPage);
        const newRooms = allRoomsCopy.slice(currentIndex, currentIndex + roomsPerPage);
        displayRooms(newRooms);
    });

    // Initial display
    displayRooms(allRoomsCopy.slice(currentIndex, currentIndex + roomsPerPage));
});
</script>

<?php include 'footer.php'; ?>