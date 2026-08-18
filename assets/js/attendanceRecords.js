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


function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
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
     loadNotifications();
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
