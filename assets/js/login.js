const grid = document.getElementById("grid");
const TILE_SIZE = 50; 

function createGrid() {
    grid.innerHTML = ""; 

    const columns = Math.ceil(window.innerWidth / TILE_SIZE);
    const rows = Math.ceil(window.innerHeight / TILE_SIZE);
    const totalTiles = columns * rows;

    grid.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;

    for (let i = 0; i < totalTiles; i++) {
        const tile = document.createElement("div");
        tile.classList.add("tile");
        grid.appendChild(tile);
    }
}

createGrid();

let resizeTimeout;
window.addEventListener("resize", () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(createGrid, 200);
});


//recent logins cards
function renderRecentTimeIns(rows) {
    const timeCards= document.querySelector(".time-card");
    if (!timeCards) return;

    if (!rows.length) {
        timeCards.innerHTML = `
            <div class="time-card-item">
                <div class="employee-name">No recent sign-ins yet</div>
            </div>
        `;
        return;
    }

    const cardsHtml = rows.map(function (employee) {
        const timeIn = employee.login_time ? formatTime(employee.login_time) : "-";
        const status = employee.status ? employee.status : "Present";

        return `
            <div class="time-card-item">
                <div class="employee-name">${employee.full_name}</div>
                <div class="time-column">
                    <span class="column-title">Time-in</span>
                    <span class="column-value">${timeIn}</span>
                </div>
                <div class="time-column">
                    <span class="column-title">Status</span>
                    <span class="status">${capitalize(status)}</span>
                </div>
            </div>
        `;
    }).join("");

    timeCards.innerHTML = cardsHtml;
}

function formatTime(datetimeString) {
    const date = new Date(datetimeString.replace(" ", "T"));
    return date.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });
}

function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
}

//get only the present employees
async function loadAttendance(filter) {
    try {
        const response = await fetch(`../api/recentTimeIns.php?filter=${filter}`);
        const rows = await response.json();

        renderRecentTimeIns(rows);
    } catch (error) {
        console.error("Failed to load attendance:", error);
    }
}


document.addEventListener("DOMContentLoaded", () => {
    loadAttendance('present');
});
