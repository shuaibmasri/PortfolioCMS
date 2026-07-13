<?php
// Simple trial code to test index page
http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>PortfolioCMS - Index Test</title>
	<style>body{font-family:Arial,Helvetica,sans-serif;background:#f7f7f7;color:#222;padding:40px}</style>
</head>
<body>
	<h1>Index Page Test</h1>
	<p>Status: <strong>OK</strong></p>
	<ul>
		<li>PHP version: <?php echo phpversion(); ?></li>
		<li>Server time: <?php echo date('Y-m-d H:i:s'); ?></li>
		<li>Request method: <?php echo $_SERVER['REQUEST_METHOD'] ?? 'N/A'; ?></li>
	</ul>
	<p>If you see this page, the index.php is being served correctly.</p>
</body>
</html>
