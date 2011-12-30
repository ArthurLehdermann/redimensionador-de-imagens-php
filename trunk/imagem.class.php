<?php
/**
 * Classe para trabalhar com redimensionamento de imagens
 * Trabalha com exceções(try...catch)
 *
 * @author Arthur Lehdermann [alehdermann@univates.br]
 */
class imagem
{
    /**
     * Mensagens de erro:
     */
    const ERRO_SEM_IMAGEM = 'Você precisa informar ao menos uma imagem para ser redimensionada.';
    const ERRO_FORMATO_NAO_SUPORTADO = 'O formato <b>$imagemType</b> infelizmente ainda não é suportado.';
    const ERRO_CRIAR_NOVA_IMAGEM = 'Erro ao criar imagem temporária $n.';
    const ERRO_LIBERA_MEMORIA = 'Erro ao apagar imagem temporária $n.';
    const ERRO_CONVERTER = 'Desculpe, mas infelizmente ainda não é possível converter as imagens para o formato <b>$converterPara</b>.';

    /**
     * Atributos da classe
     */
    private $imagens;
    private $alturas = array();
    private $larguras = array();
    private $pasta = '';
    private $converterPara = null;

    private $novoDiretorio;

    /**
     * Método construtor da classe que redimensiona imagens
     *
     * @author Arthur Lehdermann [alehdermann@univates.br]
     *
     * @param $imagens Array de array associativo com as imagens(estilo $_FILES). Parâmetro obrigatório.
     * Para alterar o nome da nova imagem, existe a possibilidade de passar um novo nome na posição 'novo_nome'.
     * @param $alturas Array com as alturas das imagens (isto define quantos redimensionamentos serão feitos)
     * @param $larguras Array com as larguras das imagens (isto define quantos redimensionamentos serão feitos)
     * @param $pasta Diretrio onde as imagens serão "jogadas" (padrão 'media' ou 'imagens'). Não precisa '/' no fim.
     * @param $converterPara Converter para 'jpg' ou para 'png'
     * @return String nome do diretório onde ficaram as imagens redimensionadas
     */
    public function __construct($imagens, $alturas=array(), $larguras=array(), $pasta=null, $converterPara=null)
    {
        // Popula
        $this->imagens = $imagens;
        $this->alturas = $alturas;
        $this->larguras = $larguras;
        $this->pasta = $pasta;
        $this->converterPara = $converterPara;

        // Gera as novas imagens
        $this->gerarImagens();
    }

    public function obterDiretorioNovasImagens()
    {
        return $this->novoDiretorio;
    }

    /**
     * Método que gera as novas imagens
     */
    private function gerarImagens()
    {
        if ( count($this->imagens) > 0 )
        {
            // Obtém o nome do diretório onde serão salvas as imagens redimensionadas
            $pasta = gerarNomeDiretorio($this->pasta);

            // Cria um novo diretório
            exec("mkdir $pasta");

            // Quantidade de imagens a serem redimensionadas
            $quantidade = (count($this->alturas) > count($this->larguras)) ? count($this>alturas) : count($this->larguras);

            // Redimensiona as imagens para cada tamanho
            for ( $i=0; $i < $quantidade; $i++ )
            {
                // Dimensões máximas
                $alturaMax = $this->alturas[$i];
                $larguraMax = $this->larguras[$i];

                // Percorre cada imagem
                foreach ( $this->imagens as $imagem )
                {
                    // Extensão da imagem
                    $nomeArray = explode('.', $imagem['name']);
                    $extensao = trim($nomeArray[count($nomeArray)-1]);
                    unset($nomeArray[count($nomeArray)-1]);
                    $nome = trim(implode('.', $nomeArray));

                    // Altera o nome da imagem
                    if ( strlen($imagem['novo_nome']) > 0 )
                    {
                        $nome = $imagem['novo_nome'];
                    }

                    // Altera o nome
                    $nome_novo = '';
                    if ( $quantidade > 1 )
                    {
                        $nome_novo .= ($i+1).'-';
                    }
                    $nome_novo .= removeSpecialChars($nome).'.'.$extensao;
                    $this->gerarImagem($imagem, $alturaMax, $larguraMax, $pasta, $nome_novo, $this->converterPara);
                }
            }
        }
        else
        {
            throw new Exception(self::ERRO_SEM_IMAGEM);
        }

        $this->novoDiretorio = $pasta;
    }

    /**
     * Gera a imagem com as dimensões definidas
     * @param $imagem Array associativo com as imagens(estilo $_FILES). Parâmetro obrigatório.
     * Para alterar o nome da nova imagem, existe a possibilidade de passar um novo nome na posição 'novo_nome'.
     * @param $altura Altura máxima da imagens. Caso em não informado, mantém altura original.
     * @param $largura Largura da imagem. Caso em não informado, mantém a largura original.
     * @param $pasta Diretrio onde a imagem será salva (padrão 'media' ou 'imagens'). Não precisa '/' no fim.
     * @param $converterPara Converter para 'jpg' ou para 'png'. Padrão NULL (mantém formato original.
     * @return String nome do diretório completo da imagem redimensionada.
     */
    public function gerarImagem($imagem, $alturaMax, $larguraMax, $pasta, $nome, $converterPara)
    {
        /*
         * Verifica o tipo da imagem e "importa-a"
         */
        // JPG / JPEG
        if ( $imagem['type'] == 'image/jpeg' )
        {
            $img = imageCreateFromJPEG($imagem['tmp_name']);
        }
        // PNG
        elseif ( $imagem['type'] == 'image/png' )
        {
            $img = imageCreateFromPNG($imagem['tmp_name']);
        }
        // GIF
        elseif ( $imagem['type'] == 'image/gif' )
        {
            // Se for converter
            if ( !is_null($converterPara) )
            {
                $img = imageCreateFromGIF($imagem['tmp_name']);
            }
            else
            {
                // Mantém a transparência (a animação é perdida)
                return $this->criarImagemGIF($imagem, $alturaMax, $larguraMax, $pasta, $nome);
            }
        }
        // BMP
        elseif ( $imagem['type'] == 'image/bmp' )
        {
            // Será convertida para PNG
            $img = $this->criarImagemBMP($imagem['tmp_name']);
        }
        // Formato não suportado
        else
        {
            $msg = self::ERRO_FORMATO_NAO_SUPORTADO;
            $msg = str_replace('$imagemType', $imagem['type'], $msg);
            throw new Exception($msg);
        }

        // Calcula nova altura/largura
        $dimensoesOriginais = $this->obterAlturaLargura($img);
        $alturaOriginal = $dimensoesOriginais['altura'];
        $larguraOriginal = $dimensoesOriginais['largura'];

        $novasDimensoes = $this->calcularNovasDimensoes($alturaOriginal, $larguraOriginal, $alturaMax, $larguraMax);
        $altura = $novasDimensoes['altura'];
        $largura = $novasDimensoes['largura'];

        // Mantém transparência para PNG
        $isTrueColor = imageIsTrueColor($img);
        if ( $isTrueColor )
        {
            $nova = imageCreateTrueColor($largura, $altura);
            imageAlphaBlending($nova, false);
            imageSaveAlpha($nova, true);
        }
        else
        {
            $nova = imageCreate($largura, $altura);
            imageAlphaBlending($nova, false);
            $transparent = imageColorAllocateAlpha($nova, 0, 0, 0, 127);
            imageFill($nova, 0, 0, $transparent);
            imageSaveAlpha($nova, true);
            imageAlphaBlending($nova, true);
        }

        // Copia a imagem para o diretório destino ($this->pasta)
        $msg = self::ERRO_CRIAR_NOVA_IMAGEM;
        if ( !imagecopyresampled($nova, $img, 0, 0, 0, 0, $largura, $altura, $larguraOriginal, $alturaOriginal) )
        {
            $msg = str_replace('$n', '1', $msg);
            throw new Exception($msg);
        }
        if ( !is_null($converterPara) )
        {
            if ( $converterPara == 'jpg' )
            {
                $nomeArray = explode('.', $nome);
                $nomeArray[count($nomeArray)-1] = 'jpg';
                $nome = implode('.', $nomeArray);
                if ( !imagejpeg($nova, $pasta."/".$nome) )
                {
                    $msg = str_replace('$n', '2', $msg);
                    throw new Exception($msg);
                }
            }
            elseif( $converterPara == 'png' )
            {
                $nomeArray = explode('.', $nome);
                $nomeArray[count($nomeArray)-1] = 'png';
                $nome = implode('.', $nomeArray);
                if ( !imagepng($nova, $pasta."/".$nome) )
                {
                    $msg = str_replace('$n', '3', $msg);
                    throw new Exception($msg);
                }
            }
            else
            {
                $msg = self::ERRO_CONVERTER;
                $msg = str_replace('$converterPara', $converterPara, $msg);
                throw new Exception($msg);
            }
        }
        else
        {
            // JPG / JPEG
            if ( $imagem['type'] == 'image/jpeg' )
            {
                if ( !imagejpeg($nova, $pasta."/".$nome) )
                {
                    $msg = str_replace('$n', '4', $msg);
                    throw new Exception($msg);
                }
            }
            // PNG
            elseif ( $imagem['type'] == 'image/png' )
            {
                if ( !imagepng($nova, $pasta."/".$nome) )
                {
                    $msg = str_replace('$n', '5', $msg);
                    throw new Exception($msg);
                }
            }
            // GIF
            elseif ( $imagem['type'] == 'image/gif' )
            {
                if ( !imagegif($nova, $pasta."/".$nome) )
                {
                    $msg = str_replace('$n', '6', $msg);
                    throw new Exception($msg);
                }
            }
            // BMP - converte para png
            elseif ( $imagem['type'] == 'image/bmp' )
            {
                $nomeArray = explode('.', $nome);
                $nomeArray[count($nomeArray)-1] = 'png';
                $nome = implode('.', $nomeArray);
                if ( !imagepng($nova, $pasta."/".$nome) )
                {
                    $msg = str_replace('$n', '7', $msg);
                    throw new Exception($msg);
                }
            }
        }

        /*
         * Libera memória associada as imagens
         */
        $msg = self::ERRO_LIBERA_MEMORIA;
        if ( !imagedestroy($img) )
        {
            $msg = str_replace('$n', '1', $msg);
            throw new Exception($msg);
        }
        if ( !imagedestroy($nova) )
        {
            $msg = str_replace('$n', '2', $msg);
            throw new Exception($msg);
        }

        return $pasta.'/'.$nome;
    }

    private function criaImagemGif($imagem, $alturaMax, $larguraMax, $pasta, $nome)
    {
        $img = imagecreatefromgif($imagem["tmp_name"]);

        // Pega o Tamanho da Imagem
        $largura = imagesX($img);
        $altura = imagesY($img);

        // Define a Largura para a Imagem
        $novaLargura = $largura;

        // Faz o Calculo para Definir o Tamanho da Imagem
        $ratio = $novaLargura / $largura;
        $novaLargura = $altura * $ratio;

        // Cria uma Imagem Temporaria com o Novo Tamanho
        $img_temp = imageCreateTrueColor($novaLargura, $novaAltura);

        // Muda o tamanho da Imagem Original de Acordo com a Imagem Temporaria
        imageCopyResampled($img_temp, $img, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

        // Copia a Imagem Original Alterada substituindo a Imagem Original Padrão
        imageGif($img_temp, $pasta."/".$nome, 100);
    }

    /**
     * Obtém a altura e largura da imagem original
     * @param $imagem Objeto imagem do PHP
     * @return Array associativo de duas posições ('altura' e 'largura')
     */
    public function obterAlturaLargura($imagem)
    {
        $altura = imagesY($imagem);
        $largura = imagesX($imagem);

        if ( !$altura )
        {
            throw new Exception('Não foi possível obter a altura da imagem.');
        }
        if ( !$largura )
        {
            throw new Exception('Não foi possível obter a largura da imagem.');
        }

        return array( 'altura' => (int)$altura,
                      'largura' => (int)$largura );
    }

    /**
     * Calcula as novas dimensões da imagem
     * @param $altura Altura da imagem
     * @param $largura Largura da umagem
     * @param $alturaMax Altura máxima
     * @param $larguraMax Largura máxima
     * @return Array associativo de duas posições ('altura' e 'largura')
     */
    public function calcularNovasDimensoes($altura, $largura, $alturaMax, $larguraMax)
    {
        // Caso definido altura máxima
        if ( $alturaMax )
        {
            // E também definida largura máxima
            if ( $larguraMax )
            {
                // Largura e altura máxima definidas
                // Diferença da altura da imagem para a altura máxima
                $difAltura = $alturaMax-$altura;
                if ( $difAltura < 0 )
                {
                    // Caso dê a diferença negativa, inverte o sinal
                    $difAltura = $diffAltura * (-1);
                }
                // Diferença da largura da imagem para a largura máxima
                $difLargura = $larguraMax-$largura;
                if ( $difLargura < 0 )
                {
                    $difLargura = $difLargura * (-1);
                }

                // Calcula novas dimensoes
                if ( $dfLargura > $difAltura )
                {
                    $alturaMax = ($larguraMax * $altura) / $largura;
                }
                else
                {
                    $larguraMax = ($alturaMax * $largura) / $altura;
                }
            }
            else
            {
                // Somente altura máxima definida
                $larguraMax = ($alturaMax * $largura) / $altura;
            }
        }
        elseif ( $larguraMax )
        {
            // Somente largura máxima definida
            $alturaMax = ($larguraMax * $altura) / $largura;
        }
        else
        {
            // Não foi definido limites de tamanho
            // Mantém dimensões originais
            $alturaMax = $altura;
            $larguraMax = $largura;
        }

        return array( 'altura' => (int)$alturaMax,
                      'largura' => (int)$larguraMax );
    }

    /*********************************************/
    /* Fonction: ImageCreateFromBMP              */
    /* Author:   DHKold                          */
    /* Contact:  admin@dhkold.com                */
    /* Date:     The 15th of June 2005           */
    /* Version:  2.0B                            */
    /*********************************************/
    public function criarImagemBMP($filename)
    {
        //Ouverture du fichier en mode binaire
        if ( !$f1 = fopen($filename, "rb") )
        {
            return FALSE;
        }

        //1 : Chargement des ent?tes FICHIER
        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
        if ( $FILE['file_type'] != 19778 )
        {
            return FALSE;
        }

        //2 : Chargement des ent?tes BMP
        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
                      '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
                      '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
        $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
        if ( $BMP['size_bitmap'] == 0 )
        {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
        $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
        $BMP['decal'] = 4-(4*$BMP['decal']);
        if ( $BMP['decal'] == 4 )
        {
            $BMP['decal'] = 0;
        }

        //3 : Chargement des couleurs de la palette
        $PALETTE = array();
        if ($BMP['colors'] < 16777216 && $BMP['colors'] != 65536)
        {
            $PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
            #nei file a 16bit manca la palette,
        }

        //4 : Create the image
        $IMG = fread($f1,$BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
        $P = 0;
        $Y = $BMP['height']-1;
        while ( $Y >= 0 )
        {
            $X=0;
            while ( $X < $BMP['width'] )
            {
                if ( $BMP['bits_per_pixel'] == 24 )
                {
                    $COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
                }
                elseif ( $BMP['bits_per_pixel'] == 16 )
                {
                    $COLOR = unpack("v",substr($IMG,$P,2));
                    $blue  = (($COLOR[1] & 0x001f) << 3) + 7;
                    $green = (($COLOR[1] & 0x03e0) >> 2) + 7;
                    $red   = (($COLOR[1] & 0xfc00) >> 7) + 7;
                    $COLOR[1] = $red * 65536 + $green * 256 + $blue;
                }
                elseif ( $BMP['bits_per_pixel'] == 8 )
                {
                    $COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
                    $COLOR[1] = $PALETTE[$COLOR[1]+1];
                }
                elseif ( $BMP['bits_per_pixel'] == 4 )
                {
                    $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
                    if ( ($P*2)%2 == 0 )
                    {
                        $COLOR[1] = ($COLOR[1] >> 4 );
                    }
                    else
                    {
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1]+1];
                }
                elseif ( $BMP['bits_per_pixel'] == 1 )
                {
                    $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
                    if ( ($P*8)%8 == 0 )
                    {
                        $COLOR[1] = $COLOR[1] = $COLOR[1]>>7;
                    }
                    elseif ( ($P*8)%8 == 1 )
                    {
                        $COLOR[1] = ($COLOR[1] & 0x40)>>6;
                    }
                    elseif ( ($P*8)%8 == 2 )
                    {
                        $COLOR[1] = ($COLOR[1] & 0x20)>>5;
                    }
                    elseif ( ($P*8)%8 == 3 )
                    {
                        $COLOR[1] = ($COLOR[1] & 0x10)>>4;
                    }
                    elseif ( ($P*8)%8 == 4 )
                    {
                        $COLOR[1] = ($COLOR[1] & 0x8)>>3;
                    }
                    elseif ( ($P*8)%8 == 5 )
                    {
                        $COLOR[1] = ($COLOR[1] & 0x4)>>2;
                    }
                    elseif ( ($P*8)%8 == 6 )
                    {
                        $COLOR[1] = ($COLOR[1] & 0x2)>>1;
                    }
                    elseif ( ($P*8)%8 == 7 )
                    {
                        $COLOR[1] = ($COLOR[1] & 0x1);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1]+1];
                }
                else
                {
                    return FALSE;
                }

                imagesetpixel($res,$X,$Y,$COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P += $BMP['decal'];
        }

        //Fermeture du fichier
        fclose($f1);

        return $res;
    }
}

/**
 * Gera um nome de diretório aleatório
 * @recursive
 * @return String
 */
function gerarNomeDiretorio($baseDir)
{
    $data = date('Y-m-d');
    // Cria uma pasta por dia
    if ( !in_array($data, (array)scandir($baseDir)) )
    {
        exec('mkdir '.$basedir.'/'.$data);
    }

    // Gera um nomealeatório 6numeros
    $nome = getRandomNumbers();

    // Caso já exista gera outro
    if ( in_array($nome, (array)scandir($baseDir.'/'.$data)) )
    {
        $nome = gerarNomeDiretorio($baseDir);
    }
    else
    {
        $nome = $baseDir.'/'.$data.'/'.$nome;
    }

    return $nome;
}

/**
 * Get random numbers
 */
function getRandomNumbers()
{
    return rand(100,999).rand(100,999);
}

/**
 * Compacta as imagens de um diretório
 * @param $dir Destino das imagens a serem compactadas
 * @param $destDir Destino do arquivo.zip
 */
function compactarImagens($dir, $destDir, $nomeDoArquivo)
{
    if ( strlen($nomeDoArquivo) > 0 )
    {
        $nomeDoArquivo = 'imagens_redimensionadas';
    }
    $newFile = $destDir.'/'.$nomeDoArquivo.getRandomNumbers().'.zip';

    // Load the Library
    require("libs/zip.lib.php");

    // Generate a new object
    $zipfile = new zipFile($newFile);

    // Add a folder
    $zipfile->addDirContent("./");

    // Output the new zip file
    return $zipfile->file();
}

/**
 * Descompacta as imagens
 * @param $dir Destino das imagens
 * @param $arquivo Arquivo .zip (caminho)
 */
function descompactarImagens($dir, $arquivo)
{
    $dest_dir = $dir . '/';
    if ( strlen($arquivo['tmp_name']) > 0 )
    {
        $arquivo = $arquivo['tmp_name'];
    }

    $zip = new ZipArchive();
    if ( $zip->open($arquivo) )
    {
        $zip->extractTo($dest_dir);
        $zip->close();
        removeLixo($dest_dir);
    }
    else
    {
        throw new Exception('Não foi possível abrir o arquivo comprimido com as imagens.');
    }
}

/**
 * Remove tudo que NÃO for: .jpg, .jpeg, .bmp, .gif e .png
 */
function removeLixo($dir)
{
    // Tudo que não for imagem será deletado
    $valid_headers = array(
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp'
    );

    foreach ( (array)glob($dir.'*', GLOB_BRACE) as $file )
    {
        if ( is_dir($file) )
        {
            // Recursividade
            removeLixo($file.'/');
        }
        else
        {
            $mime_type = image_type_to_mime_type(exif_imagetype($file));
            if ( !in_array($mime_type, $valid_headers) )
            {
                flog('apagando: '.$file);
                exec('rm -Rf '.$file);
            }
            else
            {
                $array = explode('/', $file);
                $nomeDoArquivo = $array[count($array)-1];
                unset($array[count($array)-1]);
                $base_dir = implode('/', $array);

                $array = explode('.', $nomeDoArquivo);
                unset($array[count($array)-1]);
                $novoNome = removeSpecialChars(implode('.', $array));
                if ( $mime_type == 'image/jpeg' ) { $extensao = 'jpg'; }
                if ( $mime_type == 'image/png' ) { $extensao = 'png'; }
                if ( $mime_type == 'image/gif' ) { $extensao = 'gif'; }
                if ( $mime_type == 'image/bmp' ) { $extensao = 'bmp'; }
                $fileNew = $base_dir.'/'.$novoNome.'.'.$extensao;
                flog($file);
                flog($fileNew);
                exec('mv '.$file.' '.$fileNew);
            }
        }
    }
}

/**
 * Lê as imagens que estão compactadas. Retorna um array associativo estilo $_FILES.
 */
function obterFotosCompactadas($dir, $arquivo)
{
    $dir = gerarNomeDiretorio($dir);
    // Cria um novo diretório
    exec("mkdir $dir");
    
    descompactarImagens($dir, $arquivo);

    $imagens = array();
    foreach ( glob($dir.'/*.*') as $k => $img )
    {
        $nomeArray = explode('/',$img);
        $imagens[$k]['name'] = $nomeArray[count($nomeArray)-1];
        $imagens[$k]['type'] = image_type_to_mime_type(exif_imagetype($img)); // Obtém o "type"
        $imagens[$k]['tmp_name'] = $img;
    }

    return $imagens;
}

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
     * A função "strtr" substitui os caracteres acentuados pelos não acentuados.
     * A função "ereg_replace" utiliza uma expressão regular que remove todos os
     * caracteres que não são letras, números e são diferentes de "_" (underscore).
     */
    $newText = ereg_replace("[^a-zA-Z0-9_-]", "", strtr($oldText, "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ ", "aaaaeeiooouucAAAAEEIOOOUUC_"));

    if ( !(strlen($newText) > 0) )
    {
        $newText = 'nome_invalido'.getRandomNumbers().getRandomNumbers();
    }

    return $newText;
}

/**
 * Semelhante ao var_dump() do PHP, armazena o valor da variável em:
 * /tmp/var_dump.
 * Existe um script chamado flog.sh que lê em tempo real este arquivo e exibe em tela.
 *
 * @param: $1, $2, ..., $N: flog() pode receber quantos parametros forem necessarios.
 */
function flog()
{
    if ( file_exists('/tmp/var_dump') )
    {
        $numArgs = func_num_args();
        $dump = '';
        for($i = 0; $i < $numArgs; $i++)
        {
            $dump .= var_export(func_get_arg($i), true) . "\n";
        }

        $f = fopen('/tmp/var_dump', 'w');
        fwrite($f, $dump);
        fclose($f);
    }
}

/**
 * Semelhante ao var_dump() do PHP, mas com identacao. vd() coloca as tags <pre> do HTML
 * facilitando a visualizacao do valor contido na variavel. 
 *
 * @param: $1, $2, ..., $n: recebe N parametros.
 * Se o ultimo parametro for TRUE, executa exit()
 */
function vd()
{
    $numArgs = func_num_args();
    if ( $numArgs > 1 && is_bool(func_get_arg($numArgs - 1)) )
    {
        $numArgs--;
        $exit = func_get_arg($numArgs);
    }
    else
    {
        $exit = false;
    }

    echo ('<div align="left"><pre>');
    for($i = 0; $i < $numArgs; $i++)
    {
        var_dump(func_get_arg($i));
    }

    echo ('</pre></div>');

    if ( $exit )
    {
        exit();
    }
}
?>
