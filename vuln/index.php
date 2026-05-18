<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Demo Vulnerabilități — Quizztador</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 40px auto; background: #f5f5f5; }
        h1   { color: #c0392b; }
        .card { background: #fff; border-left: 5px solid #e74c3c; padding: 20px; margin: 15px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        .card h2 { margin: 0 0 8px; color: #2c3e50; }
        .card p  { margin: 4px 0; color: #555; font-size:.95em; }
        .card a  { display:inline-block; margin-top:10px; background:#e74c3c; color:#fff; padding:7px 16px; border-radius:4px; text-decoration:none; }
        .card a:hover { background:#c0392b; }
        .warn { background:#fff3cd; border:1px solid #ffc107; padding:15px; border-radius:5px; margin-bottom:20px; }
        .fixed { border-left-color: #27ae60; }
        .setup-btn { background:#2c3e50; color:#fff; padding:10px 20px; border-radius:5px; text-decoration:none; display:inline-block; margin-bottom:20px; }
    </style>
</head>
<body>
    <h1>&#9888; Demo Vulnerabilități Web — Quizztador</h1>

    <div class="warn">
        <strong>ATENȚIE:</strong> Aceste pagini sunt <u>intenționat vulnerabile</u> — create exclusiv
        pentru demonstrarea atacurilor în cadrul laboratorului de Programare Web (UBB).
        <strong>Nu lăsați aceste pagini accesibile în producție!</strong>
    </div>

    <a class="setup-btn" href="setup_vuln.php">&#9881; Rulează Setup Vulnerabilități</a>

    <div class="card">
        <h2>1. SQL Injection — Bypass Autentificare</h2>
        <p>Login vulnerabil care concatenează direct input-ul utilizatorului în query SQL.</p>
        <p><strong>Exploit:</strong> <code>admin'--</code> ca username → ignoră complet parola</p>
        <a href="1_sqli_login.php">Demo SQL Injection Login</a>
    </div>

    <div class="card">
        <h2>2. SQL Injection — Extragere Date (UNION)</h2>
        <p>Căutare vulnerabilă care permite extragerea datelor din alte tabele via UNION.</p>
        <p><strong>Exploit:</strong> <code>' UNION SELECT username, password_hash, email FROM users -- </code></p>
        <a href="2_sqli_search.php">Demo SQL Injection Search</a>
    </div>

    <div class="card">
        <h2>3. Cross-Site Scripting (XSS) — Reflected</h2>
        <p>Parametru GET afișat direct în HTML fără escape.</p>
        <p><strong>Exploit:</strong> <code>?name=&lt;script&gt;alert(document.cookie)&lt;/script&gt;</code></p>
        <a href="3_xss_reflected.php">Demo XSS Reflected</a>
    </div>

    <div class="card">
        <h2>4. Cross-Site Scripting (XSS) — Stored</h2>
        <p>Mesajele din guestbook salvate și afișate fără escape → XSS persistent.</p>
        <p><strong>Exploit:</strong> Trimite <code>&lt;script&gt;alert('XSS stored!')&lt;/script&gt;</code> ca mesaj</p>
        <a href="4_xss_stored.php">Demo XSS Stored</a>
    </div>

    <div class="card">
        <h2>5. Unrestricted File Upload</h2>
        <p>Upload fără validare tip fișier — permite încărcarea de fișiere PHP executabile (webshell).</p>
        <p><strong>Exploit:</strong> Urcă <code>shell.php</code> și execută comenzi sistem</p>
        <a href="5_upload_vuln.php">Demo Unrestricted Upload</a>
    </div>

    <div class="card">
        <h2>6. Path Traversal</h2>
        <p>Descărcare fișier fără sanitizare → permite citirea oricărui fișier de pe server.</p>
        <p><strong>Exploit:</strong> <code>?file=../config/db.php</code> → credențiale BD expuse</p>
        <a href="6_path_traversal.php">Demo Path Traversal</a>
    </div>

    <div class="card">
        <h2>7. Cross-Site Request Forgery (CSRF)</h2>
        <p>Formular fără token CSRF — o pagină malițioasă poate modifica datele utilizatorului autentificat.</p>
        <p><strong>Exploit:</strong> Deschide pagina de mai jos când ești autentificat în Quizztador</p>
        <a href="csrf_exploit.html">Deschide pagina CSRF (exploit)</a>
    </div>

    <hr>
    <p><a href="../login.php">← Înapoi la aplicație</a></p>
</body>
</html>
