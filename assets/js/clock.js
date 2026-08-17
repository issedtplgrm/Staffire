

//use id=clock
window.onload = function() {
    function updateClock() {
        const now = new Date();
        const options = {
            timeZone: 'Asia/Manila',   
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('clock').textContent =
            now.toLocaleString('en-PH', options);
    }

    updateClock();
    setInterval(updateClock, 1000);
};
