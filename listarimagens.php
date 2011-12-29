<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <title>Listar Imagens</title>
    </head>
    <body>
<?php
// nome da pasta onde está as imagens
$pasta = $_GET['path'];
// recuperar as imagens e colocar em um array
$imagens = (array)glob("$pasta/{*jpg,*png,*gif}", GLOB_BRACE);
// percorre o array
if ( count($imagens) > 0 )
{
    foreach ( $imagens as $img )
    {
        // imprime a imagem
        echo '<img src="'.$img.'"/><br />';
    }
}
else
{
    echo "Nenhuma imagem.";
}
?>
    </body>
</html>
