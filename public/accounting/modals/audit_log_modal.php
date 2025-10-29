<div id="auditLogModal" class="modal">
    <div class="modal-content" style="max-width:900px;">
        <div class="modal-header">
            <h3>Salary Change Audit Log</h3>
            <span class="close" onclick="closeAuditLogModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="filter-bar" style="margin-bottom:15px;">
                <input type="date" id="auditStartDate">
                <input type="date" id="auditEndDate">
                <button class="btn btn-sm btn-primary" onclick="loadSalaryAuditLog()">Filter</button>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Old Rate</th>
                            <th>New Rate</th>
                            <th>Old Grade</th>
                            <th>New Grade</th>
                            <th>Changed By</th>
                        </tr>
                    </thead>
                    <tbody id="auditLogTableBody">
                        <tr><td colspan="7" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>