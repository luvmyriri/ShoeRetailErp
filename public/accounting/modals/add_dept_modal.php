<div id="addDeptModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Department</h3>
            <span class="close" onclick="closeAddDeptModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Department Name</label>
                <input type="text" id="deptName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea id="deptDescription" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Base Hourly Rate</label>
                <input type="number" id="deptBaseRate" class="form-control" step="0.01" min="0" required>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAddDeptModal()">Cancel</button>
            <button class="btn btn-success" onclick="submitAddDepartment()">Add Department</button>
        </div>
    </div>
</div>