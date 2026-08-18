document.addEventListener("DOMContentLoaded", () => {

    const formTabs = document.querySelector(".form-tabs");
    //leave form
    const leaveTabBtn = document.getElementById("tab-leave");
    const overtimeTabBtn = document.getElementById("tab-overtime");

    const leaveForm = document.getElementById("leave-form-section");
    const overtimeForm = document.getElementById("overtime-form-section");

    const guidelinesLeave = document.getElementById("guidelines-leave");
    const guidelinesOvertime = document.getElementById("guidelines-overtime");

    const noteLeave = document.getElementById("note-leave");
    const noteOvertime = document.getElementById("note-overtime");


function switchTab(target){
    const isLeave = target === 'leave';
    //return true if target is "leave"
    //return false if target is not equal to "leave"

    leaveTabBtn.classList.toggle("active", isLeave);    
    overtimeTabBtn.classList.toggle("active", !isLeave);

    leaveForm.classList.toggle("hidden", !isLeave);
    overtimeForm.classList.toggle("hidden", isLeave);

    // forms on the right — only present on empDashboard.php, not the admin modal
    if (guidelinesLeave) guidelinesLeave.classList.toggle("hidden", !isLeave);
    if (guidelinesOvertime) guidelinesOvertime.classList.toggle("hidden", isLeave);
    if (noteLeave) noteLeave.classList.toggle("hidden", !isLeave);
    if (noteOvertime) noteOvertime.classList.toggle("hidden", isLeave);

    if (formTabs) formTabs.classList.toggle("overtime-active", !isLeave);
}

    leaveTabBtn.addEventListener("click", () => switchTab("leave"));
    overtimeTabBtn.addEventListener("click", () => switchTab("overtime"));

    //autofill end-date minmimun wehn start date is picked
    const startDateInput = document.getElementById("start_date");
    const endDateInput = document.getElementById("end_date");
 
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener("change", () => {
        endDateInput.min = startDateInput.value;
        });
    } 

    const overtimeStart = document.getElementById("overtime_start");
    const overtimeEnd = document.getElementById("overtime_end");
    const totalHours = document.getElementById("total_hours");

    function calculateOvertime() {
        
      if (!overtimeStart.value || !overtimeEnd.value) {
        totalHours.value = "--:--";
        return;
      }

      const start = new Date(`1970-01-01T${overtimeStart.value}`);
      let end = new Date(`1970-01-01T${overtimeEnd.value}`);

      if (end <= start) {
        totalHours.value = "--:--";
        return;
      }

      const minutes = (end - start) / 60000;
      const hours = Math.floor(minutes / 60);
      const mins = minutes % 60;

      totalHours.value = `${String(hours).padStart(2, "0")}h ${String(mins).padStart(2, "0")}m`;
    }

    if (overtimeStart && overtimeEnd && totalHours) {
        overtimeStart.addEventListener("change", calculateOvertime);
        overtimeEnd.addEventListener("change", calculateOvertime);
    }

    const overtimeDateInput = document.getElementById("overtime_date");
    if (overtimeDateInput) {
        const todayStr = new Date().toISOString().split("T")[0];
        overtimeDateInput.min = todayStr;
    }


});