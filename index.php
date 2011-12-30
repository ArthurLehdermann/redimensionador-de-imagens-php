<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <title>Redimensionador de imagens</title>
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <script type="text/javascript" language="JavaScript" src="js/script.js"></script>
        <link rel="shortcut icon" href="media/favicon.ico" type="image/x-icon"/>
<style>
body {background-image: url(http://www.univates.br/media/sistemas/verde.png);}
.direita {margin-left:300px;width:400px;-moz-border-radius:4px;border-radius:4px;border: 1px solid #BBBBBB; }
#form_imagens {margin: 0 auto; width:1000px;}
</style>
    </head>
    <body>
        <div id="header">
            <div id="popup">
                <div id="inf" style="display:none;" onclick="this.style.display='none';">
                    <div class="titulo">Informa��o:</div>
                    <div id="informacao" class="mensagem"></div>
                </div>
                <div id="err" style="display:none;" onclick="this.style.display='none';">
                    <div class="titulo">ERRO!</div>
                    <div id="erro" class="mensagem"></div>
                </div>
            </div>
            <div id="cabecalho">
                <img src="http://www.univates.br/media/sistemas/lupa.png" style="width:55px;height:43px;margin-bottom:-14px;"> Redimensionador de imagens v1.0 ;-)
                <hr>
            </div>
        </div>
        <form id="form_imagens" name="form_imagens" method="post" action="" enctype="multipart/form-data">
            <fieldset class="direita">
                <legend>
                    Prefer�ncias
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
            <fieldset class="direita" >
                <legend>Imagens compactadas (.zip)</legend>
                <label>Arquivo(s) .zip:</label>
                <br style="clear:both;">
                <div id="div_zips">
                    <!-- Campos de arquivos .ZIP -->
                    <script>add_zipField();</script>
                </div>
            </fieldset>
            <fieldset class="direita">
                <legend>
                    Imagens:
                </legend>
                <div id="div_imagens">
                    <!-- Campos de imagem -->
                    <script>add_fileField();</script>
                </div>
            </fieldset>
            <br />
            <center><input type="submit" style="margin-left:300px;" value="Enviar" /></center>
            <input type="hidden" name="enviado" value="1"/>
        </form>
    </body>
</html>
<?php
// Ap�s feito o post
if ( (isset($_POST)) && ($_POST['enviado'] == 1) )
{
    try
    {
        // Inclui a classe que trabalha nas imagens
        require("imagem.class.php");

        // Define alguns diret�rios
        $dir = '/tmp/redimensionador_imagens/imagens';
        $dirCompactadas = $dir.'/compactadas';
        $dirDescompactadas = $dir.'/descompactadas';

        // Se necess�rio, cria os diret�rios (visto que est�o no /tmp)
        // /tmp/redimensionador_imagens
        if ( !in_array('redimensionador_imagens', scandir('/tmp')) )
        {
            exec('mkdir /tmp/redimensionador_imagens');
        }
        // /tmp/redimensionador_imagens/imagens
        if ( !in_array('imagens', scandir('/tmp')) )
        {
            exec('mkdir '.$dir);
        }
        // /tmp/redimensionador_imagens/imagens/compactadas
        if ( !in_array('compactadas', scandir($dir)) )
        {
            exec('mkdir '.$dirCompactadas);
        }
        // /tmp/redimensionador_imagens/imagens/descompactadas
        if ( !in_array('descompactadas', scandir($dir)) )
        {
            exec('mkdir '.$dirDescompactada);
        }

        // Obt�m as imagens "upadas"
        $fotosEnviadas = arrumaArrayFiles($_FILES['imagens']);
        $fotosCompactadasEnviadas = arrumaArrayFiles($_FILES['compactadas']);

        // Caso tenha aqruivos comprimidos, obt�m as imagens deletes
        if ( count($fotosCompactadasEnviadas) > 0 )
        {
            $fotosCompactadas = array();
            foreach ( $fotosCompactadasEnviadas as $fotosCompactadas )
            {
                $fotosCompactadas = array_merge(obterFotosCompactadas($dirDescompactadas, $fotosCompactadas), $fotosCompactadas);
            }
        }
        // Junta todas as imagens
        $fotos = array_merge($fotosEnviadas, (array)$fotosCompactadas);

        // Op��o de convers�o de formato de imagem
        $converterPara = ($_POST['converter']) ? $_POST['converterPara'] : null;
    
        // Converte/redimensiona
        $imagem = new imagem($fotos, $_POST['altura'], $_POST['largura'], $dir, $converterPara);

        // Imagens prontas
        $novasImagens = $imagem->obterDiretorioNovasImagens();

        // Compacta a pasta com as imagens
        //$compactadas = compactarImagens($novasImagens, $dirCompactadas);

        if ( strlen($novasImagens) > 0 )
        {
            echo "<script>informacao('Imagens redimensionadas com sucesso!');</script>";
            echo "<br /><a href=\"listarimagens.php?path=$novasImagens\" target=\"_blank\">Clique aqui</a> para v�-las.<br /> Ou <a href=\"$compactadas\" target=\"_blank\">clique aqui</a> para fazer o download.";
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

function arrumaArrayFiles($array)
{
    $new = array();

    foreach ( (array)$array as $key => $value )
    {
        foreach ( $value as $k => $val )
        {
            // Remove os post de campo vazio
            if ( strlen($array['name'][$k]) > 0 )
            {
                $new[$k][$key] = $val;
            }
        }
    }

    return $new;
}
