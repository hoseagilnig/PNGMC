<?php
/**
 * Fee Management Helper Functions
 * Provides functions for fee management operations
 */

require_once __DIR__ . '/db_config.php';

/**
 * Get fee statistics for dashboard
 */
function getFeeStatistics() {
    $conn = getDBConnection();
    $stats = [
        'total_outstanding' => 0,
        'total_receipts_today' => 0,
        'total_receipts_week' => 0,
        'total_invoices' => 0,
        'students_with_holds' => 0,
        'overdue_fees' => 0,
        'pending_reminders' => 0
    ];
    
    if ($conn) {
        // Check if fee management tables exist
        $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
        
        if ($tables_exist) {
            // Total outstanding fees
            $result = $conn->query("SELECT SUM(outstanding_amount) as total FROM student_fees WHERE status IN ('pending', 'partial', 'overdue')");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_outstanding'] = floatval($row['total'] ?? 0);
            }
            
            // Receipts today
            $result = $conn->query("SELECT SUM(amount_paid) as total FROM fee_payment_history WHERE payment_date = CURDATE()");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_receipts_today'] = floatval($row['total'] ?? 0);
            }
            
            // Receipts this week
            $result = $conn->query("SELECT SUM(amount_paid) as total FROM fee_payment_history WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_receipts_week'] = floatval($row['total'] ?? 0);
            }
            
            // Total invoices
            $result = $conn->query("SELECT COUNT(*) as total FROM student_fees WHERE invoice_id IS NOT NULL");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_invoices'] = intval($row['total'] ?? 0);
            }
            
            // Students with financial holds
            $result = $conn->query("SELECT COUNT(DISTINCT student_id) as total FROM student_holds WHERE hold_type = 'financial' AND is_active = TRUE");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['students_with_holds'] = intval($row['total'] ?? 0);
            }
            
            // Overdue fees
            $result = $conn->query("SELECT SUM(outstanding_amount) as total FROM student_fees WHERE status = 'overdue' OR (due_date < CURDATE() AND outstanding_amount > 0)");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['overdue_fees'] = floatval($row['total'] ?? 0);
            }
            
            // Pending reminders
            $result = $conn->query("SELECT COUNT(*) as total FROM payment_reminders WHERE status = 'pending' AND reminder_date <= CURDATE()");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['pending_reminders'] = intval($row['total'] ?? 0);
            }
        } else {
            // Fallback to existing invoice system
            $result = $conn->query("SELECT SUM(balance_amount) as total FROM invoices WHERE balance_amount > 0");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_outstanding'] = floatval($row['total'] ?? 0);
            }
            
            $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_date = CURDATE()");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_receipts_today'] = floatval($row['total'] ?? 0);
            }
        }
        
        $conn->close();
    }
    
    return $stats;
}

/**
 * Get outstanding fees by various parameters
 */
function getOutstandingByParameter($parameter = 'all', $value = null) {
    $conn = getDBConnection();
    $results = [];
    
    if ($conn) {
        $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
        
        if ($tables_exist) {
            $sql = "SELECT 
                sf.*,
                s.first_name,
                s.last_name,
                s.student_number,
                p.program_name,
                fp.plan_name
            FROM student_fees sf
            JOIN students s ON sf.student_id = s.student_id
            LEFT JOIN enrollments e ON s.student_id = e.student_id
            LEFT JOIN programs p ON e.program_id = p.program_id
            LEFT JOIN fee_plans fp ON sf.plan_id = fp.plan_id
            WHERE sf.outstanding_amount > 0";
            
            switch ($parameter) {
                case 'program':
                    if ($value !== null) {
                        $sql .= " AND p.program_id = " . intval($value);
                    }
                    break;
                case 'department':
                    // Assuming programs have department info
                    if ($value !== null && $value !== '') {
                        $sql .= " AND p.department = '" . $conn->real_escape_string($value) . "'";
                    }
                    break;
                case 'location':
                    if ($value !== null && $value !== '') {
                        $sql .= " AND s.city = '" . $conn->real_escape_string($value) . "'";
                    }
                    break;
                case 'sponsorship':
                    // Would need sponsorship table
                    break;
            }
            
            $sql .= " ORDER BY sf.due_date ASC";
            
            $result = $conn->query($sql);
            if ($result) {
                $results = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        $conn->close();
    }
    
    return $results;
}

/**
 * Get ageing analysis
 */
function getAgeingAnalysis($summary = true) {
    $conn = getDBConnection();
    $results = [];
    
    if ($conn) {
        $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
        
        if ($tables_exist) {
            if ($summary) {
                $sql = "SELECT 
                    CASE 
                        WHEN DATEDIFF(CURDATE(), due_date) <= 30 THEN '0-30 days'
                        WHEN DATEDIFF(CURDATE(), due_date) <= 60 THEN '31-60 days'
                        WHEN DATEDIFF(CURDATE(), due_date) <= 90 THEN '61-90 days'
                        ELSE '90+ days'
                    END as age_bucket,
                    COUNT(*) as count,
                    SUM(outstanding_amount) as total_amount
                FROM student_fees
                WHERE outstanding_amount > 0
                GROUP BY age_bucket
                ORDER BY 
                    CASE age_bucket
                        WHEN '0-30 days' THEN 1
                        WHEN '31-60 days' THEN 2
                        WHEN '61-90 days' THEN 3
                        ELSE 4
                    END";
            } else {
                $sql = "SELECT 
                    sf.*,
                    s.first_name,
                    s.last_name,
                    s.student_number,
                    p.program_name,
                    DATEDIFF(CURDATE(), sf.due_date) as days_overdue
                FROM student_fees sf
                JOIN students s ON sf.student_id = s.student_id
                LEFT JOIN enrollments e ON s.student_id = e.student_id
                LEFT JOIN programs p ON e.program_id = p.program_id
                WHERE sf.outstanding_amount > 0
                ORDER BY sf.due_date ASC";
            }
            
            $result = $conn->query($sql);
            if ($result) {
                $results = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        $conn->close();
    }
    
    return $results;
}

/**
 * Automatically create student holds for outstanding fees
 */
function createAutomaticHolds($threshold = 0) {
    $conn = getDBConnection();
    $holds_created = 0;
    
    if ($conn) {
        $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
        
        if ($tables_exist) {
            // Find students with outstanding fees above threshold
            $sql = "SELECT 
                s.student_id,
                SUM(sf.outstanding_amount) as total_outstanding
            FROM students s
            JOIN student_fees sf ON s.student_id = sf.student_id
            WHERE sf.outstanding_amount > 0
            AND sf.status IN ('pending', 'partial', 'overdue')
            AND NOT EXISTS (
                SELECT 1 FROM student_holds sh 
                WHERE sh.student_id = s.student_id 
                AND sh.hold_type = 'financial' 
                AND sh.is_active = TRUE
                AND sh.is_automatic = TRUE
            )
            GROUP BY s.student_id
            HAVING total_outstanding > " . floatval($threshold);
            
            $result = $conn->query($sql);
            
            while ($row = $result->fetch_assoc()) {
                $reason = "Outstanding fees: PGK " . number_format($row['total_outstanding'], 2);
                $stmt = $conn->prepare("INSERT INTO student_holds (student_id, hold_type, reason, outstanding_amount, is_automatic, is_active) VALUES (?, 'financial', ?, ?, TRUE, TRUE)");
                $stmt->bind_param("isd", $row['student_id'], $reason, $row['total_outstanding']);
                if ($stmt->execute()) {
                    $holds_created++;
                }
                $stmt->close();
            }
        }
        
        $conn->close();
    }
    
    return $holds_created;
}

/**
 * Get payment mode analysis
 */
function getPaymentModeAnalysis($period = 'week') {
    $conn = getDBConnection();
    $results = [];
    
    if ($conn) {
        $tables_exist = $conn->query("SHOW TABLES LIKE 'fee_payment_history'")->num_rows > 0;
        
        if ($tables_exist) {
            $date_condition = $period === 'week' 
                ? "payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
                : "payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            
            $sql = "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(amount_paid) as total_amount
            FROM fee_payment_history
            WHERE $date_condition
            GROUP BY payment_method
            ORDER BY total_amount DESC";
            
            $result = $conn->query($sql);
            if ($result) {
                $results = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        $conn->close();
    }
    
    return $results;
}

/**
 * Get invoice vs receipt analysis
 */
function getInvoiceReceiptAnalysis($period = 'week') {
    $conn = getDBConnection();
    $results = [];
    
    if ($conn) {
        $tables_exist = $conn->query("SHOW TABLES LIKE 'student_fees'")->num_rows > 0;
        
        if ($tables_exist) {
            $date_condition = $period === 'week' 
                ? "DATE(sf.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
                : "DATE(sf.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            
            $sql = "SELECT 
                DATE(sf.created_at) as date,
                COUNT(DISTINCT sf.invoice_id) as invoices_count,
                SUM(sf.net_amount) as invoices_amount,
                COUNT(DISTINCT fph.payment_id) as receipts_count,
                SUM(fph.amount_paid) as receipts_amount
            FROM student_fees sf
            LEFT JOIN fee_payment_history fph ON sf.fee_id = fph.fee_id
            WHERE $date_condition
            GROUP BY DATE(sf.created_at)
            ORDER BY date DESC";
            
            $result = $conn->query($sql);
            if ($result) {
                $results = $result->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        $conn->close();
    }
    
    return $results;
}

