document.getElementById('signup-form').addEventListener('input', function (event) {
    const field = event.target;
    const errorDiv = field.nextElementSibling;

    // Validators object for all form fields

    const validators = {
        username: {
            pattern: /^(?!.*\s{2})(?!.*\b(?:kkk|kjk|aaa|bbb|ccc|zzz)\b)[A-Za-z][A-Za-z\s.'-]{4,49}$/,
            message: "Name must start with a letter, can include single spaces, periods, apostrophes, and hyphens, and be 2-50 characters long. Random patterns are not allowed."
        },
        mobile: {
            pattern: /^[6-9]\d{9}$/,
            message: "Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9."
        },
        email: {
            pattern: /^[^\s][a-zA-Z0-9._%+-]+@[a-zA-Z-]+(\.[a-zA-Z]{2,})+$/,
            message: "Please enter a valid email address without leading spaces."
        },
        house: {
            pattern: /^[a-zA-Z0-9][a-zA-Z0-9 .,-]{2,}$/,
            message: "House name can only contain letters, numbers, and optionally spaces, commas, periods, or hyphens."
        },
        password: {
            pattern: /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/,
            message: "Password must be at least 8 characters long, with one uppercase, one lowercase, one number, and one special character."
        },
        "confirm-password": {
            customValidation: function (value) {
                const password = document.getElementById('password').value;
                return value === password;
            },
            message: "Passwords do not match."
        }
    };

    // Validate a single field
    function validateField(field, validator) {
        if (!validator) return;

        const value = field.value.trim();
        console.log("Input value (trimmed):", value); 
        let errorMessage = "";

        // Check if field is empty
        if (value === "") {
            errorMessage = "This field is required.";
        } 
        // Check for a pattern match
        else if (validator.pattern && !validator.pattern.test(value)) {
            errorMessage = validator.message;
        } 
        // Custom validation (e.g., password match)
        else if (validator.customValidation && !validator.customValidation(value)) {
            errorMessage = validator.message;
        }

        // Update the error message
        errorDiv.textContent = errorMessage;
        errorDiv.style.color = errorMessage ? "red" : "green";
    }

    // Trigger validation if the field is in validators
    if (field.name in validators) {
        validateField(field, validators[field.name]);
    }
}); fetch('india-states-districts.json')
        .then(response => response.json())
        .then(data => {
            const stateDropdown = document.getElementById('state');
            const districtDropdown = document.getElementById('district');

            // Populate states
            Object.keys(data).forEach(state => {
                const option = document.createElement('option');
                option.value = state;
                option.textContent = state;
                stateDropdown.appendChild(option);
            });

            // Update districts when state changes
            stateDropdown.addEventListener('change', function () {
                const selectedState = this.value;
                districtDropdown.innerHTML = '<option value="">Select District</option>';
                if (data[selectedState]) {
                    data[selectedState].forEach(district => {
                        const option = document.createElement('option');
                        option.value = district;
                        option.textContent = district;
                        districtDropdown.appendChild(option);
                    });
                }
            });
        });

    // Fetch PIN codes and validate
    fetch('pincode.json')
        .then(response => response.json())
        .then(pincode => {
            document.getElementById('pin').addEventListener('input', function () {
                const enteredPin = this.value.trim();
                const pinErrorDiv = this.nextElementSibling;
                
                if (!/^\d{6}$/.test(enteredPin)) {
                    pinErrorDiv.textContent = "Please enter a valid 6-digit PIN code.";
                    pinErrorDiv.style.color = "red";
                    return;
                }

                const validPin = pincode.some(pinEntry => pinEntry.pincode === enteredPin);
                if (!validPin) {
                    pinErrorDiv.textContent = "Invalid PIN code. Please enter a correct one.";
                    pinErrorDiv.style.color = "red";
                } else {
                    pinErrorDiv.textContent = "Valid PIN code.";
                    pinErrorDiv.style.color = "green";
                }
            });
        })
        .catch(error => console.error("Error fetching PIN codes:", error));fetch('pincode.json')
    .then(response => response.json())
    .then(data => {
        const pinArray = data.pincodes; // Extract the array

        document.getElementById('pin').addEventListener('input', function () {
            const enteredPin = this.value.trim();
            const pinErrorDiv = this.nextElementSibling;

            if (!/^\d{6}$/.test(enteredPin)) {
                pinErrorDiv.textContent = "Please enter a valid 6-digit PIN code.";
                pinErrorDiv.style.color = "red";
                return;
            }

            // Convert entered PIN to a number and check if it exists in the array
            const validPin = pinArray.includes(Number(enteredPin));
            
            if (!validPin) {
                pinErrorDiv.textContent = "Invalid PIN code. Please enter a correct one.";
                pinErrorDiv.style.color = "red";
            } else {
                pinErrorDiv.textContent = "Valid PIN code.";
                pinErrorDiv.style.color = "green";
            }
        });
    })
    .catch(error => console.error("Error fetching PIN codes:", error));