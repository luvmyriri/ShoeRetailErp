<div id="approveBudgetModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Approve Budget</h3>
            <span class="close" onclick="closeApproveBudgetModal()">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="budgetToApproveId">
            <div class="form-group"><label>Store: <span id="budgetStoreName"></span></label></div>
            <div class="form-group"><label>Department: <span id="budgetDept"></span></label></div>
            <div class="form-group"><label>Period: <span id="budgetPeriod"></span></label></div>
            <div class="form-group"><label>Proposed Amount: <span id="budgetProposed"></span></label></div>
            <div class="form-group">
                <label>Approved Amount</label>
                <input type="number" id="approvedAmount" class="form-control" step="0.01" min="0">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeApproveBudgetModal()">Cancel</button>
            <button class="btn btn-success" onclick="submitApproveBudget()">Approve</button>
        </div>
    </div>
</div>