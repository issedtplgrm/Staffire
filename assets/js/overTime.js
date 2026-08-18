  // if admin
    if (typeof userRole === "admin") {
        return;
    }

function checkOvertimePrompt() {
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();

    //condition run exactly at 5:00 PM
    if (hour === 17 && minute === 0) {
        let responded = false;

        if (confirm("It is 5:00 PM. Do you want to file for overtime? Please respond within 30 minutes")) {
            responded = true;
            window.location.href = "leaveRequests.php"; // 
        } else {
            responded = true;
            fetch("../process/logout.php", { method: "POST" })
                .then(() => window.location.reload());
        }

        // If no response within 30 minutes auto logout user
        setTimeout(() => {
            if (!responded) {
                fetch("../process/logout.php", { method: "POST" })
                    .then(() => window.location.reload());
            }
        }, 30 * 60 * 1000); // 30 minutes in ms
    }
}

// Run every minute
setInterval(checkOvertimePrompt, 60000);  