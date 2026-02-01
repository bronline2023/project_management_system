<?php
/**
 * models/hr.php
 * Contains functions for HR management.
 * FINAL & COMPLETE:
 * - The calculateSalary function has been fixed to correctly calculate salary even when check-in/out times are not provided for 'Present' or 'Half Day' statuses.
 * - It now considers the 'required_daily_hours' setting as a fallback in such cases.
 */

if (!function_exists('connectDB')) {
    require_once __DIR__ . '/db.php';
}

function calculateSalary($userId, $month, $year, $getDetails = false) {
    $pdo = connectDB();
    $user = fetchOne($pdo, "SELECT salary FROM users WHERE id = ?", [$userId]);
    $settings = fetchOne($pdo, "SELECT required_daily_hours FROM settings LIMIT 1");
    
    if (!$user || !$settings) {
        $details = ['net_salary' => 0, 'deductions' => 0, 'working_days' => 0, 'present_days' => 0, 'half_days' => 0, 'absent_days' => 0];
        return $getDetails ? $details : 0;
    }

    $baseSalary = (float)$user['salary'];
    $requiredDailyHours = (float)$settings['required_daily_hours'];

    if ($baseSalary <= 0 || $requiredDailyHours <= 0) {
        $details = ['net_salary' => 0, 'deductions' => $baseSalary, 'working_days' => 0, 'present_days' => 0, 'half_days' => 0, 'absent_days' => 0];
        return $getDetails ? $details : 0;
    }

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $workingDays = 0;
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = new DateTime("$year-$month-$d");
        if ($date->format('N') < 7) { // Exclude Sundays
            $workingDays++;
        }
    }

    if ($workingDays == 0) {
         $details = ['net_salary' => 0, 'deductions' => $baseSalary, 'working_days' => 0, 'present_days' => 0, 'half_days' => 0, 'absent_days' => 0];
        return $getDetails ? $details : 0;
    }
    
    $hourlyRate = $baseSalary / ($workingDays * $requiredDailyHours);
    
    $attendance = fetchAll($pdo, "SELECT work_duration_hours, status FROM attendance WHERE user_id = ? AND MONTH(entry_date) = ? AND YEAR(entry_date) = ?", [$userId, $month, $year]);

    $totalWorkedHours = 0;
    $presentDays = 0;
    $halfDays = 0;
    $absentDaysCount = 0; // Keep track of absent days from DB

    foreach ($attendance as $att) {
        $duration = (float)$att['work_duration_hours'];
        
        // --- [ SALARY CALCULATION FIX ] ---
        if ($duration > 0) {
            // If duration is logged, use it directly
            $totalWorkedHours += $duration;
        } else {
            // If no check-in/out times, calculate based on status
            if ($att['status'] == 'present') {
                $totalWorkedHours += $requiredDailyHours;
            } elseif ($att['status'] == 'half_day') {
                $totalWorkedHours += ($requiredDailyHours / 2);
            }
        }

        if($att['status'] == 'present') $presentDays++;
        if($att['status'] == 'half_day') $halfDays++;
        if($att['status'] == 'absent') $absentDaysCount++;
    }
    
    $earnedSalary = $totalWorkedHours * $hourlyRate;
    $deductions = $baseSalary - $earnedSalary;
    
    // Ensure deductions are not negative
    $deductions = ($deductions > 0) ? $deductions : 0;

    if ($getDetails) {
        // Calculate absent days based on total working days minus days with attendance records
        $totalRecordedDays = count($attendance);
        $absent_days = $workingDays - $totalRecordedDays + $absentDaysCount;

        return [
            'net_salary' => $earnedSalary,
            'deductions' => $deductions,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absent_days
        ];
    }

    return $earnedSalary;
}

function markAttendance($userId, $entryDate, $status, $checkIn, $checkOut) {
    $pdo = connectDB();
    
    $work_duration = 0;
    if (!empty($checkIn) && !empty($checkOut)) {
        $checkInTime = new DateTime($entryDate . ' ' . $checkIn);
        $checkOutTime = new DateTime($entryDate . ' ' . $checkOut);
        if ($checkOutTime > $checkInTime) {
            $interval = $checkInTime->diff($checkOutTime);
            $work_duration = $interval->h + ($interval->i / 60);
        }
    }
    
    $checkInValue = (!empty($checkIn) && $status !== 'absent') ? $entryDate . ' ' . $checkIn : null;
    $checkOutValue = (!empty($checkOut) && $status !== 'absent') ? $entryDate . ' ' . $checkOut : null;
    
    try {
        $existing = fetchOne($pdo, "SELECT id FROM attendance WHERE user_id = ? AND entry_date = ?", [$userId, $entryDate]);
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, check_in = ?, check_out = ?, work_duration_hours = ? WHERE id = ?");
            return $stmt->execute([$status, $checkInValue, $checkOutValue, $work_duration, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, entry_date, status, check_in, check_out, work_duration_hours) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$userId, $entryDate, $status, $checkInValue, $checkOutValue, $work_duration]);
        }
    } catch (PDOException $e) {
        error_log("Attendance Error: " . $e->getMessage());
        return false;
    }
}
?>
