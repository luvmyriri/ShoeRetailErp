<?php
// Company-level settings for receipts and taxation
if (!defined('COMPANY_NAME')) define('COMPANY_NAME', 'Shoe Retail ERP');
if (!defined('COMPANY_TIN')) define('COMPANY_TIN', '000-000-000-000'); // TODO: set your real VAT Reg TIN
if (!defined('VAT_RATE')) define('VAT_RATE', 0.12); // 12% default
// Public QR service used to render receipt QR (encodes invoice metadata)
if (!defined('RECEIPT_QR_BASE')) define('RECEIPT_QR_BASE', 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=');
?>
