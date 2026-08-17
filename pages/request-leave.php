<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staffire Requests</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/empDashboard.css">
</head>
<body>
  <div class="main-grid">

    <!-- Request forms -->
    <div class="panel">
      <div class="panel-header"><h3>File Request Forms</h3></div>

      <!-- Tabs -->
      <div class="form-tabs">
        <button type="button" id="tab-leave" class="form-tab-btn active">Leave Request</button>
        <button type="button" id="tab-overtime" class="form-tab-btn">Overtime Request</button>
      </div>

      <!-- Leave Request Form -->
      <form id="leave-form-section" class="request-form" 
            action="../process/submit-request.php" method="POST" enctype="multipart/form-data">

        <!-- Hidden fields -->
        <input type="hidden" name="request_type" value="leave">
        <input type="hidden" name="submitted_by_role" value="<?php echo $_SESSION['role']; ?>">

        <label for="leave_type">Leave Type</label>
        <select name="leave_type" id="leave_type" required>
          <option value="" disabled selected>Select leave type</option>
          <option value="vacation">Vacation Leave</option>
          <option value="sick">Sick Leave</option>
          <option value="emergency">Emergency Leave</option>
          <option value="others">Others</option>
        </select>

        <div class="date-row">
          <div class="date-field">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" required>
          </div>
          <div class="date-field">
            <label for="end_date">End Date</label>
            <input type="date" name="end_date" id="end_date" required>
          </div>
        </div>

        <label for="reason">Reason</label>
        <textarea name="reason" id="reason" maxlength="300" placeholder="Enter Reason for Leave" required></textarea>

        <label for="attachment-input">Attachment (Optional)</label>
        <div class="file-input-row">
          <label class="file-choose-btn" for="attachment-input">Choose File</label>
          <span id="attachment-filename">No file chosen</span>
          <input type="file" name="attachment" id="attachment-input" hidden>
        </div>

        <button type="submit" class="submit-btn">Submit Leave Request</button>
      </form>

      <!-- Overtime Request Form -->
      <form id="overtime-form-section" class="request-form hidden" 
            action="../process/submit-request.php" method="POST" enctype="multipart/form-data">

        <!-- Hidden fields -->
        <input type="hidden" name="request_type" value="overtime">
        <input type="hidden" name="submitted_by_role" value="<?php echo $_SESSION['role']; ?>">

        <label for="overtime_date">Date of Overtime</label>
        <input type="date" name="overtime_date" id="overtime_date" required>

        <div class="date-row">
          <div class="date-field">
            <label for="overtime_start">Start Time</label>
            <input type="time" name="overtime_start" id="overtime_start" required>
          </div>
          <div class="date-field">
            <label for="overtime_end">End Time</label>
            <input type="time" name="overtime_end" id="overtime_end" required>
          </div>
        </div>

        <label for="total_hours">Total Hours</label>
        <input type="text" name="total_hours" id="total_hours" placeholder="--:--" readonly>
        <label><i>Note: Maximum Overtime is 11:59PM</i></label>

        <label>Type of Overtime</label>
        <div class="overtime-type-row">
          <label class="overtime-type-option">
            <input type="radio" name="overtime_type" value="regular" checked>
            Regular Overtime
          </label>
          <label class="overtime-type-option">
            <input type="radio" name="overtime_type" value="emergency">
            Emergency Overtime
          </label>
        </div>

        <label for="overtime_reason">Reason for Overtime</label>
        <textarea name="overtime_reason" id="overtime_reason" maxlength="300" 
                  placeholder="Provide a detailed reason for your overtime request." required></textarea>

        <label for="overtime_work">Work to be Accomplished (Optional)</label>
        <textarea name="overtime_work" id="overtime_work" maxlength="300" 
                  placeholder="Describe tasks or work you will be handling during this overtime."></textarea>

        <button type="submit" class="submit-btn">Submit Overtime Request</button>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="../assets/js/admindashboard.js"></script>
  <script src="../assets/js/overTime.js"></script>
  <script src="../assets/js/clock.js"></script>

  <script>
    const leaveTab = document.getElementById("tab-leave"),
          overtimeTab = document.getElementById("tab-overtime"),
          leaveForm = document.getElementById("leave-form-section"),
          overtimeForm = document.getElementById("overtime-form-section");

    leaveTab.onclick = () => {
      leaveTab.classList.add("active");
      overtimeTab.classList.remove("active");
      leaveForm.classList.remove("hidden");
      overtimeForm.classList.add("hidden");
    };

    overtimeTab.onclick = () => {
      overtimeTab.classList.add("active");
      leaveTab.classList.remove("active");
      overtimeForm.classList.remove("hidden");
      leaveForm.classList.add("hidden");
    };
  </script>

  <script>
    const overtimeStart = document.getElementById("overtime_start");
    const overtimeEnd = document.getElementById("overtime_end");
    const totalHours = document.getElementById("total_hours");

    function calculateOvertime() {
      if (!overtimeStart.value || !overtimeEnd.value) {
        totalHours.value = "--:--";
        return;
      }

      const start = new Date(`1970-01-01T${overtimeStart.value}`);
      const end = new Date(`1970-01-01T${overtimeEnd.value}`);

      if (end <= start) {
        totalHours.value = "--:--";
        return;
      }

      const minutes = (end - start) / 60000;
      const hours = Math.floor(minutes / 60);
      const mins = minutes % 60;

      totalHours.value = `${String(hours).padStart(2, "0")}h ${String(mins).padStart(2, "0")}m`;
    }

    overtimeStart.addEventListener("change", calculateOvertime);
    overtimeEnd.addEventListener("change", calculateOvertime);
  </script>
</body>
</html>
