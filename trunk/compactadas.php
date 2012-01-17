<?php
// Obtém o caminho o arquivo.zip
$zip_file = $_GET['path'];

// Retorna o arquivo.zip para download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="compactadas.zip"'); 
header('Content-Transfer-Encoding: binary');
readfile($zip_file);
?>
