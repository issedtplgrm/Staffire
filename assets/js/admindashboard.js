async function loadLeaveRequests() {
  try {
    const response = await fetch("../api/leave_requests.php");
    const leaveRequests = await response.json();
    // console.log(leaveRequests);
    const listContainer = document.getElementById("leave-list");
    const counter = document.getElementById("leave-count");

    counter.textContent = leaveRequests.length;

    if (leaveRequests.length === 0) {
      listContainer.innerHTML =
        '<p style="color:#9498b8; font-size:13px;">No pending leave requests.</p>';
      return;
    }

    const itemsHtml = leaveRequests.map(function (leave) {
      const dateRange = formatDateRange(leave.start_date, leave.end_date);

      return `
                <div class="leave-item">
                    <div class="leave-info">
                        <p class="leave-name">${leave.full_name}</p>
                        <p class="leave-dept">${leave.department_name ?? "Unassigned"} • ${capitalize(leave.leave_type)}</p>
                    </div>
                    <div class="leave-meta">
                        <span class="leave-time">${dateRange}</span>
                        <button class="view-btn" data-id="${leave.leave_request_id}">View</button>
                    </div>
                </div>
            `;
    });

    listContainer.innerHTML = itemsHtml.join("");
  } catch (error) {
    console.error("Failed to load leave requests:", error);
    document.getElementById("leave-list").innerHTML =
      "<p style='color:#ff8686; font-size:13px;'>Could not load leave requests.</p>";
  }
}

// formats the dates in "Jan 1 -3 " format, or "Jan 1" if the start and end dates are the same.
function formatDateRange(startDate, endDate) {
    const options = { month: 'short', day: 'numeric' };
    const start = new Date(startDate).toLocaleDateString("en-US", options);
    const end = new Date(endDate).toLocaleDateString("en-US", options);
    return start === end ? start : `${start} - ${end}`;
}

function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
}

//load attendance data based on the selected filter (all, present, absent)
async function loadAttendance(filter) {
 
    try {
        const response = await fetch(`../api/attendance.php?filter=${filter}`);
        const rows = await response.json();
 
        const tableBody = document.getElementById("attendance-body");
 
        if (rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="4">No employees found.</td></tr>`;
            return;
        }
 
        const rowsHtml = rows.map(function (employee) {
            const timeIn = employee.login_time ? formatTime(employee.login_time) : "-";
            const timeOut = employee.logout_time ? formatTime(employee.logout_time) : "-";
 
            // NOTE: edit emp avatar put placeholder
            return `
                <tr>
                    <td>
                        <div class="emp-cell">
                            <div class="emp-avatar"></div>
                            ${employee.full_name}
                        </div>
                    </td>
                    <td class="dept-muted">${employee.department ?? "Unassigned"}</td>
                    <td>${timeIn}</td>
                    <td>${timeOut}</td>
                </tr>
            `;
        });
 
        tableBody.innerHTML = rowsHtml.join("");
 
    } catch (error) {
        console.error("Failed to load attendance:", error);
        document.getElementById("attendance-body").innerHTML =
            `<tr><td colspan="4" style="color:#ff8686;">Could not load attendance.</td></tr>`;
    }
}
 
// formatting the time to a more readable format
function formatTime(datetimeString) {
    const date = new Date(datetimeString.replace(" ", "T"));
    return date.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });
}
 
// add active class when button is clicked and load the corresponding attendance data
function setupFilterButtons() {
    const buttons = document.querySelectorAll(".filter-btn");
 
    buttons.forEach(function (button) {
        button.addEventListener("click", function () {
            buttons.forEach(function (btn) {
                btn.classList.remove("active");
            });
            button.classList.add("active");
 
            const filter = button.dataset.filter;
            loadAttendance(filter);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadLeaveRequests();
    setupFilterButtons();
    loadAttendance('all'); // Load all attendance records by default
});
