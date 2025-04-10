<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmfolio - Sign Up</title>
    <link rel="stylesheet" href="sign.css">
</head>
<body>
    <div class="home-button-container">
        <a href="../home/html5up-dimension/index.html" class="home-button">Home</a>
    </div>

    <div class="left-container">
        <h1 class="title">Delivery Boy <br>Sign Up</h1>
        <form method="POST" action="" id="signup-form">
            <div class="form-group">
                <label for="state">State</label>
                <select id="state" name="state" required>
                    <option value="">Select State</option>
                </select>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="district">District</label>
                <select id="district" name="district" required>
                    <option value="">Select District</option>
                </select>
                <span class="error-message"></span>
            </div>

            <button type="submit">Sign Up</button>
        </form>
    </div>

    <script>
        // Fetch states on page load
        document.addEventListener('DOMContentLoaded', function () {
            fetch('http://localhost:3000/api/states')
                .then(response => response.json())
                .then(data => {
                    const stateDropdown = document.getElementById('state');
                    stateDropdown.innerHTML = '<option value="">Select State</option>';
                    data.states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.state_id;
                        option.textContent = state.state_name;
                        stateDropdown.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching states:', error));
        });

        // Fetch districts when a state is selected
        document.getElementById('state').addEventListener('change', function () {
            const selectedStateId = this.value;

            if (selectedStateId) {
                fetch(`http://localhost:3000/api/districts/${selectedStateId}`)
                    .then(response => response.json())
                    .then(data => {
                        const districtDropdown = document.getElementById('district');
                        districtDropdown.innerHTML = '<option value="">Select District</option>';
                        data.districts.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.district_id;
                            option.textContent = district.district_name;
                            districtDropdown.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching districts:', error));
            } else {
                document.getElementById('district').innerHTML = '<option value="">Select District</option>';
            }
        });
    </script>
</body>
</html>
