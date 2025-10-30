<div id="salaryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Employee Salary</h3>
            <span class="close" onclick="closeSalaryModal()">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="salaryEmployeeId">
            <div class="form-group"><label>Employee: <span id="salaryEmployeeName"></span></label></div>
            <div class="form-group"><label>Department: <span id="salaryDepartment"></span></label></div>
            <div class="form-group">
                <label>Salary Grade</label>
                <select id="salaryGradeId" class="form-control" onchange="updateSalaryRange()">
                    <option value="">Select Grade</option>
                </select>
            </div>
            <div class="form-group"><label>Grade Range: <span id="gradeRange">₱0 – ₱0</span></label></div>
            <div class="form-group">
                <label>New Hourly Rate</label>
                <input type="number" id="newHourlyRate" class="form-control" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Effective Date</label>
                <input type="date" id="salaryEffectiveDate" class="form-control">
            </div>
            <div class="form-group">
                <label>Notes (Optional)</label>
                <textarea id="salaryNotes" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeSalaryModal()">Cancel</button>
            <button class="btn btn-success" onclick="submitSalaryUpdate()">Update Salary</button>
        </div>
    </div>
</div>