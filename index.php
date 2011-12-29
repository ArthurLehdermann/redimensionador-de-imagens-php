<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <title>Redimensionador de imagens</title>
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <script type="text/javascript" language="JavaScript" src="js/script.js"></script>
    </head>
    <body>
        <div id="header">
            <div id="popup">
                <div id="inf" style="display:none;" onclick="this.style.display='none';">
                    <div class="titulo">Informação:</div>
                    <div id="informacao" class="mensagem"></div>
                </div>
                <div id="err" style="display:none;" onclick="this.style.display='none';">
                    <div class="titulo">ERRO!</div>
                    <div id="erro" class="mensagem"></div>
                </div>
            </div>
            <div id="cabecalho">
                Redimensionador de imagens v1.0 ;-)
                <hr>
            </div>
        </div>
        <form id="form_imagens" name="form_imagens" method="post" action="" enctype="multipart/form-data">
            <fieldset>
                <legend>
                    Preferências
                </legend>
                <div id="div_preferencias">
                    <span id="camposTamanho">
                        <!-- Campos dos tamanhos -->
                        <script>adicionaTamanhos();</script>
                    </span>

                    <a title="Menos tamanhos" style="cursor:hand;cursor:pointer;float:left;padding:3px;" onclick="removeTamanhos();">-</a>
                    <a title="Mais tamanhos" style="cursor:hand;cursor:pointer;float:right;padding:3px;" onclick="adicionaTamanhos();">+</a><br /><br />
  
                    <label>Converter imagens:</label>
                        <input type="checkbox" id="converter" name="converter" onchange="exibe_oculta('div_converterPara');" value='1'/>
                    <span id="div_converterPara" style="display:none;">
                    <label>Converter para:</label>
                            <select id="converterPara" name="converterPara">
                                <option value="jpg" checked>jpg</option>
                                <option value="png">png</option>
                            </select>
                        </span>
                </div>
            </fieldset>
            <fieldset>
                <legend>Imagens compactadas (.zip)</legend>
                <label>Arquivo .zip:</label>
                <input type="file" id="compactado" name="compactado" class="campoFile"/>
            </fieldset>
            <fieldset>
                <legend>
                    Imagens:
                </legend>
                <div id="div_imagens">
                    <!-- Campos de imagem -->
                    <script>add_filefield();</script>
                </div>
            </fieldset>
            <input type="submit" value="Enviar" style="float:right;"/>
            <input type="hidden" name="enviado" value="1"/>
        </form>
    </body>
</html>
<?php
// Após feito o post
if ( (isset($_POST)) && ($_POST['enviado'] == 1) )
{
    try
    {
        require("imagem.class.php");

        $dir = 'media/imagens';
        $dirCompactadas = $dir.'/compactadas';
        $dirDescompactadas = $dir.'/descompactadas';

        $fotosEnviadas = arrumaArrayFiles($_FILES['imagens']);
        if ( strlen($_FILES['compactado']['tmp_name']) > 0 )
        {
            $fotosCompactadas = obterFotosCompactadas($dirDescompactadas, $_FILES['compactado']['tmp_name']);
        }
        $fotos = array_merge($fotosEnviadas, (array)$fotosCompactadas);

        $converterPara = ($_POST['converter']) ? $_POST['converterPara'] : null;
    
        // Converte/renomeia/redimensiona
        $imagem = new imagem($fotos, $_POST['altura'], $_POST['largura'], $dir, $converterPara);

        $novasImagens = $imagem->obterDiretorioNovasImagens();

        // Compacta a pasta com as imagens
        $compactadas = compactarImagens($novasImagens, $dirCompactadas);

        if ( strlen($novasImagens) > 0 )
        {
            echo "<script>informacao('Imagens redimensionadas com sucesso!');</script>";
            echo "<br /><a href=\"listarimagens.php?path=$novasImagens\" target=\"_blank\">Clique aqui</a> para vê-las.<br /> Ou <a href=\"$compactadas\" target=\"_blank\">clique aqui</a> para fazer o download.";
        }
        else
        {
            echo "<script>erro('Ocorreu algum erro ao redimensionar as imagens. Tente novamente mais tarde.');</script>";
        }
    }
    catch( Exception $e )
    {
        echo "<script>erro('Falha ao redimensioar imagens.<br />');</script>";
        echo "<script>erro('".$e->getMessage()."');</script>";
    }
}

function arrumaArrayFiles($array = array())
{
    $new = array();

    foreach ( $array as $key => $value )
    {
        foreach ( $value as $k => $val )
        {
            // Remove os post de campo vazio
            if ( strlen($array['name'][$k]) > 0 )
            {
                $new[$k][$key] = $val;
                // Acrescenta o novo nome
                $new[$k]['novo_nome'] = $_POST['nome_imagem'][$k];
            }
        }
    }

    return $new;
}
