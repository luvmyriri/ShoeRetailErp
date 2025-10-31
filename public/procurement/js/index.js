document.addEventListener('DOMContentLoaded', () => {
    // ==============================
    // TAB NAVIGATION FUNCTIONALITY
    // ==============================
    document.querySelectorAll('.nav-link').forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.tab-pane').forEach(x => x.style.display = 'none');
            document.getElementById(this.dataset.tab).style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
        });
    });


    // ==============================
    // SUPPLIER STATUS FILTER ✅
    // ==============================
    const supplierFilter = document.getElementById("supplierFilter");
    const supplierRows = document.querySelectorAll("#supplierBody tr");

    if (supplierFilter) {
        supplierFilter.addEventListener("change", () => {
            const filterValue = supplierFilter.value;

            supplierRows.forEach(row => {
                const statusBadge = row.querySelector("td:nth-child(5) .badge");
                const status = statusBadge ? statusBadge.textContent.trim() : "";

                // ✅ Filter Logic
                if (filterValue === "All" || filterValue === status) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    }


    // ==============================
    // TRANSACTION HISTORY DIVIDERS
    // ==============================
    const rows = document.querySelectorAll('.transaction-row');
    const todayContainer = document.getElementById('today-container');
    const yesterdayContainer = document.getElementById('yesterday-container');
    const pastDaysContainer = document.getElementById('pastdays-container');

    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const yesterdayStr = yesterday.toISOString().split('T')[0];

    rows.forEach(row => {
        const arrivalDate = row.getAttribute('data-arrival');
        if (!arrivalDate) return;

        if (arrivalDate === today) {
            todayContainer.appendChild(row);
        } else if (arrivalDate === yesterdayStr) {
            yesterdayContainer.appendChild(row);
        } else {
            pastDaysContainer.appendChild(row);
        }
    });

    if (todayContainer.children.length === 0) document.getElementById('today-section').style.display = 'none';
    if (yesterdayContainer.children.length === 0) document.getElementById('yesterday-section').style.display = 'none';
    if (pastDaysContainer.children.length === 0) document.getElementById('pastdays-section').style.display = 'none';


    // ==============================
    // SEARCH FUNCTIONALITY
    // ==============================
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            const query = searchInput.value.toLowerCase();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});
