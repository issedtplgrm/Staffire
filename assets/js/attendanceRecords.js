async function loadAttendanceRecords() {
    //fetch the values from the input fields
    const search     = document.getElementById("ar-search").value;
    const department = document.getElementById("ar-department").value;
    const status      = document.getElementById("ar-status").value;
    const dateFrom    = document.getElementById("ar-date-from").value;
    const dateTo      = document.getElementById("ar-date-to").value;

    //transforms the filters into a query string
    const params = new URLSearchParams({
        search: search,
        department: department,
        status: status,
        date_from: dateFrom,
        date_to: dateTo
    });

    try {
        // get the list of records from the api
        const response = await fetch(`../api/attendanceLists.php?${params}`);
        const rows = await response.json();

        const tableBody = document.getElementById("ar-table-body");

        if (rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6">No attendance records found.</td></tr>`;
            return;
        }

        const rowsHtml = rows.map(function (record) {
            //format the date into a readable format
            const date = new Date(record.login_time).toLocaleDateString("en-US", {
                month: "short", day: "numeric", year: "numeric"
            });
            const timeIn = record.login_time ? formatTime(record.login_time) : "-";
            const timeOut = record.logout_time ? formatTime(record.logout_time) : "-";

            //output on the html
            return `
                <tr>
                    <td>
                        <div class="ar-emp-cell">
                            <div class="ar-avatar"></div>
                            ${record.full_name}
                        </div>
                    </td>
                    <td>${record.department_name ?? "Unassigned"}</td>
                    <td>${date}</td>
                    <td>${timeIn}</td>
                    <td>${timeOut}</td>
                    <td><span class="status-badge status-${record.status}">${record.status}</span></td>
                </tr>
            `;
        });

        tableBody.innerHTML = rowsHtml.join("");

    } catch (error) {
        console.error("Failed to load attendance records:", error);
        document.getElementById("ar-table-body").innerHTML =
            `<tr><td colspan="6" style="color:#ff8686;">Could not load attendance records.</td></tr>`;
    }
}

//format the date into a readable format
function formatTime(datetimeString) {
    const date = new Date(datetimeString.replace(" ", "T"));
    return date.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });
}

function setupFilterListeners() {
    document.getElementById("ar-search").addEventListener("input", loadAttendanceRecords);
    document.getElementById("ar-department").addEventListener("change", loadAttendanceRecords);
    document.getElementById("ar-status").addEventListener("change", loadAttendanceRecords);
    document.getElementById("ar-date-from").addEventListener("change", loadAttendanceRecords);
    document.getElementById("ar-date-to").addEventListener("change", loadAttendanceRecords);
}

document.addEventListener("DOMContentLoaded", function () {
    loadAttendanceRecords();
    setupFilterListeners();
});