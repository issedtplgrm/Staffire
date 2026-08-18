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

function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
}

document.addEventListener("DOMContentLoaded", () =>{
    loadNotifications();
});