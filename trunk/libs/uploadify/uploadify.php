<?php
/**
 * Este arquivo � chamado pelo AJAX do uploadify
 */

require('../utils.class.php');
$utils = new utils();

$ok = false;
$dest = base64_decode(urldecode($_GET['destino']));
if ( $utils->criarDiretorio($dest) )
{
    $file = $_FILES['Filedata'];
    if ( is_array($file) && count($file) > 0 )
    {
        if ( move_uploaded_file($file['tmp_name'], $dest.'/'.removeSpecialChars($file['name'])) )
        {
            $ok = true;
        }
    }
}
echo $ok;

/**
 * Script para remover acentos e caracteres especiais:
 */
function removeSpecialChars($oldText)
{
    // Se corrige os acentos com iso, taca iso
    if ( strlen($oldText) > strlen(utf8_decode($oldText)) )
    {
        $oldText = utf8_decode($oldText);
    }

    /*
     * A fun��o "strtr" substitui os caracteres acentuados pelos n�o acentuados.
     * A fun��o "ereg_replace" utiliza uma express�o regular que remove todos os
     * caracteres que n�o s�o letras, n�meros e s�o diferentes de "_" (underscore).
     */
    $newText = preg_replace('[^a-zA-Z0-9_-.]', '', strtr($oldText, '�������������������������� ', 'aaaaeeiooouucAAAAEEIOOOUUC_'));

    if ( !(strlen($newText) > 0) )
    {
        $newText = 'nome_invalido-'.getRandomNumbers().getRandomNumbers();
    }

    return $newText;
}
?>
