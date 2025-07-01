<?php
// This file is meant to be included in other PHP files.
// It does not start a session or include db_connect/config as those should be handled by the parent page.

// Check if there are any messages in the session
$has_message = false;
$message_type = '';
$message_text = '';

if (isset($_SESSION['success_message'])) {
    $has_message = true;
    $message_type = 'success';
    $message_text = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
} elseif (isset($_SESSION['error_message'])) {
    $has_message = true;
    $message_type = 'error';
    $message_text = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message after displaying
}
?>

<style>
/* Styles for the Message Modal */
.app-message-modal {
    display: <?php echo $has_message ? 'flex' : 'none'; ?>; /* Show/hide based on PHP session */
    position: fixed;
    z-index: 1001; /* Higher than other modals if any */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5); /* Dark overlay */
    justify-content: center;
    align-items: center;
    padding: 20px;
    box-sizing: border-box;
}

.app-message-content {
    background-color: #fefefe;
    padding: 30px;
    border-radius: 12px;
    max-width: 450px;
    width: 90%;
    text-align: center;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    animation: fadeInScale 0.3s ease-out;
    position: relative; /* For the close button */
}

@keyframes fadeInScale {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.app-message-content h3 {
    margin-top: 0;
    font-size: 24px;
    color: #333;
    margin-bottom: 15px;
}

.app-message-content p {
    font-size: 16px;
    line-height: 1.5;
    color: #555;
    margin-bottom: 25px;
}

.app-message-content .close-btn {
    color: #aaa;
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.app-message-content .close-btn:hover,
.app-message-content .close-btn:focus {
    color: #333;
    text-decoration: none;
}

.app-message-content.success-type {
    border: 2px solid #28a745; /* Green border */
}

.app-message-content.error-type {
    border: 2px solid #dc3545; /* Red border */
}

/* Optional: Add icons based on message type */
.app-message-content .icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.app-message-content.success-type .icon {
    color: #28a745; /* Green */
}

.app-message-content.error-type .icon {
    color: #dc3545; /* Red */
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .app-message-content {
        padding: 20px;
    }
    .app-message-content h3 {
        font-size: 20px;
    }
    .app-message-content p {
        font-size: 14px;
    }
}
</style>

<!-- Message Modal HTML -->
<div id="appMessageModal" class="app-message-modal">
    <div class="app-message-content <?php echo htmlspecialchars($message_type); ?>-type">
        <span class="close-btn" onclick="hideAppMessageModal()">&times;</span>
        <?php if ($message_type === 'success'): ?>
            <div class="icon">&#10004;</div> <!-- Checkmark icon -->
        <?php elseif ($message_type === 'error'): ?>
            <div class="icon">&#10060;</div> <!-- Cross mark icon -->
        <?php endif; ?>
        <h3><?php echo ucfirst(htmlspecialchars($message_type)); ?>!</h3>
        <p><?php echo htmlspecialchars($message_text); ?></p>
    </div>
</div>

<script>
    // JavaScript to hide the modal
    function hideAppMessageModal() {
        document.getElementById('appMessageModal').style.display = 'none';
    }

    // Automatically hide the modal after a few seconds if it's visible
    window.addEventListener('load', function() {
        const modal = document.getElementById('appMessageModal');
        if (modal.style.display === 'flex') {
            setTimeout(function() {
                hideAppMessageModal();
            }, 5000); // Hide after 5 seconds
        }
    });

    // Close the modal if the user clicks outside of it
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('appMessageModal');
        if (event.target === modal) {
            hideAppMessageModal();
        }
    });
</script>
