<?php
require_once('utils.class.php');
require_once('canvas/canvas.php');

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
     * @param Array $imagens Array de array associativo com as imagens(estilo $_FILES). Parâmetro obrigatório.
     * Para alterar o nome da nova imagem, existe a possibilidade de passar um novo nome na posição 'novo_nome'.
     * @param Array $alturas Array com as alturas das imagens (isto define quantos redimensionamentos serão feitos)
     * @param Array $larguras Array com as larguras das imagens (isto define quantos redimensionamentos serão feitos)
     * @param String $pasta Diretrio onde as imagens serão "jogadas" (padrão 'media' ou 'imagens'). Não precisa '/' no fim.
     * @param String $converterPara Converter para 'jpg' ou para 'png'
     * @para bool $limpar_nomes Limpar os nomes dos arquivos, padrão false
     * @return String nome do diretório onde ficaram as imagens redimensionadas
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
                    // Obtém as dimensões originais
                    list( $alturaOld,
                          $larguraOld ) = $this->obterAlturaLargura($imagem['tmp_name']);

                    // Calcula as novas dimensões
                    list( $altura,
                          $largura ) = $this->calcularNovasDimensoes($alturaOld, $larguraOld, $alturaMax, $larguraMax);

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
     * Gera a imagem com as dimensões definidas
     * @param $imagem Array associativo com as imagens(estilo $_FILES). Parâmetro obrigatório.
     * Para alterar o nome da nova imagem, existe a possibilidade de passar um novo nome na posição 'novo_nome'.
     * @param $altura Altura máxima da imagens. Caso em não informado, mantém altura original.
     * @param $largura Largura da imagem. Caso em não informado, mantém a largura original.
     * @param $pasta Diretrio onde a imagem será salva (padrão 'media' ou 'imagens'). Não precisa '/' no fim.
     * @param $converterPara Converter para 'jpg' ou para 'png'. Padrão NULL (mantém formato original.
     * @return String nome do diretório completo da imagem redimensionada.
     */
    public function gerarImagem($imagem, $altura, $largura, $pasta, $nome, $converterPara)
    {
        // Carrega a classe canvas
        $img = new canvas();

        // Define que é pra converter extensão
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
     * Obtém a altura e largura da imagem original
     * @param $imagem Objeto imagem do PHP
     * @return Array associativo de duas posições ('altura' e 'largura')
     */
    public function obterAlturaLargura($imagem)
    {
        list( $largura,
              $altura ) = getImageSize($imagem);

        if ( !$altura )
        {
            throw new Exception('Não foi possível obter a altura da imagem.');
        }
        if ( !$largura )
        {
            throw new Exception('Não foi possível obter a largura da imagem.');
        }

        return array( (int)$altura,
                      (int)$largura );
    }

    /**
     * Calcula as novas dimensões da imagem
     * @param $altura Altura da imagem
     * @param $largura Largura da umagem
     * @param $alturaMax Altura máxima
     * @param $larguraMax Largura máxima
     * @return Array associativo de duas posições ('altura' e 'largura')
     */
    public function calcularNovasDimensoes($altura, $largura, $alturaMax=null, $larguraMax=null)
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

        return array( (int)$alturaMax,
                      (int)$larguraMax );
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
