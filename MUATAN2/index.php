<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGENDA KEGIATAN SEKOLAH</title>
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
            overflow: hidden;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .main-container {
            position: relative; 
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header-display {
            display: flex;
            align-items: center;
            width: max-content;
            margin-bottom: 30px;
        }

        .logo-smk {
            width: 120px; 
            height: auto;
            margin-right: 15px; 
        }

        .login-title {
            color: white;
            font-size: 2.5em; 
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2); 
            margin: 0;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        .selection-box {
            background-color: white;
            padding: 40px 60px; 
            border-radius: 25px; 
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3); 
            width: 450px; 
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 0; 
        }

        .login-subtitle {
            color: #00BCD4;
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: block;
            text-decoration: none;
            color: white;
            font-size: 1.5em; 
            font-weight: bold;
            padding: 15px 30px; 
            margin: 15px 0; 
            border-radius: 60px; 
            transition: all 0.2s;
            box-shadow: 0 5px 0px rgba(0, 0, 0, 0.25), 0 0 15px rgba(0, 0, 0, 0.15); 
            letter-spacing: 1px;
            width: 100%; 
            text-align: center;
            box-sizing: border-box;
        }

        .panitia, .guru, .siswa {
            background-color: #00BCD4; 
            background-image: linear-gradient(180deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }

        .btn:hover {
            background-color: #00A8C2; 
            transform: translateY(-3px); 
            box-shadow: 0 8px 0px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .btn:active {
            transform: translateY(3px); 
            box-shadow: 0 3px 0px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body>
    <div class="main-container">
        
        <div class="header-display">
            <img src="logo.png" alt="Logo SMK Negeri 1 Kota Bekasi" class="logo-smk">
            <h1 class="login-title">AGENDA KEGIATAN SEKOLAH</h1>
        </div>

        <div class="selection-box">

            <div class="login-subtitle">LOGIN SEBAGAI</div>
            
            <a href="panitia.php" class="btn panitia">PANITIA</a>
            <a href="guru.php" class="btn guru">GURU</a>
            <a href="siswa.php" class="btn siswa">SISWA</a>
        </div>
    </div>
</body>
</html>