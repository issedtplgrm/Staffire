async function loadNotifications(){
 try{
    //get both leave req and overtime req at the same time
    const [leaveReq, overtimeReq] = await Promise.all([
        fetch("../api/leave_requests.php"),
        fetch("../api/overtime_requests.php"),
    ]);

    const leaveRequest = await leaveReq.json();
    const overtimeRequest = await overtimeReq.json();

    //spread operator to treat each array alone as an individual and not nest it
    const notifItems = [
        ...leaveRequest.map(function (leave){
           return {
          name: leave.full_name,
          detail: `${capitalize(leave.leave_type)} Leave Request`,
          date: leave.created_at,
          link: "leaveRequests.php",
          dotClass: "notif-dot-leave",
        };
    }),

        ...overtimeRequest.map(function (ot){
           return {
          name: ot.full_name,
          detail: `${capitalize(ot.overtime_type)} Overtime Request`,
          date: ot.created_at,
          link: "overtimeRequests.php",
          dotClass: "notif-dot-overtime",
        };
    }),

    ];

     notifItems.sort(function (a, b) {
      return new Date(b.date) - new Date(a.date);
    });
 
    const notifList = document.getElementById("notif-list");
    const badge = document.getElementById("notif-badge");
 
    if (notifItems.length === 0) {
      badge.classList.add("hidden");
      notifList.innerHTML =
        '<p style="color:#9498b8; font-size:13px;">No pending requests.</p>';
      return;
    }
 
    badge.textContent = notifItems.length > 9 ? "9+" : notifItems.length;
    badge.classList.remove("hidden");
 
    const itemsHtml = notifItems.map(function (item) {
      return `
                <a href="${item.link}" class="notif-item">
                    <span class="notif-dot ${item.dotClass}"></span>
                    <div class="notif-item-info">
                        <p class="notif-item-title">${item.name}</p>
                        <p class="notif-item-detail">${item.detail}</p>
                    </div>
                </a>
            `;
    });
 
    notifList.innerHTML = itemsHtml.join("");
  } catch (error) {
    console.error("Failed to load notifications:", error);
    document.getElementById("notif-list").innerHTML =
      "<p style='color:#ff8686; font-size:13px;'>Could not load notifications.</p>";
  }
}



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
                        <a href="leaveRequests.php" class="view-btn">View</a>
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

async function loadOvertimeRequests() {
  try {
    const response = await fetch("../api/overtime_requests.php");
    const overtimeRequests = await response.json();

    const listContainer = document.getElementById("ot-list");
    const counter = document.getElementById("ot-count");

    counter.textContent = overtimeRequests.length;

    if (overtimeRequests.length === 0) {
      listContainer.innerHTML =
        '<p style="color:#9498b8; font-size:13px;">No pending overtime requests.</p>';
      return;
    }

    const itemsHtml = overtimeRequests.map(function (ot) {
      const otDate = formatDateRange(ot.overtime_date, ot.overtime_date);
      const timeRange = `${formatTime(`${ot.overtime_date} ${ot.start_time}`)} - ${formatTime(`${ot.overtime_date} ${ot.end_time}`)}`;

      return `
                <div class="leave-item">
                    <div class="leave-info">
                        <p class="leave-name">${ot.full_name}</p>
                        <p class="leave-dept">${ot.department_name ?? "Unassigned"} • ${capitalize(ot.overtime_type)}</p>
                    </div>
                    <div class="leave-meta">
                        <span class="leave-time">${otDate} • ${timeRange}</span>
                        <a href="overtimeRequests.php" class="view-btn">View</a>
                    </div>
                </div>
            `;
    });

    listContainer.innerHTML = itemsHtml.join("");
  } catch (error) {
    console.error("Failed to load overtime requests:", error);
    document.getElementById("ot-list").innerHTML =
      "<p style='color:#ff8686; font-size:13px;'>Could not load overtime requests.</p>";
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

// switches between the leave-list and ot-list inside the shared dashboard panel
function setupPanelTabs() {
    const tabButtons = document.querySelectorAll(".panel-tab");
    const seeMoreLink = document.getElementById("panel-see-more");

    const seeMoreHrefs = {
        leave: "leaveRequests.php",
        overtime: "overtimeRequests.php",
    };

    tabButtons.forEach(function (tab) {
        tab.addEventListener("click", function () {
            tabButtons.forEach(function (btn) {
                btn.classList.remove("active");
            });
            tab.classList.add("active");

            const selected = tab.dataset.tab;

            document.getElementById("leave-list").classList.toggle("active", selected === "leave");
            document.getElementById("ot-list").classList.toggle("active", selected === "overtime");

            seeMoreLink.href = seeMoreHrefs[selected];
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadNotifications();
    loadLeaveRequests();
    loadOvertimeRequests();
    setupFilterButtons();
    setupPanelTabs();
    loadAttendance('all'); // Load all attendance records by default
});

//show menu when pfp is clicked
const pfpMenu = document.getElementById("pfp-menu");
function showMenu (){
    notifs.classList.remove("open-notif");
    pfpMenu.classList.toggle("open-menu");
}

//show notif when clicked
const notifs = document.getElementById("notifs");
function showNotifs(){
    pfpMenu.classList.remove("open-menu")
    notifs.classList.toggle("open-notif");
}

//manager request
// leave/overtime request modal — tab switching and overtime hour
// calculation are handled by requestForms.js, shared with empDashboard.php
const request = document.getElementById("request");

function showRequest() { request.classList.add("show"); }
function hideRequest() { request.classList.remove("show"); }

// close modal when clicking the backdrop (outside the modal card)
request.onclick = e => {
    if (e.target === request) hideRequest();
};