<?php
require_once('utils.class.php');
require_once('imagem.class.php');
class zip
{
    /**
     * Compacta as imagens de um diret�rio
     * @param $dir Diret�rio das imagens a serem compactadas
     * @param $destDir Destino do arquivo.zip
     * @return string $newFIle Diret�rio do arquivo.zip com as imagens finais dentro
     */
    public function compactarImagens($dir, $destDir, $nomeDoArquivo)
    {
        $utils = new utils();
        flog('');
        flog('Compactando imagens...');
        if ( strlen($nomeDoArquivo) > 0 )
        {
            $nomeDoArquivo = 'imagens_redimensionadas';
        }
        $newFile = $destDir.'/'.$nomeDoArquivo.$utils->getRandomNumbers().'.zip';

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

        flog('imagens compactadas, dispon�vel em: '.$newFile);
        return $newFile;
    }

    /**
     * Descompacta as imagens
     * @param $dir Destino das imagens
     * @param $arquivo Arquivo .zip (caminho)
     */
    public function descompactarImagens($dir, $arquivo)
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
            $this->removeLixo($dest_dir);
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
    public function removeLixo($dir)
    {
        $utils = new utils();
        $imagem = new imagem();

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
                $this->removeLixo($file.'/');
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
                    $novoNome = $utils->removeSpecialChars($nomeDoArquivo);

                    // Extens�o
                    $fileNew = $base_dir.'/'.$novoNome.'.'.$imagem->obtem_extensao($file);

                    exec('mv '.str_replace(' ', '\\ ', $file).' '.$fileNew);
                }
            }
        }
    }

    /**
     * L� as imagens que est�o compactadas. Retorna um array associativo estilo $_FILES.
     * @param $dir Diret�rio onde ir�o as imagens do .ZIP
     * @param $arquivo Caminho completo onde est� o arquivo .ZIP das imagens
     * @return array Array com as imagens
     */
    public function obterFotosCompactadas($dir, $arquivo)
    {
        $utils = new utils();
        $dir = $utils->gerarNomeDiretorio($dir);
        // Cria um novo diret�rio
        $utils->criarDiretorio($dir);

        // Descompacta as imagens
        $this->descompactarImagens($dir, $arquivo);

        // Retorna um array com as imagens
        return $utils->obterArquivos($dir);
    }
}
?>
