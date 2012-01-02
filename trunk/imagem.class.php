<?php
/**
 * Classe para trabalhar com redimensionamento de imagens
 * Trabalha com exce��es(try...catch)
 *
 * @author Arthur Lehdermann [alehdermann@univates.br]
 */
class imagem
{
    /**
     * Mensagens de erro:
     */
    const ERRO_SEM_IMAGEM = 'Voc� precisa informar ao menos uma imagem para ser redimensionada.';
    const ERRO_FORMATO_NAO_SUPORTADO = 'O formato <b>$imagemType</b> infelizmente ainda n�o � suportado.';
    const ERRO_CRIAR_NOVA_IMAGEM_GD = 'N�o foi poss�vel inicializar nova imagem GD.';
    const ERRO_CRIAR_NOVA_IMAGEM = 'Erro ao criar imagem tempor�ria $n.';
    const ERRO_LIBERA_MEMORIA = 'Erro ao apagar imagem tempor�ria $n.';
    const ERRO_CONVERTER = 'Desculpe, mas infelizmente ainda n�o � poss�vel converter as imagens para o formato <b>$converterPara</b>.';

    /**
     * Atributos da classe
     */
    private $imagens;
    private $alturas = array();
    private $larguras = array();
    private $pasta = '';
    private $converterPara = null;
    private $nome_padrao = null;
    private $posicao_miniatura = null;
    private $novoDiretorio;

    /**
     * M�todo construtor da classe que redimensiona imagens
     *
     * @author Arthur Lehdermann [alehdermann@univates.br]
     *
     * @param $imagens Array de array associativo com as imagens(estilo $_FILES). Par�metro obrigat�rio.
     * Para alterar o nome da nova imagem, existe a possibilidade de passar um novo nome na posi��o 'novo_nome'.
     * @param $alturas Array com as alturas das imagens (isto define quantos redimensionamentos ser�o feitos)
     * @param $larguras Array com as larguras das imagens (isto define quantos redimensionamentos ser�o feitos)
     * @param $pasta Diretrio onde as imagens ser�o "jogadas" (padr�o 'media' ou 'imagens'). N�o precisa '/' no fim.
     * @param $converterPara Converter para 'jpg' ou para 'png'
     * @return String nome do diret�rio onde ficaram as imagens redimensionadas
     */
    public function __construct($imagens, $alturas=array(), $larguras=array(), $pasta=null, $converterPara=null, $nome_padrao=null, $posicao_miniatura=null)
    {
        // Popula
        $this->imagens = $imagens;
        $this->alturas = $alturas;
        $this->larguras = $larguras;
        $this->pasta = $pasta;
        $this->converterPara = $converterPara;
        $this->nome_padrao = trim($nome_padrao);
        $this->posicao_miniatura = $posicao_miniatura;

        // Gera as novas imagens
        $this->gerarImagens();
    }

    public function obterDiretorioNovasImagens()
    {
        return $this->novoDiretorio;
    }

    /**
     * M�todo que gera as novas imagens
     */
    private function gerarImagens()
    {
        if ( count($this->imagens) > 0 )
        {
            // Obt�m o nome do diret�rio onde ser�o salvas as imagens redimensionadas
            $pasta = gerarNomeDiretorio($this->pasta);
            // Cria um novo diret�rio

            exec("mkdir $pasta");

            // Quantidade de imagens a serem redimensionadas
            $quantidade = (count($this->alturas) > count($this->larguras)) ? count($this>alturas) : count($this->larguras);

            // Redimensiona as imagens para cada tamanho
            for ( $i=0; $i < $quantidade; $i++ )
            {
                // Dimens�es m�ximas
                $alturaMax = $this->alturas[$i];
                $larguraMax = $this->larguras[$i];

                // Percorre cada imagem
                foreach ( $this->imagens as $count => $imagem )
                {
                    // Extens�o da imagem
                    $nomeArray = explode('.', $imagem['name']);
                    $extensao = trim($nomeArray[count($nomeArray)-1]);
                    unset($nomeArray[count($nomeArray)-1]);
                    $nome = trim(implode('.', $nomeArray));

                    // Altera o nome
                    $nome_novo = '';
                    if ( !is_null($this->posicao_miniatura) &&
                         ($this->posicao_miniatura == ($i+1)) )
                    {
                        $nome_novo = 'thumb_';
                    }

                    if ( strlen(trim($this->nome_padrao)) > 0 )
                    {
                        if ( strpos($this->nome_padrao, '%n') )
                        {
                            $nome_novo .= str_replace('%n', ($count+1), $this->nome_padrao);
                        }
                        else
                        {
                            $nome_novo .= $this->nome_padrao.($count+1);
                        }
                    }
                    else
                    {
                        if ( $quantidade > 2 || (($quantidade > 1) && (is_null($this->posicao_miniatura))) )
                        {
                            $nome_novo .= ($i+1).'-';
                        }
                        $nome_novo .= removeSpecialChars($nome);
                    }
                    $nome_novo .= '.'.$extensao;

                    flog('---------------------------');
                    flog('Gerando imagem: '.$nome_novo);
                    flog('com largura m�xima: '.$larguraMax);
                    flog('e altura m�xima: '.$alturaMax);
                    $this->gerarImagem($imagem, $alturaMax, $larguraMax, $pasta, $nome_novo, $this->converterPara);
                    flog('Imagem gerada com sucesso!');
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
     * Gera a imagem com as dimens�es definidas
     * @param $imagem Array associativo com as imagens(estilo $_FILES). Par�metro obrigat�rio.
     * Para alterar o nome da nova imagem, existe a possibilidade de passar um novo nome na posi��o 'novo_nome'.
     * @param $altura Altura m�xima da imagens. Caso em n�o informado, mant�m altura original.
     * @param $largura Largura da imagem. Caso em n�o informado, mant�m a largura original.
     * @param $pasta Diretrio onde a imagem ser� salva (padr�o 'media' ou 'imagens'). N�o precisa '/' no fim.
     * @param $converterPara Converter para 'jpg' ou para 'png'. Padr�o NULL (mant�m formato original.
     * @return String nome do diret�rio completo da imagem redimensionada.
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
                // Mant�m a transpar�ncia (a anima��o � perdida)
                return $this->criarImagemGIF($imagem, $alturaMax, $larguraMax, $pasta, $nome);
            }
        }
        // BMP
        elseif ( $imagem['type'] == 'image/bmp' )
        {
            // Ser� convertida para PNG
            $img = $this->criarImagemBMP($imagem['tmp_name']);
        }
        // Formato n�o suportado
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
        flog('nova largura: '.$largura);
        flog('nova altura: '.$altura);

        // Cria nova imagem
        if ( $nova = imageCreateTrueColor($largura, $altura) )
        {
            imageAlphaBlending($nova, false);

            // Mant�m transpar�ncia para PNG
            $isTrueColor = imageIsTrueColor($img);
            if ( $isTrueColor )
            {
                imageSaveAlpha($nova, true);
            }
            else
            {
                $transparent = imageColorAllocateAlpha($nova, 0, 0, 0, 127);
                imageFill($nova, 0, 0, $transparent);
                imageSaveAlpha($nova, true);
                imageAlphaBlending($nova, true);
            }

            // Copia a imagem para o diret�rio destino ($this->pasta)
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
        }
        else
        {
            throw new Exception(self::ERRO_CRIAR_NOVA_IMAGEM_GD);
        }

        /*
         * Libera mem�ria associada as imagens
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

        flog('salva em: '.$pasta.'/'.$nome);
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

        // Copia a Imagem Original Alterada substituindo a Imagem Original Padr�o
        imageGif($img_temp, $pasta."/".$nome, 100);
    }

    /**
     * Obt�m a altura e largura da imagem original
     * @param $imagem Objeto imagem do PHP
     * @return Array associativo de duas posi��es ('altura' e 'largura')
     */
    public function obterAlturaLargura($imagem)
    {
        $altura = imagesY($imagem);
        $largura = imagesX($imagem);

        if ( !$altura )
        {
            throw new Exception('N�o foi poss�vel obter a altura da imagem.');
        }
        if ( !$largura )
        {
            throw new Exception('N�o foi poss�vel obter a largura da imagem.');
        }

        return array( 'altura' => (int)$altura,
                      'largura' => (int)$largura );
    }

    /**
     * Calcula as novas dimens�es da imagem
     * @param $altura Altura da imagem
     * @param $largura Largura da umagem
     * @param $alturaMax Altura m�xima
     * @param $larguraMax Largura m�xima
     * @return Array associativo de duas posi��es ('altura' e 'largura')
     */
    public function calcularNovasDimensoes($altura, $largura, $alturaMax, $larguraMax)
    {
        // Caso definido altura m�xima
        if ( $alturaMax )
        {
            // E tamb�m definida largura m�xima
            if ( $larguraMax )
            {
                $novaLargura = ($alturaMax * $largura) / $altura;
                if ( $novaLargura > $larguraMax)
                {
                    $alturaMax = ($larguraMax * $altura) / $largura;
                }
                else
                {
                    $larguraMax = $novaLargura;
                }
            }
            else
            {
                // Somente altura m�xima definida
                $larguraMax = ($alturaMax * $largura) / $altura;
            }
        }
        elseif ( $larguraMax )
        {
            // Somente largura m�xima definida
            $alturaMax = ($larguraMax * $altura) / $largura;
        }
        else
        {
            // N�o foi definido limites de tamanho
            // Mant�m dimens�es originais
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
 * Gera um nome de diret�rio aleat�rio
 * @recursive
 * @return String
 */
function gerarNomeDiretorio($baseDir)
{
    $data = date('Y-m-d');
    // Cria uma pasta por dia
    if ( !in_array($data, (array)scandir($baseDir)) )
    {
        exec('mkdir '.$baseDir.'/'.$data);
    }


    // Gera um nomealeat�rio 6numeros
    $nome = getRandomNumbers();

    // Caso j� exista gera outro
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
 * Compacta as imagens de um diret�rio
 * @param $dir Diret�rio das imagens a serem compactadas
 * @param $destDir Destino do arquivo.zip
 */
function compactarImagens($dir, $destDir, $nomeDoArquivo)
{
    flog('');
    flog('Compactando imagens...');
    if ( strlen($nomeDoArquivo) > 0 )
    {
        $nomeDoArquivo = 'imagens_redimensionadas';
    }
    $newFile = $destDir.'/'.$nomeDoArquivo.getRandomNumbers().'.zip';

    // Criando o pacote
    $zip = new ZipArchive();
    $criou = $zip->open($newFile, ZipArchive::CREATE);
    if ( $criou )
    {
        // Adicionando as imagens redimensionadas
        foreach ( (array)glob($dir.'/{*jpg,*png,*gif}', GLOB_BRACE) as $file )
        {
            // Copiando arquivo
            $arrayName = explode('/', $file);
            $zip->addFile($file, $arrayName[count($arrayName)-1]);
        }

        // Salvando o arquivo
        $zip->close();
    }
    else
    {
        throw new Exception('Erro: '.$criou);
    }

    flog('imagens compactadas: '.$newFile);
    return $newFile;
}

/**
 * Descompacta as imagens
 * @param $dir Destino das imagens
 * @param $arquivo Arquivo .zip (caminho)
 */
function descompactarImagens($dir, $arquivo)
{
    flog('Descompactando imagens de: '.$arquivo['tmp_name']);
    $dest_dir = $dir.'/';
    if ( strlen($arquivo['tmp_name']) > 0 )
    {
        $arquivo = $arquivo['tmp_name'];
    }

    $zip = new ZipArchive();
    if ( $zip->open($arquivo) )
    {
        $zip->extractTo($dest_dir);
        $zip->close();
        flog('Removendo o que n�o � imagem...');
        removeLixo($dest_dir);
        flog('removido "lixo".');
    }
    else
    {
        throw new Exception('N�o foi poss�vel abrir o arquivo comprimido com as imagens.');
    }

    flog('imagens descompactadas em: '.$dest_dir);
}

/**
 * Remove tudo que N�O for: .jpg, .jpeg, .bmp, .gif e .png
 */
function removeLixo($dir)
{
    // Tudo que n�o for imagem ser� deletado
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
                // Apaga o que n�o � imagem
                exec('rm -Rf '.$file);
            }
            else
            {
                // Corrige o nome do arquivo e a extensao 
                $array = explode('/', $file);
                $nomeDoArquivo = $array[count($array)-1];
                unset($array[count($array)-1]);
                $base_dir = implode('/', $array);

                // Somente o nome do arquivo (sem extens�o)
                $array = explode('.', $nomeDoArquivo);
                unset($array[count($array)-1]);
                if ( strlen($array[0]) > 0 )
                {
                    $nomeDoArquivo = implode('.', $array);
                }
                $novoNome = removeSpecialChars($nomeDoArquivo);

                // Extens�o
                $fileNew = $base_dir.'/'.$novoNome.'.'.obtem_extensao($file);

                exec('mv '.str_replace(' ', '\\ ', $file).' '.$fileNew);
            }
        }
    }
}

/**
 * Obt�m a extens�o do arquivo a partir do MIME
 */
function obtem_extensao($image)
{
    $mime_type = image_type_to_mime_type(exif_imagetype($image));
    if ( $mime_type == 'image/jpeg' ) { $extensao = 'jpg'; }
    if ( $mime_type == 'image/png' ) { $extensao = 'png'; }
    if ( $mime_type == 'image/gif' ) { $extensao = 'gif'; }
    if ( $mime_type == 'image/bmp' ) { $extensao = 'bmp'; }

    return $extensao;
}

/**
 * L� as imagens que est�o compactadas. Retorna um array associativo estilo $_FILES.
 */
function obterFotosCompactadas($dir, $arquivo)
{
    $dir = gerarNomeDiretorio($dir);
    // Cria um novo diret�rio
    exec("mkdir $dir");

    descompactarImagens($dir, $arquivo);

    return obterArquivos($dir);
}

/**
 * Retorna um array com todos os arquivos de um diret�rio
 * @param $dir Diret�rio de onde ser� lido os arquivos
 * @return array
 */
function obterArquivos($dir)
{
    if ( substr($dir, -1) != '/' )
    {
        $dir .= '/';
    }

    $imagens = array();
    // J� descompactou, agora monta um array com todas elas (aqui se perde a hierarquia de diret�rios que existia dentro do .zip)
    foreach ( (array)glob($dir."*", GLOB_BRACE) as $k => $file ) 
    {
        if ( is_dir($file) )
        {
            $imagens = array_merge($imagens, obterArquivos($file));
        }
        else
        {
            $nomeArray = explode('/',$file);
            $imagens[$k]['name'] = $nomeArray[count($nomeArray)-1];
            $imagens[$k]['type'] = image_type_to_mime_type(exif_imagetype($file)); // Obt�m o "type"
            $imagens[$k]['tmp_name'] = $file;
        }
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
     * A fun��o "strtr" substitui os caracteres acentuados pelos n�o acentuados.
     * A fun��o "ereg_replace" utiliza uma express�o regular que remove todos os
     * caracteres que n�o s�o letras, n�meros e s�o diferentes de "_" (underscore).
     */
    $newText = ereg_replace("[^a-zA-Z0-9_-]", "", strtr($oldText, "�������������������������� ", "aaaaeeiooouucAAAAEEIOOOUUC_"));

    if ( !(strlen($newText) > 0) )
    {
        $newText = 'nome_invalido-'.getRandomNumbers().getRandomNumbers();
    }

    return $newText;
}

/**
 * Semelhante ao var_dump() do PHP, armazena o valor da vari�vel em:
 * /tmp/var_dump.
 * Existe um script chamado flog.sh que l� em tempo real este arquivo e exibe em tela.
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
