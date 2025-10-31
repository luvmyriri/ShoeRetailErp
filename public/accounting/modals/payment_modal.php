<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="paymentModalTitle">Record Payment</h3>
            <span class="close" onclick="closePaymentModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group"><label>Amount Due: <span id="amountDue">₱0.00</span></label></div>
            <div class="form-group"><label>Already Paid: <span id="alreadyPaid">₱0.00</span></label></div>
            <div class="form-group"><label>Balance: <span id="balance">₱0.00</span></label></div>
            <div class="form-group">
                <label>Payment Amount</label>
                <input type="number" id="paymentAmount" class="form-control" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Payment Date</label>
                <input type="datetime-local" id="paymentDate" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitPayment()">Record Payment</button>
        </div>
    </div>
</div>