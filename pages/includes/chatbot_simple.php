<?php
/**
 * Simplified Chatbot Component - Standalone Version
 */
$user_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['name'] ?? 'User';
?>
<div id="chatbot-container" class="chatbot-container">
    <div id="chatbot-toggle" class="chatbot-toggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </div>
    
    <div id="chatbot-window" class="chatbot-window">
        <div class="chatbot-header">
            <div class="chatbot-header-content">
                <h3>💬 System Help Assistant</h3>
                <p class="chatbot-subtitle">Ask me anything about using the system</p>
            </div>
            <div class="chatbot-header-buttons">
                <button class="chatbot-refresh" title="Clear chat">🔄</button>
                <button class="chatbot-close">×</button>
            </div>
        </div>
        
        <div class="chatbot-messages" id="chatbot-messages">
            <div class="chatbot-message bot-message">
                <div class="message-content">
                    <p>Hello <?php echo htmlspecialchars($user_name); ?>! 👋</p>
                    <p>I'm here to help you learn how to use the system. Try asking:</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>"How do I review applications?"</li>
                        <li>"How to generate an invoice?"</li>
                        <li>"What is the workflow?"</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="chatbot-quick-topics" id="chatbot-quick-topics"></div>
        
        <div class="chatbot-input-area">
            <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Type your question here...">
            <button class="chatbot-send" type="button">Send</button>
        </div>
    </div>
</div>

<style>
.chatbot-container {
    position: fixed;
    bottom: 15px;
    right: 15px;
    z-index: 99999;
    font-family: Arial, sans-serif;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

@media (min-width: 768px) {
    .chatbot-container {
        bottom: 20px;
        right: 20px;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}

/* Ensure chatbot is visible on workstation and laptop views */
@media (min-width: 1200px) {
    .chatbot-container {
        bottom: 25px;
        right: 25px;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}

@media (min-width: 1440px) {
    .chatbot-container {
        bottom: 30px;
        right: 30px;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}

.chatbot-toggle {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
    border-radius: 50%;
    display: flex !important;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(29, 78, 137, 0.4);
    transition: all 0.3s ease;
    color: white;
    visibility: visible !important;
    opacity: 1 !important;
}

@media (min-width: 768px) {
    .chatbot-toggle {
        width: 60px;
        height: 60px;
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}

/* Ensure chatbot toggle is visible on workstation and laptop views */
@media (min-width: 1200px) {
    .chatbot-toggle {
        width: 65px;
        height: 65px;
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}

@media (min-width: 1440px) {
    .chatbot-toggle {
        width: 70px;
        height: 70px;
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}

.chatbot-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(29, 78, 137, 0.6);
}

.chatbot-window {
    position: fixed;
    bottom: 70px;
    right: 15px;
    left: 15px;
    width: auto;
    height: calc(100vh - 100px);
    max-height: 600px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 99999;
}

@media (min-width: 768px) {
    .chatbot-window {
        position: absolute;
        bottom: 80px;
        right: 0;
        left: auto;
        width: 380px;
        height: 600px;
        max-height: 80vh;
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
    
    .chatbot-window.active {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        z-index: 99999 !important;
    }
}

/* Ensure chatbot window works on workstation and laptop views */
@media (min-width: 1200px) {
    .chatbot-window {
        width: 420px;
        height: 650px;
        max-height: 85vh;
        bottom: 90px;
        right: 0;
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
    
    .chatbot-window.active {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        z-index: 99999 !important;
    }
}

@media (min-width: 1440px) {
    .chatbot-window {
        width: 450px;
        height: 700px;
        max-height: 85vh;
        bottom: 100px;
        right: 0;
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
    
    .chatbot-window.active {
        display: flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        z-index: 99999 !important;
    }
}

.chatbot-window.active {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}

.chatbot-header {
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 10px;
}

@media (min-width: 768px) {
    .chatbot-header {
        padding: 20px;
        flex-wrap: nowrap;
    }
}

.chatbot-header-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-shrink: 0;
}

@media (min-width: 768px) {
    .chatbot-header-buttons {
        gap: 10px;
    }
}

.chatbot-header-content h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 700;
}

@media (min-width: 768px) {
    .chatbot-header-content h3 {
        font-size: 18px;
    }
}

.chatbot-subtitle {
    margin: 0;
    font-size: 11px;
    opacity: 0.9;
}

@media (min-width: 768px) {
    .chatbot-subtitle {
        font-size: 12px;
    }
}

.chatbot-refresh {
    background: none;
    border: none;
    color: white;
    font-size: 16px;
    cursor: pointer;
    padding: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

@media (min-width: 768px) {
    .chatbot-refresh {
        font-size: 18px;
        width: 30px;
        height: 30px;
    }
}

.chatbot-refresh:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(180deg);
}

.chatbot-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

@media (min-width: 768px) {
    .chatbot-close {
        font-size: 28px;
        width: 30px;
        height: 30px;
    }
}

.chatbot-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
    -webkit-overflow-scrolling: touch;
}

@media (min-width: 768px) {
    .chatbot-messages {
        padding: 20px;
    }
}

.chatbot-message {
    margin-bottom: 15px;
    display: flex;
}

.chatbot-message.user-message {
    justify-content: flex-end;
}

.message-content {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 12px;
    line-height: 1.5;
    font-size: 13px;
    word-wrap: break-word;
}

@media (min-width: 768px) {
    .message-content {
        max-width: 80%;
        padding: 12px 16px;
        font-size: 14px;
    }
}

.bot-message .message-content {
    background: white;
    border: 1px solid #e0e0e0;
    color: #333;
}

.user-message .message-content {
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
    color: white;
}

.chatbot-quick-topics {
    padding: 12px 15px;
    background: white;
    border-top: 1px solid #e0e0e0;
    max-height: 100px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

@media (min-width: 768px) {
    .chatbot-quick-topics {
        padding: 15px 20px;
        max-height: 120px;
    }
}

.quick-topic {
    display: inline-block;
    background: #f0f7ff;
    color: #1d4e89;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    margin: 4px 4px 4px 0;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #d0e5ff;
    white-space: nowrap;
}

@media (min-width: 768px) {
    .quick-topic {
        padding: 6px 12px;
        font-size: 12px;
        margin: 5px 5px 5px 0;
    }
}

.quick-topic:hover {
    background: #1d4e89;
    color: white;
}

.chatbot-input-area {
    display: flex;
    padding: 12px;
    background: white;
    border-top: 1px solid #e0e0e0;
    gap: 8px;
}

@media (min-width: 768px) {
    .chatbot-input-area {
        padding: 15px;
        gap: 10px;
    }
}

.chatbot-input {
    flex: 1;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 14px;
    outline: none;
    min-width: 0;
    transition: all 0.3s;
}

@media (min-width: 768px) {
    .chatbot-input {
        padding: 12px 15px;
    }
}

.chatbot-input:focus {
    border-color: #1d4e89;
    box-shadow: 0 0 0 3px rgba(29, 78, 137, 0.1);
}

.chatbot-send {
    padding: 10px 16px;
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
    border: none;
    border-radius: 25px;
    color: white;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s;
    white-space: nowrap;
    flex-shrink: 0;
}

@media (min-width: 768px) {
    .chatbot-send {
        padding: 12px 20px;
        font-size: 14px;
    }
}

.chatbot-send:hover {
    transform: scale(1.05);
}
</style>

<script>
(function() {
    'use strict';
    
    const userRole = '<?php echo $user_role; ?>';
    
    // Response data - Comprehensive knowledge base
    const responses = {
        'admin': {
            'workflow': {
                title: 'Workflow Monitoring',
                content: '<p><strong>As Administrator, you can monitor the entire workflow:</strong></p><ul><li><strong>Workflow Monitor:</strong> View overall application statistics, status breakdown, and department-wise distribution</li><li><strong>View Applications:</strong> You can view all applications but cannot perform actions (read-only mode)</li><li><strong>Track Progress:</strong> Monitor how applications move through different departments</li><li><strong>Financial Overview:</strong> View invoice statistics and payment information</li><li><strong>Activity Logs:</strong> See all workflow actions and notifications</li></ul><p><strong>Note:</strong> Administration role is read-only. Only Student Admin Service can perform actions on applications.</p>'
            },
            'application': {
                title: 'Viewing Applications',
                content: '<p><strong>To view applications:</strong></p><ol><li>Go to the Workflow Monitor from your dashboard</li><li>Click on any application to view details</li><li>You can see the complete workflow history and status</li><li>View all submitted documents</li><li>See department assignments and notifications</li></ol><p><strong>Remember:</strong> You can view but not modify applications. This is a monitoring role only.</p>'
            },
            'report': {
                title: 'Viewing Reports',
                content: '<p><strong>Available Reports:</strong></p><ul><li><strong>Application Statistics:</strong> Total applications, by status, by department</li><li><strong>Student Enrollment Reports:</strong> Enrolled students, course history</li><li><strong>Financial Reports:</strong> Invoice statistics, payment tracking, outstanding balances</li><li><strong>Workflow Activity Logs:</strong> All actions taken on applications</li><li><strong>Department Performance:</strong> Track efficiency of each department</li></ul><p>Access reports from the Reports section in your dashboard.</p>'
            },
            'monitor': {
                title: 'Monitoring Features',
                content: '<p><strong>What you can monitor:</strong></p><ul><li><strong>Application Status:</strong> See how many applications are at each stage</li><li><strong>Department Activity:</strong> Track which departments are processing applications</li><li><strong>Financial Status:</strong> Monitor invoices, payments, and outstanding balances</li><li><strong>Workflow Bottlenecks:</strong> Identify where applications are getting stuck</li><li><strong>System Performance:</strong> View overall system health and activity</li></ul>'
            },
            'dashboard': {
                title: 'Admin Dashboard',
                content: '<p><strong>Your dashboard provides:</strong></p><ul><li><strong>Quick Statistics:</strong> Pending applications, HOD reviews, accepted applications</li><li><strong>Workflow Monitor Link:</strong> Access comprehensive workflow monitoring</li><li><strong>View-Only Cards:</strong> View statistics without performing actions</li><li><strong>Recent Activity:</strong> See latest workflow actions</li></ul><p>All features are read-only for monitoring purposes.</p>'
            },
            'help': {
                title: 'Help Topics',
                content: '<p>I can help with:</p><ul><li>Workflow Monitoring</li><li>View Applications</li><li>View Reports</li><li>System Overview</li><li>Dashboard Features</li></ul><p>Try asking about any of these topics!</p>'
            }
        },
        'finance': {
            'invoice': {
                title: 'Generate Invoice',
                content: '<p><strong>How to generate an invoice:</strong></p><ol><li>When an application is approved by HOD, you\'ll receive a notification</li><li>Go to the application details page from the notification or dashboard</li><li>Click "Print Proforma Invoice" button</li><li>The invoice will automatically include fees based on the program</li><li>Fees are calculated from: application program, course type (new/continuing), and program fees</li><li>Print or save the invoice for the applicant</li></ol><p><strong>Note:</strong> Fees are automatically calculated from the application\'s program information. New students: K 24,120.00, Continuing students: K 26,811.00</p>'
            },
            'payment': {
                title: 'View Payments',
                content: '<p><strong>To view payments:</strong></p><ol><li>Go to your Finance dashboard</li><li>Check the "Payments" section</li><li>View payment history and outstanding balances</li><li>Filter by student, date, or status</li><li>Export payment reports if needed</li></ol><p><strong>Payment Tracking:</strong> You can see all payments made, pending payments, and outstanding balances for each student.</p>'
            },
            'red': {
                title: 'Red & Green Days Monitor',
                content: '<p><strong>Red & Green Days System:</strong></p><ul><li><strong>GREEN (0-14 days):</strong> Student is in good standing, fees paid on time</li><li><strong>YELLOW (15-30 days):</strong> Warning - fees overdue, follow up needed</li><li><strong>RED (30+ days):</strong> Critical - immediate action needed, student may be suspended</li></ul><p><strong>How it works:</strong></p><ul><li>Daily rate calculated from course fee divided by total course days</li><li>Days paid: Number of days covered by payments</li><li>Unpaid days: Days within course period with unpaid fees</li><li>Overstayed days: Days beyond course ending date with unpaid fees</li></ul><p>Access this from your dashboard to monitor student fee statuses and take appropriate action.</p>'
            },
            'green': {
                title: 'Red & Green Days Monitor',
                content: '<p><strong>Red & Green Days System:</strong></p><ul><li><strong>GREEN (0-14 days):</strong> Student is in good standing, fees paid on time</li><li><strong>YELLOW (15-30 days):</strong> Warning - fees overdue, follow up needed</li><li><strong>RED (30+ days):</strong> Critical - immediate action needed, student may be suspended</li></ul><p>Access this from your dashboard to monitor student fee statuses.</p>'
            },
            'fee': {
                title: 'Fee Management',
                content: '<p><strong>Fee Management:</strong></p><ul><li><strong>Course Fees:</strong> New students pay K 24,120.00, Continuing students pay K 26,811.00</li><li><strong>Fee Calculation:</strong> Automatically calculated based on program and student type</li><li><strong>Payment Recording:</strong> Record payments against student accounts</li><li><strong>Outstanding Balances:</strong> Track unpaid fees and send reminders</li><li><strong>Fee Reports:</strong> Generate reports on fee collection and outstanding amounts</li></ul>'
            },
            'report': {
                title: 'Financial Reports',
                content: '<p><strong>Financial Reports Available:</strong></p><ul><li><strong>Invoice Statistics:</strong> Total invoices generated, by status, by month</li><li><strong>Payment Tracking:</strong> All payments received, payment methods, dates</li><li><strong>Outstanding Balances:</strong> Students with unpaid fees, amounts due</li><li><strong>Fee Collection Reports:</strong> Total collected, by program, by month</li><li><strong>Red & Green Days Report:</strong> Student fee status summary</li></ul><p>Access reports from your dashboard or the Reports section.</p>'
            },
            'help': {
                title: 'Help Topics',
                content: '<p>I can help with:</p><ul><li>Generate Invoice</li><li>View Payments</li><li>Red & Green Days</li><li>Financial Reports</li><li>Fee Management</li></ul><p>Try asking about any of these topics!</p>'
            }
        },
        'studentservices': {
            'review': {
                title: 'Review Applications',
                content: '<p><strong>Application Review Process:</strong></p><ol><li>New applications appear in your dashboard when submitted by applicants</li><li>Click on an application to review details</li><li>Check all submitted documents (ID, certificates, photos, etc.)</li><li>Verify applicant meets requirements for the program</li><li>Check completeness of application form</li><li>Review applicant qualifications and eligibility</li><li>Forward to appropriate HOD for final decision</li></ol><p><strong>Tip:</strong> You can view and verify all applicant documents from the application details page. Click "View" or "Download" to check documents.</p>'
            },
            'forward': {
                title: 'Forward to HOD',
                content: '<p><strong>To forward an application to HOD:</strong></p><ol><li>Open the application details page</li><li>Click "Forward to HOD" button (available when status is "submitted" or "under_review")</li><li>Select the appropriate HOD based on the program the applicant is interested in</li><li>Add any notes or comments if needed</li><li>Submit - the HOD will be notified automatically</li></ol><p><strong>Note:</strong> HOD selection is based on the program the applicant is interested in. The system will show relevant HODs for that program.</p><p><strong>After forwarding:</strong> Application status changes to "hod_review" and HOD receives notification.</p>'
            },
            'enroll': {
                title: 'Enroll Student',
                content: '<p><strong>Student Enrollment Process:</strong></p><ol><li>Application must be accepted by HOD (status: "accepted")</li><li>All mandatory checks must be completed (medical, police clearance, etc.)</li><li>Go to application details page</li><li>Click "Enroll Student" button</li><li>Student account will be automatically created with username and password</li><li>Login credentials will be displayed - <strong>IMPORTANT: Save these credentials!</strong></li><li>Student is added to the system and can access student portal</li></ol><p><strong>Important:</strong> Make sure to save the student\'s login credentials after enrollment! You can access them later from the student credentials page.</p><p><strong>What happens:</strong> Student account is created, course history is recorded, and student can log into the student portal.</p>'
            },
            'document': {
                title: 'Generate Documents',
                content: '<p><strong>Available Documents:</strong></p><ul><li><strong>Acceptance Letter:</strong> Generated after HOD approval, includes:<ul><li>Course fee information</li><li>Complete list of required documents</li><li>Contact information</li><li>Program details</li></ul></li><li><strong>Proforma Invoice:</strong> Generated by Finance, can be printed from application details page</li><li><strong>Rejection Letter:</strong> Generated when application is rejected, includes:<ul><li>Shortfall details</li><li>HOD comments</li><li>Reasons for rejection</li></ul></li></ul><p>Access these from the application details page after HOD decision. Click "Print Acceptance Letter" or "Print Proforma Invoice" buttons.</p>'
            },
            'notify': {
                title: 'Notify Applicant',
                content: '<p><strong>To notify an applicant:</strong></p><ol><li>Go to application details page</li><li>Click "Notify Applicant" button (available when status is "accepted", "rejected", or "ineligible")</li><li>Select correspondence type (Acceptance Letter, Rejection Letter, Invoice)</li><li>Subject and message are pre-filled based on type</li><li>Customize message if needed</li><li>Send via email or phone (SMS)</li></ol><p><strong>Notification Types:</strong></p><ul><li><strong>Acceptance Letter:</strong> Sent when application is accepted, includes fees and requirements</li><li><strong>Rejection Letter:</strong> Sent when rejected, includes shortfall details</li><li><strong>Invoice:</strong> Sent with proforma invoice information</li></ul>'
            },
            'check': {
                title: 'Mandatory Checks',
                content: '<p><strong>Mandatory Checks Process:</strong></p><ul><li><strong>After Acceptance:</strong> When HOD approves, mandatory checks are created</li><li><strong>Required Documents:</strong> Medical certificate, police clearance, NMSA requirements, etc.</li><li><strong>Document Verification:</strong> View and verify documents uploaded by applicant</li><li><strong>Check Completion:</strong> Mark checks as completed when documents are verified</li><li><strong>Enrollment Prerequisite:</strong> All checks must be completed before enrollment</li></ul><p><strong>Note:</strong> Requirements are listed in the acceptance letter. Applicants provide these documents after receiving the acceptance letter.</p>'
            },
            'student': {
                title: 'Student Account Management',
                content: '<p><strong>Student Accounts:</strong></p><ul><li><strong>Automatic Creation:</strong> Student accounts are created automatically upon enrollment</li><li><strong>Account Status:</strong> Can be active, on hold, suspended, or inactive</li><li><strong>Account Management:</strong> Access "Manage Student Accounts" to view and manage accounts</li><li><strong>Course History:</strong> Track student course history and enrollment dates</li><li><strong>Notifications:</strong> Send notifications to students from your dashboard</li></ul><p><strong>Account Lifecycle:</strong> After course ends, accounts are put "on hold". If student returns, account is reactivated.</p>'
            },
            'workflow': {
                title: 'Application Workflow',
                content: '<p><strong>The complete workflow:</strong></p><ol><li><strong>Submission:</strong> Applicant submits application → Status: "submitted"</li><li><strong>Review:</strong> You review application → Status: "under_review"</li><li><strong>Forward to HOD:</strong> You forward to appropriate HOD → Status: "hod_review"</li><li><strong>HOD Decision:</strong> HOD approves or rejects</li><li><strong>If Approved:</strong> Status: "accepted" → Finance generates invoice, you generate acceptance letter</li><li><strong>If Rejected:</strong> Status: "ineligible" → You generate rejection letter</li><li><strong>Mandatory Checks:</strong> Applicant provides required documents → Status: "checks_pending"</li><li><strong>Checks Completed:</strong> You verify documents → Status: "checks_completed"</li><li><strong>Enrollment:</strong> You enroll student → Status: "enrolled"</li></ol>'
            },
            'help': {
                title: 'Help Topics',
                content: '<p>I can help with:</p><ul><li>Review Applications</li><li>Forward to HOD</li><li>Enroll Student</li><li>Generate Documents</li><li>Notify Applicant</li><li>Mandatory Checks</li><li>Student Account Management</li><li>Application Workflow</li></ul><p>Try asking about any of these topics!</p>'
            }
        },
        'hod': {
            'review': {
                title: 'Review Applications',
                content: '<p><strong>HOD Review Process:</strong></p><ol><li>Applications forwarded to you appear in your dashboard with notifications</li><li>Click on an application to review complete details</li><li>Check all submitted documents (click "View" to see documents)</li><li>Review applicant qualifications and eligibility</li><li>Verify applicant meets program requirements</li><li>Check academic background and prerequisites</li><li>Make your decision: Approve or Reject</li></ol><p><strong>Tip:</strong> You can view all applicant documents directly from the application details page. Click "View" or "Download" links to check each document.</p>'
            },
            'approve': {
                title: 'Approve Application',
                content: '<p><strong>To approve an application:</strong></p><ol><li>Review the application thoroughly</li><li>Verify all documents and qualifications</li><li>Click "Approve" button on application details page</li><li>Add any comments or notes (optional but recommended)</li><li>Submit - Finance and Student Admin Service will be notified automatically</li></ol><p><strong>What happens next:</strong></p><ul><li>Finance receives notification to generate proforma invoice</li><li>Student Admin Service receives notification to generate acceptance letter</li><li>Acceptance letter includes fees and complete requirements list</li><li>Application status changes to "accepted"</li><li>Mandatory checks are created for the applicant</li></ul>'
            },
            'reject': {
                title: 'Reject Application',
                content: '<p><strong>To reject an application:</strong></p><ol><li>Review the application</li><li>Identify shortfalls or reasons for rejection</li><li>Click "Reject" button</li><li><strong>IMPORTANT:</strong> Add detailed comments explaining:<ul><li>Shortfalls in qualifications</li><li>Missing requirements</li><li>Reasons for rejection</li><li>What applicant needs to improve</li></ul></li><li>Submit - Student Admin Service will be notified to generate rejection letter</li></ol><p><strong>Note:</strong> Your comments will be included in the rejection letter sent to the applicant. Be clear and constructive in your feedback.</p><p><strong>What happens:</strong> Application status changes to "ineligible", SAS generates rejection letter with your comments, and applicant is notified.</p>'
            },
            'comment': {
                title: 'Adding Comments',
                content: '<p><strong>When to add comments:</strong></p><ul><li><strong>Approval:</strong> Optional but helpful for record-keeping</li><li><strong>Rejection:</strong> <strong>REQUIRED</strong> - Must explain shortfalls clearly</li></ul><p><strong>Comment Guidelines:</strong></p><ul><li>Be specific about shortfalls</li><li>Mention missing documents or qualifications</li><li>Provide constructive feedback</li><li>Explain what applicant needs to improve</li><li>Be professional and clear</li></ul><p><strong>Note:</strong> Rejection comments are included in the rejection letter sent to applicants.</p>'
            },
            'notification': {
                title: 'Notifications',
                content: '<p><strong>Notification System:</strong></p><ul><li>You receive notifications when applications are forwarded to you</li><li>Click the notification bubble in the header to view</li><li>Notifications show application number and basic details</li><li>Click notification to go directly to application details</li><li>Mark as read when you\'ve reviewed the application</li></ul><p><strong>Notification Types:</strong></p><ul><li>New applications forwarded to you</li><li>Applications requiring your review</li></ul>'
            },
            'document': {
                title: 'Viewing Documents',
                content: '<p><strong>To view applicant documents:</strong></p><ol><li>Open the application details page</li><li>Scroll to "Submitted Documents" section</li><li>Click "View" to see document inline</li><li>Click "Download" to save document</li><li>Verify each document meets requirements</li></ol><p><strong>Document Types:</strong> ID documents, certificates, photos, medical reports, police clearance, etc.</p><p><strong>Tip:</strong> Review all documents carefully before making your decision.</p>'
            },
            'help': {
                title: 'Help Topics',
                content: '<p>I can help with:</p><ul><li>Review Applications</li><li>Approve Application</li><li>Reject Application</li><li>Add Comments</li><li>View Documents</li><li>Notifications</li></ul><p>Try asking about any of these topics!</p>'
            }
        }
    };
    
    // Get response
    function getResponse(message) {
        const lowerMsg = message.toLowerCase();
        const roleResponses = responses[userRole] || responses['admin'];
        
        // Check for exact keyword matches
        for (const [key, response] of Object.entries(roleResponses)) {
            if (lowerMsg.includes(key)) {
                return response;
            }
        }
        
        // Check for variations and synonyms
        if (lowerMsg.includes('workflow') || lowerMsg.includes('process') || lowerMsg.includes('procedure')) {
            if (roleResponses['workflow']) return roleResponses['workflow'];
            if (roleResponses['review']) return roleResponses['review'];
        }
        
        if (lowerMsg.includes('application') || lowerMsg.includes('apply') || lowerMsg.includes('applicant')) {
            if (roleResponses['application']) return roleResponses['application'];
            if (roleResponses['review']) return roleResponses['review'];
        }
        
        if (lowerMsg.includes('invoice') || lowerMsg.includes('bill') || lowerMsg.includes('fee') || lowerMsg.includes('payment')) {
            if (roleResponses['invoice']) return roleResponses['invoice'];
            if (roleResponses['payment']) return roleResponses['payment'];
            if (roleResponses['fee']) return roleResponses['fee'];
        }
        
        if (lowerMsg.includes('enroll') || lowerMsg.includes('enrollment') || lowerMsg.includes('register')) {
            if (roleResponses['enroll']) return roleResponses['enroll'];
        }
        
        if (lowerMsg.includes('document') || lowerMsg.includes('letter') || lowerMsg.includes('acceptance') || lowerMsg.includes('rejection')) {
            if (roleResponses['document']) return roleResponses['document'];
        }
        
        if (lowerMsg.includes('notify') || lowerMsg.includes('notification') || lowerMsg.includes('send') || lowerMsg.includes('email')) {
            if (roleResponses['notify']) return roleResponses['notify'];
            if (roleResponses['notification']) return roleResponses['notification'];
        }
        
        if (lowerMsg.includes('check') || lowerMsg.includes('mandatory') || lowerMsg.includes('requirement') || lowerMsg.includes('verify')) {
            if (roleResponses['check']) return roleResponses['check'];
        }
        
        if (lowerMsg.includes('approve') || lowerMsg.includes('accept') || lowerMsg.includes('approval')) {
            if (roleResponses['approve']) return roleResponses['approve'];
        }
        
        if (lowerMsg.includes('reject') || lowerMsg.includes('rejection') || lowerMsg.includes('deny')) {
            if (roleResponses['reject']) return roleResponses['reject'];
        }
        
        if (lowerMsg.includes('comment') || lowerMsg.includes('note') || lowerMsg.includes('feedback')) {
            if (roleResponses['comment']) return roleResponses['comment'];
        }
        
        if (lowerMsg.includes('student') || lowerMsg.includes('account') || lowerMsg.includes('manage')) {
            if (roleResponses['student']) return roleResponses['student'];
        }
        
        if (lowerMsg.includes('report') || lowerMsg.includes('statistic') || lowerMsg.includes('data')) {
            if (roleResponses['report']) return roleResponses['report'];
        }
        
        if (lowerMsg.includes('dashboard') || lowerMsg.includes('home') || lowerMsg.includes('main')) {
            if (roleResponses['dashboard']) return roleResponses['dashboard'];
        }
        
        if (lowerMsg.includes('monitor') || lowerMsg.includes('track') || lowerMsg.includes('view')) {
            if (roleResponses['monitor']) return roleResponses['monitor'];
            if (roleResponses['workflow']) return roleResponses['workflow'];
        }
        
        // General help queries
        if (lowerMsg.includes('help') || lowerMsg.includes('how') || lowerMsg.includes('what') || lowerMsg.includes('where') || lowerMsg.includes('when') || lowerMsg.includes('why')) {
            return roleResponses['help'] || {title: 'Help', content: '<p>I can help you with system questions. Try asking about specific tasks or click the quick topics above!</p>'};
        }
        
        // Default response with suggestions
        const suggestions = Object.keys(roleResponses).filter(k => k !== 'help').join(', ');
        return {
            title: 'I\'m here to help!',
            content: '<p>I received: "' + message + '"</p><p>Try asking about: ' + suggestions + ', or click the quick topics above!</p><p>You can also ask general questions like "How do I..." or "What is..."</p>'
        };
    }
    
    // Add message
    function addMessage(content, type, title) {
        const container = document.getElementById('chatbot-messages');
        if (!container) return;
        
        const div = document.createElement('div');
        div.className = 'chatbot-message ' + type + '-message';
        
        let html = '<div class="message-content">';
        if (title && type === 'bot') {
            html += '<p><strong>' + title + '</strong></p>';
        }
        html += content;
        html += '</div>';
        
        div.innerHTML = html;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    
    // Send message - Updated to use API
    function sendMessage() {
        const input = document.getElementById('chatbot-input');
        if (!input) return;
        
        const message = input.value.trim();
        if (!message) return;
        
        // Add user message
        addMessage(message, 'user');
        input.value = '';
        
        // Disable input while processing
        input.disabled = true;
        const sendButton = document.querySelector('.chatbot-send');
        if (sendButton) sendButton.disabled = true;
        
        // Show typing indicator
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'chatbot-message bot-message';
        typingIndicator.id = 'typing-indicator';
        typingIndicator.innerHTML = '<div class="message-content"><p>Thinking...</p></div>';
        const messagesContainer = document.getElementById('chatbot-messages');
        messagesContainer.appendChild(typingIndicator);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Call API - Use relative path from current page
        const apiPath = window.location.pathname.includes('/pages/') 
            ? 'api/chatbot_query.php' 
            : 'pages/api/chatbot_query.php';
        fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            // Remove typing indicator
            const indicator = document.getElementById('typing-indicator');
            if (indicator) indicator.remove();
            
            if (data.success) {
                addMessage(data.content, 'bot', data.title);
            } else {
                // Fallback to local response if API fails
                const response = getResponse(message);
                addMessage(response.content, 'bot', response.title);
            }
            
            // Re-enable input
            input.disabled = false;
            if (sendButton) sendButton.disabled = false;
            input.focus();
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Remove typing indicator
            const indicator = document.getElementById('typing-indicator');
            if (indicator) indicator.remove();
            
            // Fallback to local response
            const response = getResponse(message);
            addMessage(response.content, 'bot', response.title);
            
            // Re-enable input
            input.disabled = false;
            if (sendButton) sendButton.disabled = false;
            input.focus();
        });
    }
    
    // Toggle chatbot
    function toggleChatbot() {
        const chatbotWindow = document.getElementById('chatbot-window');
        if (chatbotWindow) {
            const isActive = chatbotWindow.classList.contains('active');
            if (isActive) {
                // Close chatbot
                chatbotWindow.classList.remove('active');
                chatbotWindow.style.setProperty('display', 'none', 'important');
                chatbotWindow.style.setProperty('visibility', 'hidden', 'important');
                chatbotWindow.style.setProperty('opacity', '0', 'important');
                chatbotWindow.style.setProperty('pointer-events', 'none', 'important');
            } else {
                // Open chatbot
                chatbotWindow.classList.add('active');
                chatbotWindow.style.setProperty('display', 'flex', 'important');
                chatbotWindow.style.setProperty('visibility', 'visible', 'important');
                chatbotWindow.style.setProperty('opacity', '1', 'important');
                chatbotWindow.style.setProperty('pointer-events', 'auto', 'important');
                chatbotWindow.style.setProperty('z-index', '99999', 'important');
                
                // Ensure proper positioning on desktop
                if (window.innerWidth >= 768) {
                    chatbotWindow.style.setProperty('position', 'absolute', 'important');
                    chatbotWindow.style.setProperty('bottom', window.innerWidth >= 1440 ? '100px' : (window.innerWidth >= 1200 ? '90px' : '80px'), 'important');
                    chatbotWindow.style.setProperty('right', '0', 'important');
                    chatbotWindow.style.setProperty('left', 'auto', 'important');
                }
                
                // Focus on input when opening
                const input = document.getElementById('chatbot-input');
                if (input) {
                    setTimeout(function() {
                        input.focus();
                    }, 100);
                }
            }
        }
    }
    
    // Make toggleChatbot globally accessible
    window.toggleChatbot = toggleChatbot;
    
    // Clear chat - Reset to initial state
    function clearChat() {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (!messagesContainer) return;
        
        // Clear all messages
        messagesContainer.innerHTML = '';
        
        // Add welcome message back
        const welcomeMsg = '<?php echo htmlspecialchars($user_name); ?>';
        const welcomeDiv = document.createElement('div');
        welcomeDiv.className = 'chatbot-message bot-message';
        welcomeDiv.innerHTML = '<div class="message-content">' +
            '<p>Hello ' + welcomeMsg + '! 👋</p>' +
            '<p>I\'m here to help you learn how to use the system. Try asking:</p>' +
            '<ul style="margin: 10px 0; padding-left: 20px;">' +
            '<li>"How do I review applications?"</li>' +
            '<li>"How to generate an invoice?"</li>' +
            '<li>"What is the workflow?"</li>' +
            '</ul>' +
            '</div>';
        messagesContainer.appendChild(welcomeDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Initialize chatbot - robust initialization for all devices
    function initializeChatbot() {
        const toggle = document.getElementById('chatbot-toggle');
        const close = document.querySelector('.chatbot-close');
        const refresh = document.querySelector('.chatbot-refresh');
        const send = document.querySelector('.chatbot-send');
        const input = document.getElementById('chatbot-input');
        const chatbotWindow = document.getElementById('chatbot-window');
        
        // Check if all required elements exist
        if (!toggle || !chatbotWindow) {
            console.warn('Chatbot elements not found, retrying...');
            return false;
        }
        
        // Ensure chatbot window is initially hidden
        if (chatbotWindow) {
            chatbotWindow.style.setProperty('display', 'none', 'important');
            chatbotWindow.style.setProperty('visibility', 'hidden', 'important');
            chatbotWindow.style.setProperty('opacity', '0', 'important');
            chatbotWindow.style.setProperty('pointer-events', 'none', 'important');
            chatbotWindow.classList.remove('active');
        }
        
        // Remove existing event listeners by cloning toggle button
        if (toggle && toggle.parentNode) {
            const newToggle = toggle.cloneNode(true);
            toggle.parentNode.replaceChild(newToggle, toggle);
            
            // Attach multiple event handlers for reliability
            newToggle.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleChatbot();
                return false;
            };
            
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleChatbot();
                return false;
            }, true);
            
            // Add touch event for mobile devices
            newToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleChatbot();
                return false;
            }, { passive: false });
        }
        
        // Close button
        if (close) {
            close.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleChatbot();
                return false;
            };
            close.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleChatbot();
                return false;
            });
        }
        
        // Refresh button
        if (refresh) {
            refresh.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                clearChat();
                return false;
            };
            refresh.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                clearChat();
                return false;
            });
        }
        
        // Send button
        if (send) {
            send.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                sendMessage();
                return false;
            };
            send.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                sendMessage();
                return false;
            });
        }
        
        // Input field
        if (input) {
            input.onkeypress = function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    sendMessage();
                    return false;
                }
            };
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    sendMessage();
                    return false;
                }
            });
        }
        
        // Quick topics
        const topicsContainer = document.getElementById('chatbot-quick-topics');
        if (topicsContainer) {
            const topics = {
                'admin': ['Workflow Monitoring', 'View Applications', 'View Reports', 'Dashboard Features'],
                'finance': ['Generate Invoice', 'View Payments', 'Red & Green Days', 'Fee Management'],
                'studentservices': ['Review Applications', 'Forward to HOD', 'Enroll Student', 'Generate Documents', 'Notify Applicant', 'Mandatory Checks'],
                'hod': ['Review Applications', 'Approve Application', 'Reject Application', 'View Documents', 'Add Comments']
            };
            
            const roleTopics = topics[userRole] || topics['admin'];
            topicsContainer.innerHTML = roleTopics.map(function(topic) {
                const safeTopic = topic.replace(/'/g, "\\'");
                return '<span class="quick-topic" onclick="if(typeof window.toggleChatbot === \'function\') { document.getElementById(\'chatbot-input\').value=\'' + safeTopic + '\'; document.querySelector(\'.chatbot-send\').click(); }">' + topic + '</span>';
            }).join('');
        }
        
        return true;
    }
    
    // Try multiple initialization methods to ensure it works on all devices
    (function() {
        // Method 1: Try immediate initialization if DOM is ready
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            if (initializeChatbot()) {
                return;
            }
        }
        
        // Method 2: Wait for DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                if (!initializeChatbot()) {
                    // Retry after short delay
                    setTimeout(function() {
                        if (!initializeChatbot()) {
                            // Final retry after longer delay
                            setTimeout(initializeChatbot, 1000);
                        }
                    }, 100);
                }
            });
        } else {
            // DOM already loaded, try immediately
            if (!initializeChatbot()) {
                // Retry after short delay
                setTimeout(function() {
                    if (!initializeChatbot()) {
                        // Final retry after longer delay
                        setTimeout(initializeChatbot, 1000);
                    }
                }, 100);
            }
        }
        
        // Method 3: Fallback initialization after window load
        window.addEventListener('load', function() {
            setTimeout(function() {
                const toggle = document.getElementById('chatbot-toggle');
                if (toggle && !toggle.hasAttribute('data-initialized')) {
                    toggle.setAttribute('data-initialized', 'true');
                    initializeChatbot();
                }
            }, 500);
        });
    })();
})();
</script>

