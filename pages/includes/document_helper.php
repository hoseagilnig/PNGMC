<?php
/**
 * Document Helper Functions
 * Functions for saving uploaded files to application_documents table
 */

require_once __DIR__ . '/db_config.php';

/**
 * Save uploaded file to application_documents table
 * @param int $application_id Application ID
 * @param string $document_type Document type (from ENUM)
 * @param string $file_path Path where file was saved
 * @param string $original_filename Original filename
 * @return bool Success status
 */
function saveApplicationDocument($application_id, $document_type, $file_path, $original_filename) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'application_documents'");
    if (!$table_check || $table_check->num_rows === 0) {
        $conn->close();
        return false;
    }
    
    // Check if document_type is valid - if not, use 'other'
    $valid_types = ['grade_12_certificate', 'transcript', 'birth_certificate', 'medical_certificate', 
                    'police_clearance', 'passport_photo', 'nmsa_approval_letter', 'sea_service_record', 
                    'coc_certificate', 'previous_certificates', 'other'];
    
    if (!in_array($document_type, $valid_types)) {
        // Try to map common types
        $type_map = [
            'nmsa_approval' => 'nmsa_approval_letter',
            'sea_service' => 'sea_service_record',
            'coc' => 'coc_certificate'
        ];
        $document_type = $type_map[$document_type] ?? 'other';
    }
    
    // Insert document record
    $stmt = $conn->prepare("INSERT INTO application_documents (application_id, document_type, document_name, file_path) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    
    $stmt->bind_param("isss", $application_id, $document_type, $original_filename, $file_path);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Save multiple documents for an application
 * @param int $application_id Application ID
 * @param array $documents Array of ['type' => document_type, 'path' => file_path, 'name' => original_filename]
 * @return int Number of documents saved
 */
function saveApplicationDocuments($application_id, $documents) {
    $saved = 0;
    foreach ($documents as $doc) {
        if (saveApplicationDocument($application_id, $doc['type'], $doc['path'], $doc['name'])) {
            $saved++;
        }
    }
    return $saved;
}

?>

