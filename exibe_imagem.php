<?php
// Obtém o caminho da imagem
$image_path = $_GET['path'];
// Obtém o mimeType
$mimeType = image_type_to_mime_type(exif_imagetype($image_path));

// Exibe a imagem
header('Content-Type: '.$mimeType);
$array = explode('/', $image_path);
$file_name = array_pop($array);
header('Content-Disposition: attachment; filename="'.$file_name.'";'); 
header('Content-Transfer-Encoding: binary');
readfile($image_path);
?>
