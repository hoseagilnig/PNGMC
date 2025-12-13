<?php
/**
 * Finance-SAS Workflow Helper Functions
 * Provides functions for the workflow between Finance and Student Admin Services
 */

require_once __DIR__ . '/db_config.php';

/**
 * Generate Proforma Invoice Number (MCSA format)
 */
function generatePINumber() {
    $conn = getDBConnection();
    $year = date('Y');
    $prefix = "MCSA-{$year}-";
    
    // Get the last PI number for this year
    $result = $conn->query("SELECT pi_number FROM proforma_invoices WHERE pi_number LIKE '{$prefix}%' ORDER BY pi_id DESC LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $last_number = $result->fetch_assoc()['pi_number'];
        $last_seq = intval(substr($last_number, strrpos($last_number, '-') + 1));
        $new_seq = str_pad($last_seq + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_seq = '0001';
    }
    
    $conn->close();
    return $prefix . $new_seq;
}

/**
 * Get student schedules by type
 */
function getStudentSchedules($schedule_type, $filters = []) {
    $conn = getDBConnection();
    $results = [];
    
    if ($conn) {
        $tables_exist = $conn->query("SHOW TABLES LIKE 'student_schedules'")->num_rows > 0;
        
        if ($tables_exist) {
            $sql = "SELECT * FROM student_schedules WHERE schedule_type = ?";
            $params = [$schedule_type];
            $types = "s";
            
            if (isset($filters['student_id'])) {
                $sql .= " AND student_id = ?";
                $params[] = $filters['student_id'];
                $types .= "i";
            }
            
            if (isset($filters['program_course_name'])) {
                $sql .= " AND program_course_name LIKE ?";
                $params[] = '%' . $filters['program_course_name'] . '%';
                $types .= "s";
            }
            
            $sql .= " ORDER BY student_name";
            
            $stmt = $conn->prepare($sql);
            if (count($params) > 1) {
                $stmt->bind_param($types, ...$params);
            } else {
                $stmt->bind_param($types, $schedule_type);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            
            $stmt->close();
        }
        
        $conn->close();
    }
    
    return $results;
}

/**
 * Calculate daily rate for Red & Green Days
 */
function calculateDailyRate($course_fee, $start_date, $ending_date) {
    if (!$ending_date || !$start_date) {
        return 0;
    }
    
    $start = new DateTime($start_date);
    $end = new DateTime($ending_date);
    $days = $start->diff($end)->days;
    
    if ($days == 0) {
        return 0;
    }
    
    return floatval($course_fee) / $days;
}

/**
 * Calculate days paid and unpaid for Red & Green Days
 */
function calculateRedGreenDays($student_id, $course_fee, $start_date, $ending_date, $amount_paid) {
    $daily_rate = calculateDailyRate($course_fee, $start_date, $ending_date);
    
    if ($daily_rate == 0) {
        return [
            'days_paid' => 0,
            'days_non_paid' => 0,
            'overstayed_days' => 0,
            'unpaid_days' => 0,
            'alert_flag' => 'green'
        ];
    }
    
    $days_paid = intval($amount_paid / $daily_rate);
    
    $start = new DateTime($start_date);
    $end = $ending_date ? new DateTime($ending_date) : new DateTime();
    $total_days = $start->diff($end)->days;
    
    $days_non_paid = max(0, $total_days - $days_paid);
    
    // Calculate overstayed days (if ending date passed and still unpaid)
    $today = new DateTime();
    $overstayed_days = 0;
    if ($ending_date && $today > new DateTime($ending_date)) {
        $overstayed_days = $today->diff(new DateTime($ending_date))->days;
    }
    
    // Unpaid days
    $unpaid_days = max(0, $days_non_paid - $overstayed_days);
    
    // Alert flag
    $alert_flag = 'green';
    if ($unpaid_days > 30) {
        $alert_flag = 'red';
    } elseif ($unpaid_days > 14) {
        $alert_flag = 'yellow';
    }
    
    return [
        'days_paid' => $days_paid,
        'days_non_paid' => $days_non_paid,
        'overstayed_days' => $overstayed_days,
        'unpaid_days' => $unpaid_days,
        'alert_flag' => $alert_flag,
        'daily_rate' => $daily_rate
    ];
}

/**
 * Create or update student schedule
 */
function createStudentSchedule($schedule_type, $student_id, $data) {
    $conn = getDBConnection();
    
    if (!$conn) {
        return false;
    }
    
    $tables_exist = $conn->query("SHOW TABLES LIKE 'student_schedules'")->num_rows > 0;
    
    if (!$tables_exist) {
        $conn->close();
        return false;
    }
    
    // Check if schedule already exists
    $check = $conn->prepare("SELECT schedule_id FROM student_schedules WHERE schedule_type = ? AND student_id = ?");
    $check->bind_param("si", $schedule_type, $student_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    
    if ($exists) {
        // Update existing schedule (note: student_schedules table doesn't have updated_at column)
        $sql = "UPDATE student_schedules SET 
            student_number = ?,
            student_name = ?,
            student_address = ?,
            sponsor_name = ?,
            sponsor_type = ?,
            program_course_name = ?,
            program_course_start_date = ?,
            program_course_ending_date = ?,
            program_course_tuition_fee = ?,
            amount_paid = ?,
            balance = ?,
            dates_fees_paid = ?,
            proforma_invoice_number = ?,
            revised_pi_number = ?,
            remarks = ?
            WHERE schedule_type = ? AND student_id = ?";
        
        // Extract values to variables (required for bind_param which requires references)
        $student_number = $data['student_number'] ?? '';
        $student_name = $data['student_name'] ?? '';
        $student_address = $data['student_address'] ?? null;
        $sponsor_name = $data['sponsor_name'] ?? null;
        $sponsor_type = $data['sponsor_type'] ?? null;
        $program_course_name = $data['program_course_name'] ?? '';
        $program_course_start_date = $data['program_course_start_date'] ?? null;
        $program_course_ending_date = $data['program_course_ending_date'] ?? null;
        $program_course_tuition_fee = $data['program_course_tuition_fee'] ?? 0;
        $amount_paid = $data['amount_paid'] ?? 0;
        $balance = $data['balance'] ?? 0;
        $dates_fees_paid = $data['dates_fees_paid'] ?? null;
        $proforma_invoice_number = $data['proforma_invoice_number'] ?? null;
        $revised_pi_number = $data['revised_pi_number'] ?? null;
        $remarks = $data['remarks'] ?? null;
        
        $stmt = $conn->prepare($sql);
        // Type string: s=string, d=double, i=integer
        // 15 SET parameters: s s s s s s s s d d d s s s s (15 params)
        // 2 WHERE parameters: s i (2 params)
        // Total: 17 parameters
        $stmt->bind_param("ssssssssdddsssssi",
            $student_number,        // s
            $student_name,           // s
            $student_address,        // s
            $sponsor_name,           // s
            $sponsor_type,           // s
            $program_course_name,    // s
            $program_course_start_date,  // s
            $program_course_ending_date,  // s
            $program_course_tuition_fee,  // d
            $amount_paid,            // d
            $balance,                // d
            $dates_fees_paid,        // s
            $proforma_invoice_number, // s
            $revised_pi_number,      // s
            $remarks,                // s
            $schedule_type,          // s (WHERE)
            $student_id              // i (WHERE)
        );
    } else {
        // Create new schedule
        $sql = "INSERT INTO student_schedules (
            schedule_type, student_id, student_number, student_name, student_address,
            sponsor_name, sponsor_type, program_course_name, program_course_start_date,
            program_course_ending_date, program_course_tuition_fee, amount_paid, balance,
            dates_fees_paid, proforma_invoice_number, revised_pi_number, remarks, generated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Extract values to variables (required for bind_param which requires references)
        $student_number = $data['student_number'] ?? '';
        $student_name = $data['student_name'] ?? '';
        $student_address = $data['student_address'] ?? null;
        $sponsor_name = $data['sponsor_name'] ?? null;
        $sponsor_type = $data['sponsor_type'] ?? null;
        $program_course_name = $data['program_course_name'] ?? '';
        $program_course_start_date = $data['program_course_start_date'] ?? null;
        $program_course_ending_date = $data['program_course_ending_date'] ?? null;
        $program_course_tuition_fee = $data['program_course_tuition_fee'] ?? 0;
        $amount_paid = $data['amount_paid'] ?? 0;
        $balance = $data['balance'] ?? 0;
        $dates_fees_paid = $data['dates_fees_paid'] ?? null;
        $proforma_invoice_number = $data['proforma_invoice_number'] ?? null;
        $revised_pi_number = $data['revised_pi_number'] ?? null;
        $remarks = $data['remarks'] ?? null;
        $generated_by = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssssddsssssi",
            $schedule_type,
            $student_id,
            $student_number,
            $student_name,
            $student_address,
            $sponsor_name,
            $sponsor_type,
            $program_course_name,
            $program_course_start_date,
            $program_course_ending_date,
            $program_course_tuition_fee,
            $amount_paid,
            $balance,
            $dates_fees_paid,
            $proforma_invoice_number,
            $revised_pi_number,
            $remarks,
            $generated_by
        );
    }
    
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $success;
}

