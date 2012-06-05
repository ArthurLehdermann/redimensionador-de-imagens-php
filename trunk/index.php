<?php
session_start();
if ( !isset($_SESSION['sistemas']) )
{
    header("Location: /sistemas/index.php?url=imagem");
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <title>Redimensionador de imagens</title>
        <script type="text/javascript" language="JavaScript" src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
        <script type="text/javascript" language="JavaScript" src="http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <script type="text/javascript" language="JavaScript" src="js/script.js"></script>
        <link rel="shortcut icon" href="media/favicon.ico" type="image/x-icon"/>
        <!--Uploadify-->
         <link href="/libs/uploadify/uploadify.css" type="text/css" rel="stylesheet" />
         <script type="text/javascript" src="/libs/uploadify/swfobject.js"></script>
         <script type="text/javascript" src="/libs/uploadify/jquery.uploadify.v2.1.4.min.js"></script>


    </head>
    <body>
<?php
// Inclui a classe de utilidades
require_once("libs/utils.class.php");
// Instancia a classe de utilidades
$utils = new utils();

// Inclui a classe de imagem
include_once("libs/imagem.class.php");
// Instancia a classe d imagem
$imagem = new imagem();

// Inclui a classe de ZIP
include_once("libs/zip.class.php");
// Instancia a classe de ZIP
$zip = new zip();



// Define alguns diretórios
$dir = '/tmp/redimensionador_imagens/imagens';
$dirCompactadas = $dir.'/compactadas';
$dirDescompactadas = $dir.'/descompactadas';

// Se necessário, cria os diretórios (visto que estão no /tmp)
// /tmp/redimensionador_imagens/imagens
$utils->criarDiretorio($dir);
// /tmp/redimensionador_imagens/imagens/compactadas
$utils->criarDiretorio($dirCompactadas);
// /tmp/redimensionador_imagens/imagens/descompactadas
$utils->criarDiretorio($dirDescompactadas);

// Gera um nome(ip_xxxxxx) e cria o diretório para as imagens vinda do multiupload
$destino = $utils->gerarNomeDiretorio($dir);
?>
<script type="text/javascript" language="JavaScript">
$(document).ready(function()
{
    $('#file_upload').uploadify(
    {
        'uploader' : 'libs/uploadify/uploadify.swf',
        'script' : 'libs/uploadify/uploadify.php?destino=<?=urlencode(base64_encode($destino));?>',
        'cancelImg' : 'media/remover.png',
        'buttonText' : 'Selecionar arquivos',
        'fileExt'  : '*.jpeg;*.jpg;*.gif;*.png;*.bmp;*.JPG;*.GIF;*.JPEG;*.BMP;*.PNG',
        'fileDesc' : 'Arquivos',
        'multi'  : true,
        'method' : 'post',
        'removeCompleted' : false,
        'auto' : true,
        'sizeLimit' : 1024*1024*100, //1024*1024 => 1M
        'width'  : 120,
        'height' : 27,
        'onError' : function(event, ID, fileObj, errorObj)
        {
            alert(errorObj.type+"::"+errorObj.info);
        },
        //'onComplete' : function(event, ID, fileObj, response, data){ alert(response); },
        'onAllComplete' : function(event, data)
        {
            //location.reload();
            alert('Imagens enviadas com sucesso');
        }
    });
});
</script>
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
                <img src="http://www.univates.br/media/sistemas/lupa.png" style="width:55px;height:43px;margin-bottom:-14px;"> Redimensionador de imagens v3.0
                <hr>
            </div>
        </div>
        <form id="form_imagens" name="form_imagens" method="post" action="" enctype="multipart/form-data">
            <fieldset class="direita">
                <legend>
                    Preferências
                </legend>
                <div id="div_preferencias">
                    <span id="campo_nome_padrao">
                        Se desejado, informe um nome padrão:<br />
                        <input type="text" id="nome_padrao" name="nome_padrao" class="botao"/><br />
                        <small>Ex.: <b>foto_%n</b><br />
                        Obs.: "%n" é um curinga que será substituído por um número crescente que começa em 1.</small><br /><br />
                    </span>
                    <span id="camposTamanho">
                        <!-- Campos dos tamanhos -->
                    </span>

                    <a id="menos" title="Menos tamanhos" style="cursor:hand;cursor:pointer;float:left;padding:3px;display:none;" onclick="removeTamanhos();">
                        <img src="media/menos.png" border="0px"/>
                    </a>
                    <a id="mais" title="Mais tamanhos" style="cursor:hand;cursor:pointer;float:right;padding:3px;" onclick="adicionaTamanhos();">
                        <img src="media/mais.png" border="0px"/>
                    </a><br /><br />
  
                    <label>Converter imagens:</label>
                        <input type="checkbox" id="converter" name="converter" onchange="exibe_oculta('div_converterPara');" value='1' title="Marque para definir que deseja converter as imagens"/>
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
                </div>
                    <a id="menos_zips" title="Menos arquivos" style="cursor:hand;cursor:pointer;float:left;padding:3px;display:none;" onclick="removeCampoZipField();">
                        <img src="media/menos.png" border="0px"/>
                    </a>
                    <a id="mais_zips" title="Mais arquivos" style="cursor:hand;cursor:pointer;float:right;padding:3px;" onclick="add_zipField();">
                        <img src="media/mais.png" border="0px"/>
                    </a><br /><br />
            </fieldset>
            <fieldset class="direita">
                <legend>
                    Imagens:
                </legend>
                <div id="multiupload">
                    <!-- Campos de imagem -->
                    <!--<script>add_fileField();</script>-->
                    <div id="div_imagens">
                        <input type="file" id="file_upload" name="file_upload" style="margin-left: 2px;"/>
                    </div>
                    <input type="hidden" name="diretorio_multiupload" value="<?=$destino;?>"/>
                </div>
            </fieldset>
            <br />
            <center>
                <!--<input type="button" style="margin-left:300px;height:25px;width:120px;" value="Enviar" class="botao"/>-->
                <input type="submit" value="Enviar" class="button ok botao" name="enviaPadrao" onclick="$('#file_upload').uploadifyUpload();" style="height:25px;width:120px;margin-left:333px;"/>
                <input type="hidden" name="enviado" value="1"/>
            </center>
        </form>
        <!-- Adiciona os primeiros campos de tamanho -->
        <script>adicionaTamanhos();</script>
        <!-- Adiciona o primeiro campo de arquivos .zip -->
        <script>add_zipField();</script>
    </body>
</html>
<?php
// Após feito o post
if ( (isset($_POST)) && ($_POST['enviado'] == 1) )
{
    try
    {
        // Obtém as imagens "upadas"
        $fotos = array();//$utils->arrumaArrayFiles($_FILES['imagens']);
        $arquivos_zip = (array)$utils->arrumaArrayFiles($_FILES['compactadas']);

        // Caso tenha aqruivos comprimidos, obtém as imagens deletes
        if ( count($arquivos_zip) > 0 )
        {
            // Percorre os arquivos .zip
            foreach ( $arquivos_zip as $arquivo_zip )
            {
                // Obtém as imagens compactadas
                $fotos_compactadas = $zip->obterFotosCompactadas($dirDescompactadas, $arquivo_zip);
                $fotos = array_merge_recursive($fotos, $fotos_compactadas);
            }
        }

        // Obtém as fotos enviadas com o multiupload
        $diretorio_multiupload = $_POST['diretorio_multiupload'];
        $fotos_multiUpload = $utils->obterArquivos($diretorio_multiupload);
        if ( count($fotos_multiUpload) > 0 )
        {
            $fotos = array_merge_recursive($fotos, $fotos_multiUpload);
        }

        // Opção de conversão de formato de imagem
        $converterPara = ($_POST['converter']) ? $_POST['converterPara'] : NULL;

        // Opção de nome padrão
        $nome_padrao = (strlen($_POST['nome_padrao']) > 0) ? $_POST['nome_padrao'] : NULL;

        // Opção de miniatura
        $posicao_miniatura = (strlen($_POST['miniatura'][0]) > 0) ? $_POST['miniatura'][0] : NULL;

        // Converte/redimensiona
        $imagem = new imagem($fotos, $_POST['altura'], $_POST['largura'], $dir, $converterPara, $nome_padrao, $posicao_miniatura);
        $imagem->gerarImagens();

        // Imagens prontas
        $novasImagens = $imagem->obterDiretorioNovasImagens();

        // Compacta a pasta com as imagens
        $compactadas = $zip->compactarImagens($novasImagens, $dirCompactadas);

        if ( strlen($novasImagens) > 0 )
        {
            echo "<script>informacao('Imagens redimensionadas com sucesso!');</script>";
            echo "<br /><a href=\"listarimagens.php?path=$novasImagens\" target=\"_blank\">Clique aqui</a> para vê-las.<br /> Ou <a href=\"compactadas.php?path=$compactadas\" target=\"_blank\">clique aqui</a> para fazer o download.";
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
