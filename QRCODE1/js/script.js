// QR Attendance System JavaScript

// Function to generate QR code
function generateQRCode(data, elementId) {
    if (document.getElementById(elementId)) {
        new QRCode(document.getElementById(elementId), {
            text: data,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    }
}

// Function to handle QR code scanning
function initQRScanner() {
    if (document.getElementById('reader')) {
        // Using HTML5 QR Code Scanner library
        const html5QrCode = new Html5Qrcode("reader");
        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            // Handle the scanned code
            document.getElementById('qr-result').value = decodedText;
            document.getElementById('scan-form').submit();
            html5QrCode.stop();
        };
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        // Start scanning
        html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);
    }
}

// Initialize components when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize QR scanner if on scanning page
    if (document.getElementById('reader')) {
        initQRScanner();
    }
    
    // Initialize QR code generation if on attendance session page
    if (document.getElementById('qrcode') && document.getElementById('qr-data')) {
        const qrData = document.getElementById('qr-data').value;
        generateQRCode(qrData, 'qrcode');
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize datepicker if available
    if (jQuery().datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
