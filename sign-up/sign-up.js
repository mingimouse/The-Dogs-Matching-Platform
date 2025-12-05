document.getElementById("closeModalBtn").addEventListener("click", function () {
    document.getElementById("signupModal").style.display = "none";
});

document.getElementById("btnUserSignup").addEventListener("click", function () {
    window.location.href = "shelter_sign-up.html"; 
});

document.getElementById("btnShelterSignup").addEventListener("click", function () {
    window.location.href = "shelter_signup.php"; 
});
