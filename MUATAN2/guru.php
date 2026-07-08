<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Guru</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-image: url('login.png'); 
            background-size: cover; 
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            
            font-family: Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: white;
            border-radius: 25px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 400px; 
            padding: 40px 50px; 
            box-sizing: border-box;
            z-index: 10;
        }

        .page-title {
            color: #495057; 
            font-size: 2.5em;
            font-weight: bold;
            text-shadow: none;
            margin-bottom: 30px;
            margin-top: 0;
        }

        .login-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .input-group {
            margin-bottom: 20px;
            width: 100%;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px; 
            font-size: 1.5em; 
            font-weight: bold;
            
            color: white; 
            background-color: #00BCD4; 
            border: 3px solid #799DE3; 
            
            text-align: center;
            border-radius: 50px; 
            box-shadow: 0 4px 0px rgba(0, 0, 0, 0.2), 0 0 10px rgba(0, 0, 0, 0.1); 
            box-sizing: border-box;
            transition: all 0.2s;
        }

        .input-group input::placeholder {
            color: white; 
            opacity: 1; 
        }

        .input-group input:focus {
            outline: none;
            border-color: #ffffff; 
            box-shadow: 0 0 10px #ffffff;
            background-color: #00A8C2;
        }

        .button-group {
            width: 100%;
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .btn {
            font-size: 1.3em;
            font-weight: bold;
            padding: 12px 25px;
            border-radius: 50px;
            border: 3px solid #799DE3; 
            cursor: pointer;
            width: 48%;
            transition: all 0.2s;
            box-shadow: 0 4px 0px rgba(0, 0, 0, 0.2), 0 0 10px rgba(0, 0, 0, 0.1); 
        }

        .btn-login {
            background-color: #00BCD4; 
            color: white;
        }

        .btn-batal {
            background-color: #00BCD4; 
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 0px rgba(0, 0, 0, 0.3), 0 0 15px rgba(0, 0, 0, 0.2);
            background-color: #00A8C2;
        }

        .btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 0px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body>
    <script>
        function validateNumeric(input) {
            // HANYA MENGIZINKAN KARAKTER 0-9
            input.value = input.value.replace(/[^0-9]/g, '');
        }
    </script>
    
    <div class="login-container">
        <h1 class="page-title">GURU</h1>
        
        <form class="login-form" action="guru_login.php" method="POST">
            <div class="input-group">
                <input 
                    type="text" 
                    id="nip" 
                    name="nip" 
                    placeholder="NIP" 
                    required
                    onkeyup="validateNumeric(this)"
                    inputmode="numeric"
                >
            </div>

            <div class="input-group">
                <input type="text" id="nama" name="nama" placeholder="NAMA" required>
            </div>

            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="PASSWORD" required>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-login">LOGIN</button>
                <button type="button" class="btn btn-batal" onclick="window.location.href='index.php'">BATAL</button> 
            </div>
        </form>
    </div>
</body>
</html>