document.addEventListener("DOMContentLoaded", function () {

    console.log("Expense Tracker loaded successfully.");


    // ==========================================
    // AUTO HIDE MESSAGES
    // ==========================================

    const message = document.querySelector(".message");

    if (message) {

        setTimeout(function () {

            message.style.transition = "opacity 0.5s ease";
            message.style.opacity = "0";

            setTimeout(function () {

                message.remove();

            }, 500);

        }, 4000);

    }


    // ==========================================
    // AMOUNT VALIDATION
    // ==========================================

    const amountInput = document.getElementById("amount");

    if (amountInput) {

        amountInput.addEventListener("input", function () {

            if (parseFloat(this.value) < 0) {

                this.value = "";

            }

        });

    }


    // ==========================================
    // CONFIRM DELETE
    // ==========================================

    const deleteForms = document.querySelectorAll(".delete-form");

    deleteForms.forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const confirmed = confirm(
                "Are you sure you want to delete this transaction?"
            );

            if (!confirmed) {

                event.preventDefault();

            }

        });

    });


    // ==========================================
    // SEARCH INPUT
    // ==========================================

    const searchInput = document.querySelector(
        'input[name="search"]'
    );

    if (searchInput) {

        searchInput.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {

                this.value = "";

            }

        });

    }

});