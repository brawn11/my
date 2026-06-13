<?php
// إعداد ترويسة الصفحة لتدعم اللغة العربية بشكل صحيح
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تجربة PHP على Vercel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 40px;
            text-align: center;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: inline-block;
            max-width: 500px;
            width: 100%;
        }
        h1 { color: #0070f3; }
        .info {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 1.1em;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>تهانينا! 🎉</h1>
        <p>ملف الـ PHP يعمل بنجاح وبكفاءة عالية على منصة <strong>Vercel</strong>.</p>
        
        <div class="info">
            <?php 
                echo "إصدار PHP الحالي: " . phpversion(); 
            ?>
        </div>
        
        <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
            الوقت الحالي في السيرفر: <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>

</body>
</html>
