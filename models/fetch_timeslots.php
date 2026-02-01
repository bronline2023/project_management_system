<?php
/**
 * models/fetch_timeslots.php
 * AJAX endpoint to fetch available appointment time slots for a given date.
 */

require_once __DIR__ . '/../config.php';
require_once MODELS_PATH . 'db.php';

header('Content-Type: application/json');

$pdo = connectDB();
$selectedDate = $_GET['date'] ?? null;

if (!$selectedDate) {
    echo json_encode(['error' => 'No date selected.']);
    exit;
}

try {
    $settings = fetchOne($pdo, "SELECT office_start_time, office_end_time, appointment_slot_duration, office_working_days FROM settings WHERE id = 1 LIMIT 1");

    $dayOfWeek = date('N', strtotime($selectedDate));
    $workingDays = explode(',', $settings['office_working_days']);

    if (!in_array($dayOfWeek, $workingDays) || strtotime($selectedDate) < strtotime('today')) {
        echo json_encode(['slots' => []]); // Not a working day or a past date
        exit;
    }

    $startTime = new DateTime($settings['office_start_time']);
    $endTime = new DateTime($settings['office_end_time']);
    $slotDuration = new DateInterval('PT' . ($settings['appointment_slot_duration'] ?? 30) . 'M');

    // Fetch booked slots and their counts
    $bookedSlotsStmt = $pdo->prepare("SELECT appointment_time, COUNT(id) as count FROM appointments WHERE appointment_date = ? AND status != 'cancelled' GROUP BY appointment_time");
    $bookedSlotsStmt->execute([$selectedDate]);
    $bookedSlotsWithCount = $bookedSlotsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $availableSlots = [];
    $currentTime = clone $startTime;

    while ($currentTime < $endTime) {
        $slotValue = $currentTime->format('H:i:s');
        $slotCount = $bookedSlotsWithCount[$slotValue] ?? 0;
        
        $label = $currentTime->format('h:i A');
        if ($slotCount > 0) {
            $label .= " ({$slotCount} booked)";
        }
        
        $availableSlots[] = [
            'value' => $currentTime->format('H:i'),
            'label' => $label
        ];
        
        $currentTime->add($slotDuration);
    }
    
    echo json_encode(['slots' => $availableSlots]);

} catch (Exception $e) {
    error_log("Timeslot Fetch Error: " . $e->getMessage());
    echo json_encode(['error' => 'Could not fetch time slots.']);
}
?>