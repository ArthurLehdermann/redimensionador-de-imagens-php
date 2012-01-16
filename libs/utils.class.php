<?php
class utils
{
    /**
     * Fun��o que cria diret�rios
     * @param $dir Diret�rio a ser criado
     * @return boolean
     */
    public function criarDiretorio($dir)
    {
        $ok = false;
        $array_dir = (array)explode('/', $dir);
        // Posi��o [0] pra base_dir
        $base_dir = '/'.array_shift($array_dir);

        // Cria o(s) diret�rio(s)
        while ( is_array($array_dir) && (count($array_dir) > 0) )
        {
            // Posi��o [1] pra current_dir
            $current_dir = array_shift($array_dir);

            // Verifica se j� existe o diret�rio
            if ( !in_array($current_dir, scandir($base_dir)) )
            {
                exec('mkdir '.$base_dir.$current_dir);

                // Criou com sucesso
                if ( in_array($current_dir, scandir($base_dir)) )
                {
                    $ok = true;
                }
            }
            // J� existia
            else
            {
                $ok = true;
            }

            // Atualiza o base_dir
            $base_dir .= $current_dir.'/';
        }

        return $ok;
    }

    /**
     * Gera um nome de diret�rio aleat�rio
     * @param $baseDir Diret�rio onde ir� o novo diret�rio
     * @return String
     */
    public function gerarNomeDiretorio($baseDir)
    {
        $data = date('Y-m-d');

        // Cria uma pasta por dia
        if ( !in_array($data, (array)scandir($baseDir)) )
        {
            exec('mkdir '.$baseDir.'/'.$data);
            $baseDir .= '/'.$data;
        }

        // Cria uma pasta por IP
        if ( !in_array($_SERVER['REMOTE_ADDR'], (array)scandir($baseDir)) )
        {
            exec('mkdir '.$baseDir.'/'.$_SERVER['REMOTE_ADDR']);
            $baseDir .= '/'.$_SERVER['REMOTE_ADDR'];
        }

        // Gera um nome aleat�rio (6 numeros)
        $nome = $this->getRandomNumbers();

        // Caso j� exista gera outro
        while ( in_array($nome, (array)scandir($baseDir)) )
        {
            $nome = $this->getRandomNumbers();
        }

        $nome = $baseDir.'/'.$nome;

        return $nome;
    }

    /**
     * Get 6 random numbers
     */
    public function getRandomNumbers()
    {
        return rand(100,999).rand(100,999);
    }

    /**
     * Organiza um array $_FILES
     */
    public function arrumaArrayFiles($array)
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

    /**
     * Retorna um array com todos os arquivos de um diret�rio
     * @param $dir Diret�rio de onde ser� lido os arquivos
     * @return array
     */
    public function obterArquivos($dir)
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
                $imagens = array_merge($imagens, $this->obterArquivos($file));
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
    public function removeSpecialChars($oldText)
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
            $newText = 'nome_invalido-'.$this->getRandomNumbers().$this->getRandomNumbers();
        }

        return $newText;
    }
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
