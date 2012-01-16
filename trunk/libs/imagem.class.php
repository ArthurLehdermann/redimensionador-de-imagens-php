<?php
require_once('utils.class.php');

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
    const ERRO_CRIAR_NOVA_IMAGEM_GD = 'Não foi possível inicializar nova imagem GD.';
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
    private $nome_padrao = null;
    private $posicao_miniatura = null;
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
    }

    public function obterDiretorioNovasImagens()
    {
        return $this->novoDiretorio;
    }

    /**
     * Método que gera as novas imagens
     */
    public function gerarImagens()
    {
        $utils = new utils();

        if ( count($this->imagens) > 0 )
        {
            // Obtém o nome do diretório onde serão salvas as imagens redimensionadas
            $pasta = $utils->gerarNomeDiretorio($this->pasta);

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
                foreach ( $this->imagens as $count => $imagem )
                {
                    // Extensão da imagem
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
                        $nome_novo .= $utils->removeSpecialChars($nome);
                    }
                    $nome_novo .= '.'.$extensao;

                    flog('---------------------------');
                    flog('Gerando imagem: '.$nome_novo);
                    flog('com largura máxima: '.$larguraMax);
                    flog('e altura máxima: '.$alturaMax);
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
        flog('nova largura: '.$largura);
        flog('nova altura: '.$altura);

        // Cria nova imagem
        if ( $nova = imageCreateTrueColor($largura, $altura) )
        {
            imageAlphaBlending($nova, false);

            // Mantém transparência para PNG
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
        }
        else
        {
            throw new Exception(self::ERRO_CRIAR_NOVA_IMAGEM_GD);
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

    /**
     * Obtém a extensão do arquivo a partir do MIME
     * @param @image Diretório de uma imagem
     * @return string $extensao
     */
    public function obtem_extensao($image)
    {
        $mime_type = image_type_to_mime_type(exif_imagetype($image));

        if ( $mime_type == 'image/jpeg' ) { $extensao = 'jpg'; }
        if ( $mime_type == 'image/png' ) { $extensao = 'png'; }
        if ( $mime_type == 'image/gif' ) { $extensao = 'gif'; }
        if ( $mime_type == 'image/bmp' ) { $extensao = 'bmp'; }

        return $extensao;
    }
}
?>
