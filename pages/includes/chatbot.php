<?php
/**
 * Chatbot Component for User Manual/Help
 * Provides interactive help and guidance for system users
 */

// Get user role for department-specific help
$user_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['name'] ?? 'User';
?>
<div id="chatbot-container" class="chatbot-container">
    <div id="chatbot-toggle" class="chatbot-toggle" style="cursor: pointer;" onclick="if(typeof window.toggleChatbot==='function'){window.toggleChatbot();}else{var w=document.getElementById('chatbot-window');if(w){w.style.display=w.style.display==='flex'?'none':'flex';w.style.visibility=w.style.display==='flex'?'visible':'hidden';w.classList.toggle('active');}}">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span class="chatbot-badge" id="chatbot-badge" style="display: none;">1</span>
    </div>
    
    <div id="chatbot-window" class="chatbot-window" style="display: none;">
        <div class="chatbot-header">
            <div class="chatbot-header-content">
                <h3>💬 System Help Assistant</h3>
                <p class="chatbot-subtitle">Ask me anything about using the system</p>
            </div>
            <button class="chatbot-close" onclick="if(typeof window.toggleChatbot==='function'){window.toggleChatbot();}else{var w=document.getElementById('chatbot-window');if(w){w.style.display='none';w.style.visibility='hidden';w.classList.remove('active');}}">×</button>
        </div>
        
        <div class="chatbot-messages" id="chatbot-messages">
            <div class="chatbot-message bot-message">
                <div class="message-content">
                    <p>Hello <?php echo htmlspecialchars($user_name); ?>! 👋</p>
                    <p>I'm here to help you learn how to use the system. You can ask me questions like:</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>"How do I enroll a student?"</li>
                        <li>"How to forward an application to HOD?"</li>
                        <li>"What is the workflow process?"</li>
                        <li>"How to generate an invoice?"</li>
                    </ul>
                    <p>Or click on the quick help topics below! ⬇️</p>
                </div>
            </div>
        </div>
        
        <div class="chatbot-quick-topics" id="chatbot-quick-topics">
            <!-- Quick help topics will be populated by JavaScript -->
        </div>
        
        <div class="chatbot-input-area">
            <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Type your question here...">
            <button class="chatbot-send" type="button">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
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
}

@media (min-width: 480px) {
    .chatbot-container {
        bottom: 18px;
        right: 18px;
    }
}

@media (min-width: 768px) {
    .chatbot-container {
        bottom: 20px;
        right: 20px;
    }
}

@media (min-width: 1024px) {
    .chatbot-container {
        bottom: 25px;
        right: 25px;
    }
}

.chatbot-toggle {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(29, 78, 137, 0.4);
    transition: all 0.3s ease;
    color: white;
    position: relative;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.chatbot-toggle svg {
    width: 24px;
    height: 24px;
}

@media (min-width: 480px) {
    .chatbot-toggle {
        width: 55px;
        height: 55px;
    }
    .chatbot-toggle svg {
        width: 26px;
        height: 26px;
    }
}

@media (min-width: 768px) {
    .chatbot-toggle {
        width: 60px;
        height: 60px;
    }
    .chatbot-toggle svg {
        width: 28px;
        height: 28px;
    }
}

.chatbot-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(29, 78, 137, 0.6);
}

.chatbot-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    border: 2px solid white;
}

@media (min-width: 768px) {
    .chatbot-badge {
        width: 24px;
        height: 24px;
        font-size: 12px;
    }
}

.chatbot-window {
    position: fixed !important;
    bottom: 70px;
    right: 10px;
    left: 10px;
    width: auto;
    height: calc(100vh - 90px);
    max-height: 600px;
    min-height: 400px;
    background: white !important;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
    display: none !important;
    flex-direction: column;
    overflow: hidden !important;
    z-index: 999999 !important;
    box-sizing: border-box;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

@media (min-width: 480px) {
    .chatbot-window {
        right: 15px;
        left: 15px;
        height: calc(100vh - 95px);
        max-height: 550px;
    }
}

@media (min-width: 768px) {
    .chatbot-window {
        position: fixed !important;
        bottom: 80px;
        right: 0;
        left: auto;
        width: 380px;
        height: 600px;
        max-height: 80vh;
        min-height: 500px;
    }
}

@media (min-width: 1024px) {
    .chatbot-window {
        width: 400px;
        height: 650px;
        max-height: 85vh;
    }
}

.chatbot-window.active {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
    animation: slideUpChatbot 0.3s ease-out;
}

/* Ensure window is visible when active */
#chatbot-window.active {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
    z-index: 999999 !important;
}

@keyframes slideUpChatbot {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    flex-shrink: 0;
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
    overflow-x: hidden;
    padding: 15px;
    background: #f8f9fa;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    min-height: 0;
}

@media (min-width: 480px) {
    .chatbot-messages {
        padding: 18px;
    }
}

@media (min-width: 768px) {
    .chatbot-messages {
        padding: 20px;
    }
}

.chatbot-message {
    margin-bottom: 15px;
    display: flex;
    animation: fadeInMessage 0.3s ease-out;
}

@keyframes fadeInMessage {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    overflow-wrap: break-word;
    hyphens: auto;
}

@media (min-width: 480px) {
    .message-content {
        max-width: 82%;
        padding: 11px 15px;
        font-size: 13.5px;
    }
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
    border-bottom-left-radius: 4px;
}

.user-message .message-content {
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-content p {
    margin: 5px 0;
}

.message-content ul, .message-content ol {
    margin: 10px 0;
    padding-left: 20px;
}

.message-content li {
    margin: 5px 0;
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
    transform: translateY(-2px);
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
    transition: all 0.3s;
    min-width: 0;
    -webkit-appearance: none;
    appearance: none;
    touch-action: manipulation;
}

@media (min-width: 480px) {
    .chatbot-input {
        padding: 11px 13px;
        font-size: 14px;
    }
}

@media (min-width: 768px) {
    .chatbot-input {
        padding: 12px 15px;
        font-size: 15px;
    }
}

.chatbot-input:focus {
    border-color: #1d4e89;
    box-shadow: 0 0 0 3px rgba(29, 78, 137, 0.1);
}

.chatbot-send {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #1d4e89 0%, #163c6a 100%);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    box-shadow: 0 2px 10px rgba(29, 78, 137, 0.3);
    flex-shrink: 0;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.chatbot-send svg {
    width: 18px;
    height: 18px;
}

@media (min-width: 480px) {
    .chatbot-send {
        width: 42px;
        height: 42px;
    }
    .chatbot-send svg {
        width: 19px;
        height: 19px;
    }
}

@media (min-width: 768px) {
    .chatbot-send {
        width: 45px;
        height: 45px;
    }
    .chatbot-send svg {
        width: 20px;
        height: 20px;
    }
}

.chatbot-send:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(29, 78, 137, 0.4);
}

.chatbot-send:active {
    transform: scale(0.95);
}

/* Scrollbar styling */
.chatbot-messages::-webkit-scrollbar {
    width: 6px;
}

.chatbot-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chatbot-messages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.chatbot-messages::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<script>
// Chatbot knowledge base - Department specific
const chatbotKnowledge = {
    'admin': {
        quickTopics: [
            'Workflow Monitoring',
            'View Applications',
            'View Reports',
            'System Overview'
        ],
        responses: {
            'workflow monitoring': {
                title: 'Workflow Monitoring',
                content: `<p><strong>As an Administrator, you can monitor the entire workflow:</strong></p>
                <ul>
                    <li><strong>Workflow Monitor:</strong> View overall application statistics, status breakdown, and department-wise distribution</li>
                    <li><strong>View Applications:</strong> You can view all applications but cannot perform actions (read-only mode)</li>
                    <li><strong>Track Progress:</strong> Monitor how applications move through different departments</li>
                    <li><strong>Financial Overview:</strong> View invoice statistics and payment information</li>
                </ul>
                <p><strong>Note:</strong> Administration role is read-only. Only Student Admin Service can perform actions on applications.</p>`
            },
            'view applications': {
                title: 'Viewing Applications',
                content: `<p><strong>To view applications:</strong></p>
                <ol>
                    <li>Go to the Workflow Monitor from your dashboard</li>
                    <li>Click on any application to view details</li>
                    <li>You can see the complete workflow history and status</li>
                </ol>
                <p><strong>Remember:</strong> You can view but not modify applications. This is a monitoring role only.</p>`
            },
            'reports': {
                title: 'Viewing Reports',
                content: `<p><strong>Available Reports:</strong></p>
                <ul>
                    <li>Application Statistics</li>
                    <li>Student Enrollment Reports</li>
                    <li>Financial Reports</li>
                    <li>Workflow Activity Logs</li>
                </ul>
                <p>Access reports from the Reports section in your dashboard.</p>`
            }
        }
    },
    'finance': {
        quickTopics: [
            'Generate Invoice',
            'View Payments',
            'Red & Green Days',
            'Financial Reports'
        ],
        responses: {
            'generate invoice': {
                title: 'Generating Invoices',
                content: `<p><strong>How to generate an invoice:</strong></p>
                <ol>
                    <li>When an application is approved by HOD, you'll receive a notification</li>
                    <li>Go to the application details page</li>
                    <li>Click "Print Proforma Invoice" to generate the invoice</li>
                    <li>The invoice will automatically include fees based on the program</li>
                </ol>
                <p><strong>Note:</strong> Fees are automatically calculated from the application's program information.</p>`
            },
            'red green days': {
                title: 'Red & Green Days Monitor',
                content: `<p><strong>Red & Green Days System:</strong></p>
                <ul>
                    <li><strong>GREEN (0-14 days):</strong> Student is in good standing</li>
                    <li><strong>YELLOW (15-30 days):</strong> Warning - fees overdue</li>
                    <li><strong>RED (30+ days):</strong> Critical - immediate action needed</li>
                </ul>
                <p>Access this from your dashboard to monitor student fee statuses.</p>`
            },
            'view payments': {
                title: 'Viewing Payments',
                content: `<p><strong>To view payments:</strong></p>
                <ol>
                    <li>Go to your Finance dashboard</li>
                    <li>Check the "Payments" section</li>
                    <li>View payment history and outstanding balances</li>
                </ol>`
            },
            'financial reports': {
                title: 'Financial Reports',
                content: `<p><strong>Financial Reports Available:</strong></p>
                <ul>
                    <li>Invoice Statistics</li>
                    <li>Payment Tracking</li>
                    <li>Outstanding Balances</li>
                    <li>Fee Collection Reports</li>
                </ul>
                <p>Access reports from your dashboard or the Reports section.</p>`
            }
    },
    'studentservices': {
        quickTopics: [
            'Review Applications',
            'Forward to HOD',
            'Enroll Student',
            'Generate Documents'
        ],
        responses: {
            'review applications': {
                title: 'Reviewing Applications',
                content: `<p><strong>Application Review Process:</strong></p>
                <ol>
                    <li>New applications appear in your dashboard</li>
                    <li>Click on an application to review details</li>
                    <li>Check all submitted documents</li>
                    <li>Verify applicant meets requirements</li>
                    <li>Forward to appropriate HOD for final decision</li>
                </ol>
                <p><strong>Tip:</strong> You can view and verify all applicant documents from the application details page.</p>`
            },
            'forward to hod': {
                title: 'Forwarding to HOD',
                content: `<p><strong>To forward an application to HOD:</strong></p>
                <ol>
                    <li>Open the application details page</li>
                    <li>Click "Forward to HOD" button</li>
                    <li>Select the appropriate HOD based on program</li>
                    <li>Add any notes if needed</li>
                    <li>Submit - the HOD will be notified</li>
                </ol>
                <p><strong>Note:</strong> HOD selection is based on the program the applicant is interested in.</p>`
            },
            'enroll student': {
                title: 'Enrolling a Student',
                content: `<p><strong>Student Enrollment Process:</strong></p>
                <ol>
                    <li>Application must be accepted by HOD</li>
                    <li>All mandatory checks must be completed</li>
                    <li>Go to application details</li>
                    <li>Click "Enroll Student" button</li>
                    <li>Student account will be automatically created</li>
                    <li>Login credentials will be displayed</li>
                </ol>
                <p><strong>Important:</strong> Make sure to save the student's login credentials after enrollment!</p>`
            },
            'generate documents': {
                title: 'Generating Documents',
                content: `<p><strong>Available Documents:</strong></p>
                <ul>
                    <li><strong>Acceptance Letter:</strong> Generated after HOD approval, includes fees and requirements</li>
                    <li><strong>Proforma Invoice:</strong> Generated by Finance, can be printed from application details</li>
                    <li><strong>Rejection Letter:</strong> Generated when application is rejected, includes shortfall details</li>
                </ul>
                <p>Access these from the application details page after HOD decision.</p>`
            }
        }
    },
    'hod': {
        quickTopics: [
            'Review Applications',
            'Approve Application',
            'Reject Application',
            'Add Comments'
        ],
        responses: {
            'review applications': {
                title: 'Reviewing Applications',
                content: `<p><strong>HOD Review Process:</strong></p>
                <ol>
                    <li>Applications forwarded to you appear in your dashboard</li>
                    <li>Click on an application to review</li>
                    <li>Check all submitted documents</li>
                    <li>Review applicant qualifications</li>
                    <li>Make your decision: Approve or Reject</li>
                </ol>
                <p><strong>Tip:</strong> You can view all applicant documents directly from the application details page.</p>`
            },
            'approve application': {
                title: 'Approving Applications',
                content: `<p><strong>To approve an application:</strong></p>
                <ol>
                    <li>Review the application thoroughly</li>
                    <li>Click "Approve" button on application details page</li>
                    <li>Add any comments or notes</li>
                    <li>Submit - Finance and Student Admin Service will be notified</li>
                </ol>
                <p><strong>What happens next:</strong> Finance will generate invoice, and SAS will generate acceptance letter with requirements list.</p>`
            },
            'reject application': {
                title: 'Rejecting Applications',
                content: `<p><strong>To reject an application:</strong></p>
                <ol>
                    <li>Review the application</li>
                    <li>Click "Reject" button</li>
                    <li><strong>Important:</strong> Add detailed comments explaining shortfalls or reasons for rejection</li>
                    <li>Submit - Student Admin Service will be notified to generate rejection letter</li>
                </ol>
                <p><strong>Note:</strong> Your comments will be included in the rejection letter sent to the applicant.</p>`
            }
        }
    }
};

// Get current user role - Make globally accessible
window.currentUserRole = '<?php echo $user_role; ?>';
window.currentKnowledge = chatbotKnowledge[window.currentUserRole] || chatbotKnowledge['admin'];

// Also keep local const for backward compatibility
const currentUserRole = window.currentUserRole;
const currentKnowledge = window.currentKnowledge;

// Initialize quick topics
function initQuickTopics() {
    const topicsContainer = document.getElementById('chatbot-quick-topics');
    if (!topicsContainer) return;
    
    const userRole = '<?php echo $user_role; ?>';
    let topics = [];
    
    if (userRole === 'admin') {
        topics = ['Workflow Monitoring', 'View Applications', 'View Reports', 'System Overview'];
    } else if (userRole === 'finance') {
        topics = ['Generate Invoice', 'View Payments', 'Red & Green Days', 'Financial Reports'];
    } else if (userRole === 'studentservices') {
        topics = ['Review Applications', 'Forward to HOD', 'Enroll Student', 'Generate Documents'];
    } else if (userRole === 'hod') {
        topics = ['Review Applications', 'Approve Application', 'Reject Application', 'Add Comments'];
    } else {
        topics = ['Help', 'Workflow', 'Applications'];
    }
    
    topicsContainer.innerHTML = topics.map(topic => 
        `<span class="quick-topic" data-topic="${topic.replace(/'/g, "&apos;")}">${topic}</span>`
    ).join('');
    
    // Add event listeners to quick topics
    const quickTopics = topicsContainer.querySelectorAll('.quick-topic');
    quickTopics.forEach(topic => {
        topic.addEventListener('click', function() {
            const topicText = this.getAttribute('data-topic');
            if (topicText) {
                window.askQuickTopic(topicText);
            }
        });
    });
}

// Toggle chatbot window - Define early and make globally accessible
(function() {
    // Define function immediately in IIFE to ensure it's available
    window.toggleChatbot = function() {
        const chatbotWindow = document.getElementById('chatbot-window');
        
        if (!chatbotWindow) {
            console.error('Chatbot window element not found!');
            return false;
        }
        
        // Check current state using computed styles
        const computedStyle = window.getComputedStyle(chatbotWindow);
        const currentDisplay = computedStyle.display;
        const currentVisibility = computedStyle.visibility;
        const hasActiveClass = chatbotWindow.classList.contains('active');
        
        // Determine if window is currently open
        const isCurrentlyOpen = currentDisplay === 'flex' || hasActiveClass || currentVisibility === 'visible';
        
        if (isCurrentlyOpen) {
            // Close chatbot
            chatbotWindow.classList.remove('active');
            chatbotWindow.style.display = 'none';
            chatbotWindow.style.visibility = 'hidden';
            chatbotWindow.style.opacity = '0';
        } else {
            // Open chatbot
            chatbotWindow.classList.add('active');
            chatbotWindow.style.display = 'flex';
            chatbotWindow.style.visibility = 'visible';
            chatbotWindow.style.opacity = '1';
            chatbotWindow.style.zIndex = '999999';
            chatbotWindow.style.position = 'fixed';
            chatbotWindow.style.pointerEvents = 'auto';
            // Force remove inline style that might hide it
            chatbotWindow.style.removeProperty('display');
            chatbotWindow.style.removeProperty('visibility');
            chatbotWindow.style.removeProperty('opacity');
            // Re-apply with !important equivalent
            chatbotWindow.setAttribute('style', chatbotWindow.getAttribute('style') + '; display: flex !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; z-index: 999999 !important;');
        }
        
        // Hide badge when opened
        const badge = document.getElementById('chatbot-badge');
        if (badge) {
            if (isCurrentlyOpen) {
                // Show badge when closing (if needed)
            } else {
                // Hide badge when opening
                badge.style.display = 'none';
            }
        }
        
        return true;
    };
})();

// Handle quick topic click - Make it globally accessible
window.askQuickTopic = function(topic) {
    const input = document.getElementById('chatbot-input');
    if (input) {
        input.value = topic;
        window.sendChatbotMessage();
    }
}

// Handle Enter key press - Make it globally accessible
window.handleChatbotKeyPress = function(event) {
    if (event.key === 'Enter') {
        sendChatbotMessage();
    }
}

// Simple function to add message to chat - MUST be defined before sendChatbotMessage
function addMessageToChat(content, type, title = null) {
    const messagesContainer = document.getElementById('chatbot-messages');
    if (!messagesContainer) {
        console.error('Messages container not found!');
        return;
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message ${type}-message`;
    
    let messageHTML = '<div class="message-content">';
    if (title && type === 'bot') {
        messageHTML += `<p><strong>${title}</strong></p>`;
    }
    messageHTML += content;
    messageHTML += '</div>';
    
    messageDiv.innerHTML = messageHTML;
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Send message - Make it globally accessible
window.sendChatbotMessage = function() {
    console.log('sendChatbotMessage called');
    const input = document.getElementById('chatbot-input');
    if (!input) {
        console.error('Input field not found!');
        return;
    }
    
    const message = input.value.trim();
    if (!message) {
        console.log('Empty message');
        return;
    }
    
    console.log('Message to send:', message);
    
    // Add user message
    addMessageToChat(message, 'user');
    input.value = '';
    
    // Get bot response immediately
    setTimeout(() => {
        try {
            const response = getSimpleResponse(message);
            console.log('Got response:', response);
            if (response && response.content) {
                addMessageToChat(response.content, 'bot', response.title);
            } else {
                addMessageToChat('<p>Sorry, I encountered an error. Please try again.</p>', 'bot', 'Error');
            }
        } catch (error) {
            console.error('Error in getSimpleResponse:', error);
            addMessageToChat('<p>Sorry, I encountered an error. Please try again.</p>', 'bot', 'Error');
        }
    }, 300);
}


// Simple response function - direct lookup
function getSimpleResponse(message) {
    const lowerMsg = message.toLowerCase();
    const userRole = '<?php echo $user_role; ?>';
    
    // Get responses based on role
    let responses = {};
    
    if (userRole === 'admin') {
        responses = {
            'workflow': {title: 'Workflow Monitoring', content: '<p><strong>As Administrator:</strong></p><ul><li>View overall application statistics</li><li>Monitor workflow progress</li><li>Track department activities</li><li>View financial overview</li></ul><p><strong>Note:</strong> You have read-only access. Only Student Admin Service can perform actions.</p>'},
            'application': {title: 'Viewing Applications', content: '<p><strong>To view applications:</strong></p><ol><li>Go to Workflow Monitor from dashboard</li><li>Click any application to view details</li><li>See complete workflow history</li></ol><p>You can view but not modify applications.</p>'},
            'report': {title: 'Reports', content: '<p><strong>Available Reports:</strong></p><ul><li>Application Statistics</li><li>Student Enrollment Reports</li><li>Financial Reports</li><li>Workflow Activity Logs</li></ul>'},
            'help': {title: 'Help Topics', content: '<p>I can help with:</p><ul><li>Workflow Monitoring</li><li>View Applications</li><li>View Reports</li><li>System Overview</li></ul>'}
        };
    } else if (userRole === 'finance') {
        responses = {
            'invoice': {title: 'Generate Invoice', content: '<p><strong>How to generate invoice:</strong></p><ol><li>When application is approved by HOD, you\'ll receive notification</li><li>Go to application details page</li><li>Click "Print Proforma Invoice"</li><li>Invoice includes fees automatically</li></ol>'},
            'red': {title: 'Red & Green Days', content: '<p><strong>Alert System:</strong></p><ul><li><strong>GREEN (0-14 days):</strong> Good standing</li><li><strong>YELLOW (15-30 days):</strong> Warning</li><li><strong>RED (30+ days):</strong> Critical</li></ul><p>Access from dashboard to monitor fee statuses.</p>'},
            'green': {title: 'Red & Green Days', content: '<p><strong>Alert System:</strong></p><ul><li><strong>GREEN (0-14 days):</strong> Good standing</li><li><strong>YELLOW (15-30 days):</strong> Warning</li><li><strong>RED (30+ days):</strong> Critical</li></ul><p>Access from dashboard to monitor fee statuses.</p>'},
            'payment': {title: 'View Payments', content: '<p><strong>To view payments:</strong></p><ol><li>Go to Finance dashboard</li><li>Check "Payments" section</li><li>View payment history and balances</li></ol>'},
            'help': {title: 'Help Topics', content: '<p>I can help with:</p><ul><li>Generate Invoice</li><li>View Payments</li><li>Red & Green Days</li><li>Financial Reports</li></ul>'}
        };
    } else if (userRole === 'studentservices') {
        responses = {
            'review': {title: 'Review Applications', content: '<p><strong>Application Review:</strong></p><ol><li>New applications appear in dashboard</li><li>Click application to review</li><li>Check submitted documents</li><li>Verify requirements</li><li>Forward to appropriate HOD</li></ol>'},
            'forward': {title: 'Forward to HOD', content: '<p><strong>To forward application:</strong></p><ol><li>Open application details</li><li>Click "Forward to HOD"</li><li>Select appropriate HOD</li><li>Add notes if needed</li><li>Submit - HOD will be notified</li></ol>'},
            'enroll': {title: 'Enroll Student', content: '<p><strong>Enrollment Process:</strong></p><ol><li>Application must be accepted by HOD</li><li>All mandatory checks completed</li><li>Go to application details</li><li>Click "Enroll Student"</li><li>Student account created automatically</li><li>Save login credentials</li></ol>'},
            'document': {title: 'Generate Documents', content: '<p><strong>Available Documents:</strong></p><ul><li><strong>Acceptance Letter:</strong> After HOD approval, includes fees and requirements</li><li><strong>Proforma Invoice:</strong> Generated by Finance</li><li><strong>Rejection Letter:</strong> When rejected, includes shortfall details</li></ul>'},
            'help': {title: 'Help Topics', content: '<p>I can help with:</p><ul><li>Review Applications</li><li>Forward to HOD</li><li>Enroll Student</li><li>Generate Documents</li></ul>'}
        };
    } else if (userRole === 'hod') {
        responses = {
            'review': {title: 'Review Applications', content: '<p><strong>HOD Review Process:</strong></p><ol><li>Applications forwarded to you appear in dashboard</li><li>Click application to review</li><li>Check all submitted documents</li><li>Review qualifications</li><li>Make decision: Approve or Reject</li></ol>'},
            'approve': {title: 'Approve Application', content: '<p><strong>To approve:</strong></p><ol><li>Review application thoroughly</li><li>Click "Approve" button</li><li>Add comments if needed</li><li>Submit - Finance and SAS will be notified</li></ol><p><strong>What happens:</strong> Finance generates invoice, SAS generates acceptance letter.</p>'},
            'reject': {title: 'Reject Application', content: '<p><strong>To reject:</strong></p><ol><li>Review application</li><li>Click "Reject" button</li><li><strong>Important:</strong> Add detailed comments explaining shortfalls</li><li>Submit - SAS will be notified to generate rejection letter</li></ol>'},
            'help': {title: 'Help Topics', content: '<p>I can help with:</p><ul><li>Review Applications</li><li>Approve Application</li><li>Reject Application</li><li>Add Comments</li></ul>'}
        };
    }
    
    // Check for matches
    for (const [key, response] of Object.entries(responses)) {
        if (lowerMsg.includes(key)) {
            return response;
        }
    }
    
    // Check for common keywords
    if (lowerMsg.includes('help') || lowerMsg.includes('how') || lowerMsg.includes('what')) {
        return responses['help'] || {title: 'Help', content: '<p>I can help you with system questions. Try asking about specific tasks or click the quick topics above!</p>'};
    }
    
    if (lowerMsg.includes('workflow') || lowerMsg.includes('process')) {
        return {title: 'Application Workflow', content: '<p><strong>Workflow Process:</strong></p><ol><li>Applicant submits application</li><li>Student Admin Service reviews</li><li>Forwarded to HOD</li><li>HOD approves or rejects</li><li>If approved: Finance generates invoice, SAS generates acceptance letter</li><li>If rejected: SAS generates rejection letter</li><li>After checks: Student can be enrolled</li></ol>'};
    }
    
    // Default response - ALWAYS return something
    return {
        title: 'I\'m here to help!',
        content: `<p>I received: "${message}"</p><p>Try asking about:</p><ul><li>How to review applications</li><li>How to generate invoices</li><li>Application workflow</li><li>Or click the quick topics above!</li></ul>`
    };
}

// Get bot response - Make it globally accessible
window.getBotResponse = function(message) {
    console.log('getBotResponse called with:', message);
    
    // Use window.currentKnowledge to ensure it's accessible
    const knowledge = window.currentKnowledge || (typeof currentKnowledge !== 'undefined' ? currentKnowledge : null);
    console.log('Using knowledge:', knowledge);
    console.log('Knowledge responses:', knowledge ? (knowledge.responses ? Object.keys(knowledge.responses) : 'no responses') : 'no knowledge');
    
    if (!knowledge || !knowledge.responses) {
        console.error('currentKnowledge or responses not found!');
        // Return a helpful default response
        return {
            title: 'I\'m here to help!',
            content: `<p>I received your message: "${message}".</p>
            <p>I'm still learning, but I can help you with questions about:</p>
            <ul>
                <li>How to use the system</li>
                <li>Application workflow</li>
                <li>Department-specific tasks</li>
            </ul>
            <p>Try asking something like "How do I review applications?" or "What is the workflow?"</p>`
        };
    }
    
    const lowerMessage = message.toLowerCase();
    console.log('Searching for matches in:', Object.keys(knowledge.responses));
    
    // Check for exact matches
    for (const [key, response] of Object.entries(knowledge.responses)) {
        if (lowerMessage.includes(key)) {
            console.log('Found match for key:', key);
            return {
                title: response.title,
                content: response.content
            };
        }
    }
    
    // Check for common keywords
    if (lowerMessage.includes('help') || lowerMessage.includes('how') || lowerMessage.includes('what')) {
        const topics = knowledge.quickTopics ? knowledge.quickTopics.map(topic => `<li>${topic}</li>`).join('') : '';
        return {
            title: 'How can I help?',
            content: `<p>I can help you with:</p>
            <ul>
                ${topics}
            </ul>
            <p>Try asking about any of these topics, or click on the quick topics above!</p>`
        };
    }
    
    if (lowerMessage.includes('workflow') || lowerMessage.includes('process')) {
        return {
            title: 'Application Workflow',
            content: `<p><strong>The application workflow process:</strong></p>
            <ol>
                <li><strong>Submission:</strong> Applicant submits application</li>
                <li><strong>Review:</strong> Student Admin Service reviews application</li>
                <li><strong>Forward to HOD:</strong> Application forwarded to appropriate HOD</li>
                <li><strong>HOD Decision:</strong> HOD approves or rejects</li>
                <li><strong>If Approved:</strong> Finance generates invoice, SAS generates acceptance letter</li>
                <li><strong>If Rejected:</strong> SAS generates rejection letter with shortfall details</li>
                <li><strong>Enrollment:</strong> After checks completed, student can be enrolled</li>
            </ol>`
        };
    }
    
    if (lowerMessage.includes('notification') || lowerMessage.includes('alert')) {
        return {
            title: 'Notifications',
            content: `<p><strong>Notification System:</strong></p>
            <ul>
                <li>You'll receive notifications when actions are required</li>
                <li>Click the notification bubble in the header to view</li>
                <li>Notifications are department-specific</li>
                <li>Mark as read when you've completed the action</li>
            </ul>`
        };
    }
    
    // Default response
    const topics = knowledge.quickTopics ? knowledge.quickTopics.map(topic => `<li>${topic}</li>`).join('') : '';
    return {
        title: 'I\'m here to help!',
        content: `<p>I understand you're asking about "${message}".</p>
        <p>Try asking about:</p>
        <ul>
            ${topics}
        </ul>
        <p>Or be more specific with your question! You can also click on the quick topics above for instant help.</p>`
    };
}

// Keep addMessage for backward compatibility
window.addMessage = function(content, type, title = null) {
    addMessageToChat(content, type, title);
}

// Initialize on page load - try multiple methods to ensure it runs
function initChatbot() {
    initQuickTopics();
    
    // Add event listeners
    const toggleButton = document.getElementById('chatbot-toggle');
    const closeButton = document.querySelector('.chatbot-close');
    const sendButton = document.querySelector('.chatbot-send');
    const inputField = document.getElementById('chatbot-input');
    
    // Toggle button click handler
    if (toggleButton) {
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.toggleChatbot();
        });
    }
    
    // Close button click handler
    if (closeButton) {
        closeButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.toggleChatbot();
        });
    }
    
    // Send button click handler
    if (sendButton) {
        sendButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.sendChatbotMessage === 'function') {
                window.sendChatbotMessage();
            }
        });
    }
    
    // Input field Enter key handler
    if (inputField) {
        inputField.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                if (typeof window.sendChatbotMessage === 'function') {
                    window.sendChatbotMessage();
                }
            }
        });
    }
}

// Initialize chatbot - multiple methods to ensure it works
(function() {
    function initializeChatbot() {
        if (document.getElementById('chatbot-toggle') && document.getElementById('chatbot-window')) {
            initChatbot();
            return true;
        }
        return false;
    }
    
    // Try immediate initialization
    if (initializeChatbot()) {
        return;
    }
    
    // Try on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeChatbot();
        });
    } else {
        // DOM already loaded, try after short delay
        setTimeout(function() {
            if (!initializeChatbot()) {
                // Last attempt after longer delay
                setTimeout(initializeChatbot, 1000);
            }
        }, 100);
    }
})();
</script>

