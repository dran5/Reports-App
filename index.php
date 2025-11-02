<?php
// index.php
require 'config.php';
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login - Reports App</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="shortcut icon" href="images/Spider-Man.png" type="image/x-icon">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0d1117;
      color: #fff;
    }

    /* Particle background */
    #particles-js {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
    }

    /* Dark glass card */
    .card {
      position: relative;
      z-index: 1;
      background: rgba(20, 25, 35, 0.92);
      color: #e0e0e0;
      padding: 34px 28px;
      border-radius: 18px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.5);
      width: 400px;
      text-align: center;
      backdrop-filter: blur(10px);
      border: 2px solid transparent;
      background-clip: padding-box;
      transition: all 0.4s ease;
      animation: fadeIn 1.2s ease;
    }

    .card::before {
      content: "";
      position: absolute;
      top: -2px; left: -2px; right: -2px; bottom: -2px;
      z-index: -1;
      border-radius: 20px;
      background: linear-gradient(135deg, #00c6ff, #0072ff, #1d72f3);
      background-size: 400% 400%;
      animation: borderFlow 6s ease infinite;
    }

    .card:hover {
      transform: scale(1.03);
      box-shadow: 0 10px 40px rgba(0,0,0,0.6);
    }

    h2 {
      margin-bottom: 20px;
      color: #fff;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    p {
      color: #aaa;
      font-size: 14px;
      margin-top: 12px;
    }

    /* Custom dark Google button container */
    .g_id_signin {
      margin-top: 10px;
    }

    /* Optional fade animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes borderFlow {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
  </style>
</head>
<body>
  <div id="particles-js"></div>

  <div class="card">
    <h2>Sign in</h2>
    <div id="g_id_onload"
      data-client_id="<?php echo htmlspecialchars(GOOGLE_CLIENT_ID); ?>"
      data-callback="handleCredentialResponse"
      data-auto_prompt="false">
    </div>

    <!-- Dark theme Google Sign-In button -->
    <div class="g_id_signin"
      data-type="standard"
      data-theme="filled_black"
      data-shape="rectangular"
      data-size="large"
      data-text="signin_with"
      data-logo_alignment="left">
    </div>

    <p>Sign in with your Google account to access the dashboard.</p>
  </div>

<script>
function handleCredentialResponse(response) {
    fetch('api/google_login.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id_token: response.credential})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'dashboard.php';
        } else {
            alert('Login failed: ' + (data.message || 'unknown'));
        }
    })
    .catch(e => { console.error(e); alert('Network error'); });
}

// Initialize particles
particlesJS("particles-js", {
  "particles": {
    "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
    "color": { "value": "#00c6ff" },
    "shape": { "type": "circle" },
    "opacity": { "value": 0.4, "random": true },
    "size": { "value": 3, "random": true },
    "line_linked": { "enable": true, "distance": 150, "color": "#00c6ff", "opacity": 0.2, "width": 1 },
    "move": { "enable": true, "speed": 2, "direction": "none", "out_mode": "out" }
  },
  "interactivity": {
    "events": {
      "onhover": { "enable": true, "mode": "repulse" },
      "onclick": { "enable": true, "mode": "push" },
      "resize": true
    },
    "modes": {
      "repulse": { "distance": 100 },
      "push": { "particles_nb": 4 }
    }
  },
  "retina_detect": true
});
</script>

</body>
</html>
