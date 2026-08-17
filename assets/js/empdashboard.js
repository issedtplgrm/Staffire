document.addEventListener("DOMContentLoaded", () => {

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

    //forms on the right
    guidelinesLeave.classList.toggle("hidden", !isLeave);
    guidelinesOvertime.classList.toggle("hidden", isLeave);
    noteLeave.classList.toggle("hidden", !isLeave);
    noteOvertime.classList.toggle("hidden", isLeave);
}

    leaveTabBtn.addEventListener("click", () => switchTab("leave"));
    overtimeTabBtn.addEventListener("click", () => switchTab("overtime"));

    const attachmentInput = document.getElementById("attachment-input");
    const attachmentFilename = document.getElementById("attachment-filename"); 

    if(attachmentInput && attachmentFilename){
        attachmentInput.addEventListener("change", () =>{
            attachmentFilename.textContent = attachmentInput.files[0] ? attachmentInput.files[0].name : "No File chosen."
        });
    }

    //autofill end-date minmimun wehn start date is picked
    const startDateInput = document.getElementById("start_date");
    const endDateInput = document.getElementById("end_date");
 
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener("change", () => {
        endDateInput.min = startDateInput.value;
        });
    } 

    //overtime form
    // const overtimeStart = document.getElementById("overtime_start");
    // const overtimeEnd = document.getElementById("overtime_end");
});