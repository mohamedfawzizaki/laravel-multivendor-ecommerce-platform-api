<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Verification</title>
    <style>
    * {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f4f4f4;
    }

    .container {
        background: white;
        padding: 20px;
        width: 100%;
        max-width: 350px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        text-align: center;
    }

    h2 {
        margin-bottom: 15px;
        color: #333;
    }

    input {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #007BFF;
        border: none;
        color: white;
        font-size: 16px;
        cursor: pointer;
        border-radius: 5px;
        transition: 0.3s;
    }

    button:hover {
        background-color: #0056b3;
    }

    .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .hidden {
        display: none;
    }

    .message {
        margin-top: 10px;
        font-weight: bold;
    }

    .success {
        color: green;
    }

    .error {
        color: red;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Mobile Submission -->
        <div id="mobileSection">
            <h2>Mobile Verification</h2>
            <input type="text" id="mobile" placeholder="Enter mobile number" value="01200593263">
            <button id="submitCodeBtn" onclick="sendCode()">Submit</button>
        </div>

        <!-- Code Submission (Initially Hidden) -->
        <div id="codeSection" class="hidden">
            <h2>Enter Verification Code</h2>
            <input type="text" id="code" placeholder="Enter code" value="4923">
            <button id="submitCodeBtn" onclick="submitCode()">Submit</button>
            <p id="codeMessage" class="message"></p> <!-- Message under the submit code button -->
        </div>
    </div>

    <script>
    const API_BASE_URL = "http://localhost/E-Commerce-App/public/api/auth/email/";
    const authToken = "{{ $token }}"; // Store token securely.

    async function fetchAPI(endpoint, data) {
        try {
            const response = await fetch(API_BASE_URL + endpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${authToken}`
                },
                body: JSON.stringify(data)
            });

            return await response.json();
        } catch (error) {
            console.error("Error:", error);
            return {
                error: "Network error. Please try again."
            };
        }
    }

    function showMessage(elementId, message, isSuccess = true) {
        const messageElement = document.getElementById(elementId);
        messageElement.innerText = message;
        messageElement.className = `message ${isSuccess ? "success" : "error"}`;
    }

    async function sendCode() {
        const mobile = document.getElementById("mobile").value.trim();
        const submitBtn = document.getElementById("submitCodeBtn");

        if (!/^[0-9]{10,15}$/.test(mobile)) {
            showMessage("codeMessage", "Please enter a valid mobile number.", false);
            return;
        }

        submitBtn.classList.add("disabled");
        submitBtn.disabled = true;

        const responseData = await fetchAPI("send-code", {
            mobile
        });

        if (responseData.error) {
            showMessage("codeMessage", responseData.error, false);
        } else {
            showMessage("codeMessage", responseData.message || "Your account is activated!");

            // Hide mobile submission and show code submission
            document.getElementById("mobileSection").classList.add("hidden");
            document.getElementById("codeSection").classList.remove("hidden");
        }

        submitBtn.classList.remove("disabled");
        submitBtn.disabled = false;
    }

    async function submitCode() {
        const code = document.getElementById("code").value.trim();
        const submitBtn = document.getElementById("submitCodeBtn");

        if (!code) {
            showMessage("codeMessage", "Please enter a verification code.", false);
            return;
        }

        submitBtn.classList.add("disabled");
        submitBtn.disabled = true;
        showMessage("codeMessage", "Submitting code...", true);

        const responseData = await fetchAPI("submit-code", {
            code
        });

        if (responseData.error) {
            showMessage("codeMessage", responseData.error, false);
        } else {
            showMessage("codeMessage", responseData.message || "Verification successful!");
        }

        submitBtn.classList.remove("disabled");
        submitBtn.disabled = false;
    }
    </script>
</body>

</html>