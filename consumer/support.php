<?php
require_once __DIR__ . '/../config.php';

$title = 'Help & Support';
$sub_title = 'Get Assistance and Information';
ob_start();

// Get user information for pre-filling the form
$mysqli = db_connect();
$user_id = $_SESSION['id'];

// Handle support ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    if (!verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid token';
    } else {
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $category = trim($_POST['category']);
        
        if (empty($subject) || empty($message) || empty($category)) {
            $_SESSION['error'] = 'All fields are required';
        } else {
            // Insert support ticket into database
            $stmt = $mysqli->prepare("INSERT INTO consumer_support_tickets (user_id, subject, message, category, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
            $stmt->bind_param("isss", $user_id, $subject, $message, $category);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Your support ticket has been submitted successfully. We will respond to you shortly.';
                // Redirect to avoid form resubmission
                header('Location: ' . view('consumer.support'));
                exit;
            } else {
                $_SESSION['error'] = 'Failed to submit your ticket. Please try again.';
            }
        }
    }
}

// Get user's support tickets
$stmt = $mysqli->prepare("SELECT * FROM consumer_support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets_result = $stmt->get_result();
$tickets = $tickets_result->fetch_all(MYSQLI_ASSOC);
?>

<!-- Display success/error messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row">
    <!-- Quick Help Section -->
    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Quick Help</h4>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="#faq-section" class="btn btn-outline-primary">
                        <i class="mdi mdi-frequently-asked-questions me-1"></i> Frequently Asked Questions
                    </a>
                    <a href="#contact-section" class="btn btn-outline-primary">
                        <i class="mdi mdi-phone me-1"></i> Contact Information
                    </a>
                    <a href="#ticket-form" class="btn btn-outline-primary">
                        <i class="mdi mdi-ticket me-1"></i> Submit a Support Ticket
                    </a>
                </div>
                
                <hr>
                
                <div class="mt-3">
                    <h5>Need Immediate Assistance?</h5>
                    <p class="mb-1"><i class="mdi mdi-phone me-1"></i> (02) 8123-4567</p>
                    <p class="mb-1"><i class="mdi mdi-email me-1"></i> support@poultryv2.com</p>
                    <p class="mb-0"><i class="mdi mdi-clock me-1"></i> Mon-Sat, 8:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Tickets -->
        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Your Recent Tickets</h4>
            </div>
            <div class="card-body p-0">
                <?php if (count($tickets) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($tickets as $ticket): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($ticket['subject']) ?></h5>
                                    <small class="text-muted"><?= !empty($ticket['created_at']) ? date('M d, Y', strtotime($ticket['created_at'])) : 'N/A' ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars(substr($ticket['message'], 0, 100)) . (strlen($ticket['message']) > 100 ? '...' : '') ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Category: <?= htmlspecialchars($ticket['category']) ?></small>
                                    <span class="badge bg-<?= $ticket['status'] === 'open' ? 'warning' : ($ticket['status'] === 'in_progress' ? 'info' : 'success') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center p-3">
                        <p class="mb-0">You haven't submitted any support tickets yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-8 col-lg-7">
        <!-- FAQ Section -->
        <div class="card" id="faq-section">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Frequently Asked Questions</h4>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <!-- Order Related FAQs -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Order Related Questions
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <div class="mb-3">
                                    <h5>How do I track my order?</h5>
                                    <p>You can track your order by going to the "My Orders" section in your dashboard. Click on the specific order to view its current status and tracking information.</p>
                                </div>
                                <div class="mb-3">
                                    <h5>Can I cancel my order?</h5>
                                    <p>Orders can be cancelled within 1 hour of placing them, provided they haven't been processed yet. To cancel, go to "My Orders" and click the cancel button if it's available.</p>
                                </div>
                                <div class="mb-0">
                                    <h5>What if I receive damaged eggs?</h5>
                                    <p>If you receive damaged eggs, please take a photo and contact our support team immediately. We'll arrange for a replacement or refund.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Related FAQs -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Payment Related Questions
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <div class="mb-3">
                                    <h5>What payment methods do you accept?</h5>
                                    <p>We accept credit/debit cards, bank transfers, and cash on delivery. All online payments are processed securely.</p>
                                </div>
                                <div class="mb-3">
                                    <h5>When will I be charged for my order?</h5>
                                    <p>For online payments, you'll be charged immediately upon confirming your order. For cash on delivery, you'll pay when you receive your eggs.</p>
                                </div>
                                <div class="mb-0">
                                    <h5>How do I request a refund?</h5>
                                    <p>To request a refund, please contact our customer support with your order number and reason for the refund. We'll process eligible refunds within 3-5 business days.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Related FAQs -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Delivery Related Questions
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <div class="mb-3">
                                    <h5>How long does delivery take?</h5>
                                    <p>Delivery typically takes 1-2 days depending on your location. You'll receive an estimated delivery time when placing your order.</p>
                                </div>
                                <div class="mb-3">
                                    <h5>Do you deliver to all areas?</h5>
                                    <p>We currently deliver to most urban and suburban areas. You can check if we deliver to your area by entering your address during checkout.</p>
                                </div>
                                <div class="mb-0">
                                    <h5>Can I change my delivery address after placing an order?</h5>
                                    <p>You can change your delivery address within 1 hour of placing your order, provided it hasn't been processed yet. Please contact customer support immediately with your order number and new address.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Related FAQs -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Account Related Questions
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <div class="mb-3">
                                    <h5>How do I update my account information?</h5>
                                    <p>You can update your account information by going to your profile settings. Click on your name in the top right corner and select "Profile" from the dropdown menu.</p>
                                </div>
                                <div class="mb-3">
                                    <h5>How does the loyalty program work?</h5>
                                    <p>Our loyalty program rewards you with points for every purchase. These points can be used for discounts on future orders. Visit the "Loyalty Program" section for more details.</p>
                                </div>
                                <div class="mb-0">
                                    <h5>I forgot my password. How do I reset it?</h5>
                                    <p>Click on the "Forgot Password" link on the login page. You'll receive an email with instructions to reset your password.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="card mt-3" id="contact-section">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Contact Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <h5><i class="mdi mdi-phone-classic me-1"></i> Phone Support</h5>
                            <p class="mb-1">Customer Service: (02) 8123-4567</p>
                            <p class="mb-1">Technical Support: (02) 8123-4568</p>
                            <p class="mb-0 text-muted">Available Mon-Sat, 8:00 AM - 5:00 PM</p>
                        </div>
                        
                        <div class="mb-4">
                            <h5><i class="mdi mdi-email-outline me-1"></i> Email Support</h5>
                            <p class="mb-1">General Inquiries: info@poultryv2.com</p>
                            <p class="mb-1">Customer Support: support@poultryv2.com</p>
                            <p class="mb-0">Order Issues: orders@poultryv2.com</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <h5><i class="mdi mdi-map-marker me-1"></i> Office Address</h5>
                            <p class="mb-0">
                                123 Poultry Lane<br>
                                Egg District, Metro Manila<br>
                                Philippines 1000
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h5><i class="mdi mdi-clock-outline me-1"></i> Business Hours</h5>
                            <p class="mb-1">Monday to Friday: 8:00 AM - 5:00 PM</p>
                            <p class="mb-1">Saturday: 8:00 AM - 12:00 PM</p>
                            <p class="mb-0">Sunday: Closed</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h5><i class="mdi mdi-frequently-asked-questions me-1"></i> Still Have Questions?</h5>
                    <p>If you couldn't find the answer to your question in our FAQ section, please submit a support ticket below and our team will get back to you as soon as possible.</p>
                </div>
            </div>
        </div>
        
        <!-- Support Ticket Form -->
        <div class="card mt-3" id="ticket-form">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Submit a Support Ticket</h4>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <?= csrf_token() ?>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="" selected disabled>Select a category</option>
                            <option value="order">Order Issue</option>
                            <option value="payment">Payment Issue</option>
                            <option value="delivery">Delivery Issue</option>
                            <option value="product">Product Issue</option>
                            <option value="account">Account Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required placeholder="Brief description of your issue">
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Please provide details about your issue"></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" name="submit_ticket" class="btn btn-primary">
                            <i class="mdi mdi-send me-1"></i> Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Smooth scroll to sections when clicking on quick help buttons
    document.addEventListener('DOMContentLoaded', function() {
        const quickHelpLinks = document.querySelectorAll('.card-body .btn-outline-primary');
        
        quickHelpLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>

<?php 
$content = ob_get_clean();
include layouts('consumer.main');
?>