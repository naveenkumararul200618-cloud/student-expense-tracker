
document.addEventListener("DOMContentLoaded", function () {
    const cards = document.querySelectorAll(".summary-card");

    cards.forEach(function (card) {
        card.addEventListener("click", function () {
            card.style.transform = "scale(1.02)";

            setTimeout(function () {
                card.style.transform = "";
            }, 150);
        });
    });
});
