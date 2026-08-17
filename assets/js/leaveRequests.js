async function loadLeaveRequests() {
    // get the buttons and fetch the values of the input and dropdown fiels
    const search = document.getElementById("lr-search").value;
    const department = document.getElementById("lr-department").value;
    const status = document.getElementById("lr-status").value;
    const dateFrom = document.getElementById("lr-date-from").value;
    const dateTo = document.getElementById("lr-date-to").value;

    //transforms the filer into a query string
    const params = new URLSearchParams({
        search: search,
        department: department,
        status: status,
        date_from: dateFrom,
        date_to: dateTo
    });

    try {
        const response = await fetch(`../api/leaveReqLists.php?${params}`);
        const rows = await response.json();

        const tableBody = document.getElementById("lr-table-body");

        if (rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="9">No leave requests found.</td></tr>`;
            return;
        }

        const rowsHtml = rows.map(function (leave) {
            const requestedOn = new Date(leave.created_at).toLocaleDateString("en-US", {
                month: "long", day: "numeric", year: "numeric"
            });

            // create the approve and reject if there is an exisitng pending leave request
            // Once it's already approved/rejected, show disabled buttons instead
            // of letting someone "approve" something twice.
            const isPending = leave.status === "pending";
            const actionButtons = isPending
                ? `
                    <button class="approve-btn" data-id="${leave.leave_request_id}" data-action="approve">Approve</button>
                    <button class="reject-btn" data-id="${leave.leave_request_id}" data-action="reject">Reject</button>
                  `
                : `
                    <button class="approve-btn" disabled>Approve</button>
                    <button class="reject-btn" disabled>Reject</button>
                  `;

            return `
                <tr>
                    <td>
                        <div class="lr-emp-cell">
                            <div class="lr-avatar"></div>
                            ${leave.full_name}
                        </div>
                    </td>
                    <td>${leave.department_name ?? "Unassigned"}</td>
                    <td>${leave.start_date}</td>
                    <td>${leave.end_date}</td>
                    <td>${leave.duration} days</td>
                    <td>${leave.reason ?? capitalize(leave.leave_type)}</td>
                    <td><span class="status-badge status-${leave.status}">${leave.status}</span></td>
                    <td>${requestedOn}</td>
                    <td><div class="lr-actions">${actionButtons}</div></td>
                </tr>
            `;
        });

        tableBody.innerHTML = rowsHtml.join("");

        attachActionButtonListeners();

    } catch (error) {
        console.error("Failed to load leave requests:", error);
        document.getElementById("lr-table-body").innerHTML =
            `<tr><td colspan="9" style="color:#ff8686;">Could not load leave requests.</td></tr>`;
    }
}

function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
}

function attachActionButtonListeners() {
    // get all of buttons except the disabled
    document.querySelectorAll(".approve-btn:not(:disabled), .reject-btn:not(:disabled)")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                handleAction(button.dataset.id, button.dataset.action);
            });
        });
}

async function handleAction(id, action) {
    try {
        const response = await fetch("../api/leaveAction.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({ id: id, action: action })
            
        });

        const result = await response.json();

        if (result.success) {
            // Reload the table so the row shows its new status
            loadLeaveRequests();
        } else {
            alert(result.error || "Something went wrong.");
        }

    } catch (error) {
        console.error("Failed to update leave request:", error);
        alert("Could not reach the server. Please try again.");
    }
}

function setupFilterListeners() {
    // "input" fires on every keystroke
    //shows the corresponding employee everytime na magtype
    document.getElementById("lr-search").addEventListener("input", loadLeaveRequests);

    // "change" fires when a dropdown/date value is picked, not every keystroke.
    document.getElementById("lr-department").addEventListener("change", loadLeaveRequests);
    document.getElementById("lr-status").addEventListener("change", loadLeaveRequests);
    document.getElementById("lr-date-from").addEventListener("change", loadLeaveRequests);
    document.getElementById("lr-date-to").addEventListener("change", loadLeaveRequests);
}

document.addEventListener("DOMContentLoaded", function () {
    loadLeaveRequests();
    setupFilterListeners();
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

