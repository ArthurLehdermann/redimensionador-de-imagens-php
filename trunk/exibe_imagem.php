<?php
// Obtém o caminho da imagem
$image_path = $_GET['path'];
// Obtém o mimeType
$mimeType = image_type_to_mime_type(exif_imagetype($image_path));;

// Exibe a imagem
header('Content-Type: '.$mimeType);
readfile($image_path);
?>
