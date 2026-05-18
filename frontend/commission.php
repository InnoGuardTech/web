<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دفع العمولة - حراج الفاخر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .comm-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        .bank-card {
            background: rgba(5, 150, 105, 0.05);
            border: 1px solid rgba(5, 150, 105, 0.2);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bank-details {
            font-weight: 800;
        }
        .bank-details span {
            color: var(--primary);
            font-size: 1.25rem;
            display: block;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <header class="glass-header">
        <div style="max-w: 1200px; margin: 0 auto; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                <span style="background: var(--primary); color: white; padding: 4px 8px; border-radius: 8px; font-weight: 900; font-size: 0.8rem;">حراج</span>
                <span style="font-size: 1.25rem; font-weight: 900;">الفاخر</span>
            </a>
            <div style="display: flex; gap: 1rem;">
                <button onclick="toggleTheme()" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">🌓</button>
                <a href="index.php" style="color:var(--text-muted); font-weight:bold; text-decoration:none;">العودة للرئيسية</a>
            </div>
        </div>
    </header>

    <div class="comm-container animate-fade-in">
        <h1 style="color: var(--primary); text-align: center; font-weight: 900; margin-bottom: 0.5rem;">عمولة الموقع 1% فقط</h1>
        <p style="text-align: center; color: var(--text-muted); font-weight: 700; margin-bottom: 2rem;">
            عمولة الموقع هي أمانة في ذمتك، وهي 1% من قيمة السلعة المباعة.
        </p>

        <h3 style="margin-bottom: 1rem;">حساباتنا البنكية المعتمدة في اليمن:</h3>
        
        <div class="bank-card">
            <div class="bank-details">
                بنك الكريمي الإسلامي
                <span>123456789</span>
            </div>
            <div style="font-size: 2rem;">🏦</div>
        </div>
        
        <div class="bank-card">
            <div class="bank-details">
                بنك التضامن الإسلامي
                <span>000-111-2222</span>
            </div>
            <div style="font-size: 2rem;">🏛️</div>
        </div>

        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(245,158,11,0.1); border-radius: var(--radius-lg); border: 1px solid rgba(245,158,11,0.2);">
            <h4 style="margin: 0 0 0.5rem 0; color: #b45309;">تأكيد الدفع</h4>
            <p style="margin: 0; font-size: 0.875rem; color: #d97706; font-weight: bold;">
                بعد تحويل المبلغ، يرجى التواصل معنا عبر واتساب الإدارة على الرقم (777000000) وإرسال صورة السند مع رقم الإعلان.
            </p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
