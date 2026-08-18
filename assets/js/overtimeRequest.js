// OT

async function loadOvertimeRequests() {
    const search = document.getElementById("ot-search").value;
    const department = document.getElementById("ot-department").value;
    const status = document.getElementById("ot-status").value;
    const dateFrom = document.getElementById("ot-date-from").value;
    const dateTo = document.getElementById("ot-date-to").value;

    const params = new URLSearchParams({
        search: search,
        department: department,
        status: status,
        date_from: dateFrom,
        date_to: dateTo
    });

    try {
        const response = await fetch(`../api/overtime-list.php?${params}`);
        const rows = await response.json();

        const tableBody = document.getElementById("ot-table-body");

        if (rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="12">No overtime requests found.</td></tr>`;
            return;
        }

        const rowsHtml = rows.map(function (ot) {
            const submittedOn = new Date(ot.created_at).toLocaleDateString("en-US", {
                month: "long", day: "numeric", year: "numeric"
            });

            const isPending = ot.status === "pending";
            const actionButtons = isPending
                ? `
                    <button class="approve-btn" data-id="${ot.overtime_request_id}" data-action="approve">Approve</button>
                    <button class="reject-btn" data-id="${ot.overtime_request_id}" data-action="reject">Reject</button>
                  `
                : `
                    <button class="approve-btn" disabled>Approve</button>
                    <button class="reject-btn" disabled>Reject</button>
                  `;

            return `
                <tr>
                    <td>
                        <div class="ot-emp-cell">
                            <div class="ot-avatar"></div>
                            ${ot.full_name}
                        </div>
                    </td>
                    <td>${ot.department_name ?? "Unassigned"}</td>
                    <td>${ot.overtime_date}</td>
                    <td>${ot.start_time}</td>
                    <td>${ot.end_time}</td>
                    <td>${ot.total_hours} hrs</td>
                    <td>${capitalize(ot.overtime_type)}</td>
                    <td>${ot.reason}</td>
                    <td>${ot.work ?? "-"}</td>
                    <td><span class="status-badge status-${ot.status}">${ot.status}</span></td>
                    <td>${submittedOn}</td>
                    <td><div class="ot-actions">${actionButtons}</div></td>
                </tr>
            `;
        });

        tableBody.innerHTML = rowsHtml.join("");
        attachActionButtonListeners();

    } catch (error) {
        console.error("Failed to load overtime requests:", error);
        document.getElementById("ot-table-body").innerHTML =
            `<tr><td colspan="12" style="color:#ff8686;">Could not load overtime requests.</td></tr>`;
    }
}

function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
}

function attachActionButtonListeners() {
    document.querySelectorAll(".approve-btn:not(:disabled), .reject-btn:not(:disabled)")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                handleAction(button.dataset.id, button.dataset.action);
            });
        });
}

async function handleAction(id, action) {
    try {
        const response = await fetch("../api/overtime-action.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({ id: id, action: action })
        });

        const result = await response.json();

        if (result.success) {
            loadOvertimeRequests();
        } else {
            alert(result.error || "Something went wrong.");
        }

    } catch (error) {
        console.error("Failed to update overtime request:", error);
        alert("Could not reach the server. Please try again.");
    }
}

function setupFilterListeners() {
    document.getElementById("ot-search").addEventListener("input", loadOvertimeRequests);
    document.getElementById("ot-department").addEventListener("change", loadOvertimeRequests);
    document.getElementById("ot-status").addEventListener("change", loadOvertimeRequests);
    document.getElementById("ot-date-from").addEventListener("change", loadOvertimeRequests);
    document.getElementById("ot-date-to").addEventListener("change", loadOvertimeRequests);
}

document.addEventListener("DOMContentLoaded", function () {
    loadOvertimeRequests();
    setupFilterListeners();
});


