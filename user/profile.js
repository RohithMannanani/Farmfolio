document.addEventListener('DOMContentLoaded', function() {
    const profileIcon = document.getElementById('profileIcon');
    const profilePopup = document.getElementById('profilePopup');
    let timeoutId;

    function showPopup() {
        profilePopup.classList.add('show');
    }

    function hidePopup() {
        profilePopup.classList.remove('show');
    }

    profileIcon.addEventListener('mouseenter', () => {
        clearTimeout(timeoutId);
        showPopup();
    });

    profileIcon.addEventListener('mouseleave', () => {
        timeoutId = setTimeout(() => {
            if (!profilePopup.matches(':hover')) {
                hidePopup();
            }
        }, 300);
    });

    profilePopup.addEventListener('mouseenter', () => {
        console.log("Mouse entered icon");
        clearTimeout(timeoutId);
        showPopup(); 
    });

    profilePopup.addEventListener('mouseleave', () => {
        
        timeoutId = setTimeout(hidePopup, 300);
    });

    profileIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        profilePopup.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (!profileIcon.contains(e.target) && !profilePopup.contains(e.target)) {
            hidePopup();
        }
    });
})