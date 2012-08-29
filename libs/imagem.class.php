<?php
require_once('utils.class.php');
require_once('canvas/canvas.php');

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
     * @param Array $imagens Array de array associativo com as imagens(estilo $_FILES). Par�metro obrigat�rio.
     * Para alterar o nome da nova imagem, existe a possibilidade de passar um novo nome na posi��o 'novo_nome'.
     * @param Array $alturas Array com as alturas das imagens (isto define quantos redimensionamentos ser�o feitos)
     * @param Array $larguras Array com as larguras das imagens (isto define quantos redimensionamentos ser�o feitos)
     * @param String $pasta Diretrio onde as imagens ser�o "jogadas" (padr�o 'media' ou 'imagens'). N�o precisa '/' no fim.
     * @param String $converterPara Converter para 'jpg' ou para 'png'
     * @para bool $limpar_nomes Limpar os nomes dos arquivos, padr�o false
     * @return String nome do diret�rio onde ficaram as imagens redimensionadas
     */
    public function __construct($imagens=null, $alturas=array(), $larguras=array(), $pasta=null, $converterPara=null, $nome_padrao=null, $posicao_miniatura=null, $limpar_nomes=false)
    {
        // Popula
        $this->imagens = $imagens;
        $this->alturas = $alturas;
        $this->larguras = $larguras;
        $this->pasta = $pasta;
        $this->converterPara = $converterPara;
        $this->nome_padrao = trim($nome_padrao);
        $this->posicao_miniatura = $posicao_miniatura;
        $this->limpar_nomes = $limpar_nomes;
    }

    public function obterDiretorioNovasImagens()
    {
        return $this->novoDiretorio;
    }

    /**
     * M�todo que gera as novas imagens
     */
    public function gerarImagens()
    {
        $utils = new utils();

        if ( count($this->imagens) > 0 )
        {
            // Obt�m o nome do diret�rio onde ser�o salvas as imagens redimensionadas
            $pasta = $utils->gerarNomeDiretorio($this->pasta);

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
                    // Obt�m as dimens�es originais
                    list( $alturaOld,
                          $larguraOld ) = $this->obterAlturaLargura($imagem['tmp_name']);

                    // Calcula as novas dimens�es
                    list( $altura,
                          $largura ) = $this->calcularNovasDimensoes($alturaOld, $larguraOld, $alturaMax, $larguraMax);

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

                        if ( $this->limpar_nomes )
                        {
                            $nome_novo .= $utils->removeSpecialChars($nome);
                        }
                        else
                        {
                            $nome_novo .= $nome;
                        }
                    }
                    $nome_novo .= '.'.$extensao;

                    flog('---------------------------');
                    flog('Gerando imagem: '.$nome_novo.' em: '.$pasta, 'de largura: '.$largura, 'e de altura: '.$altura);
                    $this->gerarImagem($imagem, $altura, $largura, $pasta, $nome_novo, $this->converterPara);
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
    public function gerarImagem($imagem, $altura, $largura, $pasta, $nome, $converterPara)
    {
        // Carrega a classe canvas
        $img = new canvas();

        // Define que � pra converter extens�o
        $img->convert_to($converterPara);

        // Importa a imagem
        $img->load($imagem['tmp_name']); //load_url() para obter a partir da web

        // Redimensiona
        $img->resize($largura, $altura, "fill")->save($pasta.'/'.$nome);

        // Marca d'agua
//        $img->merge("example.png", array("right", "bottom"), 60)->show();

        return $pasta.'/'.$nome;
    }

    /**
     * Obt�m a altura e largura da imagem original
     * @param $imagem Objeto imagem do PHP
     * @return Array associativo de duas posi��es ('altura' e 'largura')
     */
    public function obterAlturaLargura($imagem)
    {
        list( $largura,
              $altura ) = getImageSize($imagem);

        if ( !$altura )
        {
            throw new Exception('N�o foi poss�vel obter a altura da imagem.');
        }
        if ( !$largura )
        {
            throw new Exception('N�o foi poss�vel obter a largura da imagem.');
        }

        return array( (int)$altura,
                      (int)$largura );
    }

    /**
     * Calcula as novas dimens�es da imagem
     * @param $altura Altura da imagem
     * @param $largura Largura da umagem
     * @param $alturaMax Altura m�xima
     * @param $larguraMax Largura m�xima
     * @return Array associativo de duas posi��es ('altura' e 'largura')
     */
    public function calcularNovasDimensoes($altura, $largura, $alturaMax=null, $larguraMax=null)
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

        return array( (int)$alturaMax,
                      (int)$larguraMax );
    }

    /**
     * Obt�m a extens�o do arquivo a partir do MIME
     * @param @image Diret�rio de uma imagem
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
