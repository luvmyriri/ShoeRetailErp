<div id="addGradeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Salary Grade</h3>
            <span class="close" onclick="closeAddGradeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="gradeDeptId">
            <div class="form-group">
                <label>Grade Name</label>
                <input type="text" id="gradeName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Minimum Hourly Rate</label>
                <input type="number" id="gradeMinRate" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Maximum Hourly Rate</label>
                <input type="number" id="gradeMaxRate" class="form-control" step="0.01" min="0" required>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAddGradeModal()">Cancel</button>
            <button class="btn btn-success" onclick="submitAddGrade()">Add Grade</button>
        </div>
    </div>
</div>