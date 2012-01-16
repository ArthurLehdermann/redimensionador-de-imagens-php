/* JS da popup de erro/informação */
function erro(mensagem)
{
    var tipo = 'err';
    addMensagem(tipo, mensagem);
}

function informacao(mensagem)
{
    var tipo = 'inf';
    addMensagem(tipo, mensagem);
}

function addMensagem(tipo, mensagem)
{
    document.getElementById(tipo).innerHTML += "<br />" + mensagem;
    document.getElementById(tipo).style.display = "block";
}

/**
 * Exibe/oculta uma div(ou qualquer outro elemento)
 */
function exibe_oculta(div)
{
    if ( document.getElementById(div).style.display == 'block' )
    {
        document.getElementById(div).style.display = 'none';
    }
    else
    {
        document.getElementById(div).style.display = 'block';
    }
}

/**
 * Valida int
 */
function somenteNumeros(field)
{
    if ( !(field.value > 0) )
    {
        alert('Somente números!');
        field.value='';
        field.focus();
    }
    else if ( field.value > 5000 )
    {
        alert('Tem certeza que quer uma imagem maior que 5000px ??');
        field.focus();
    }
}

/**
 * Adiciona um campo altura/largura
 */
var countTamanhos = 0;
function adicionaTamanhos()
{
    var span = document.getElementById('camposTamanho');
    var elements = span.getElementsByTagName('input');

    var label1 = document.createElement('label');
    label1.textContent = 'Altura máxima ' + (1+countTamanhos);
    var input1 = document.createElement('input');
    input1.setAttribute('type','text');
    input1.setAttribute('id','altura[]');
    input1.setAttribute('name','altura[]');
    input1.setAttribute('class','campoAlturaLargura botao');
    input1.setAttribute('onchange','somenteNumeros(this);');
    var label2 = document.createElement('label');
    label2.textContent = 'Largura máxima ' + (1+countTamanhos);
    var input2 = document.createElement('input');
    input2.setAttribute('type','text');
    input2.setAttribute('id','largura[]');
    input2.setAttribute('name','largura[]');
    input2.setAttribute('class','campoAlturaLargura botao');
    input2.setAttribute('onchange','somenteNumeros(this);');

    var miniatura = document.createElement('label');
    miniatura.textContent = 'Miniatura: ';
    miniatura.setAttribute('title', 'Se marcado adiciona o prefixo "thumb_" na frente do nome');
    campo_miniatura = document.createElement('input');
    campo_miniatura.setAttribute('type','radio');
    campo_miniatura.setAttribute('id', 'miniatura[]');
    campo_miniatura.setAttribute('name', 'miniatura[]');
    campo_miniatura.setAttribute('value', countTamanhos);
    campo_miniatura.setAttribute('title', 'Se marcado adiciona o prefixo "thumb_" na frente do nome');
 
    span.insertBefore(label1, elements[elements.length]);
    span.insertBefore(input1, elements[elements.length]);
    var br = document.createElement('br');
    span.insertBefore(br, elements[elements.length]);
    span.insertBefore(label2, elements[elements.length]);
    span.insertBefore(input2, elements[elements.length]);
    var br = document.createElement('br');
    span.insertBefore(br, elements[elements.length]);
    span.insertBefore(miniatura, elements[elements.length]);
    span.insertBefore(campo_miniatura, elements[elements.length]);
    var br = document.createElement('br');
    var br = document.createElement('br');
    span.insertBefore(br, elements[elements.length]);
    countTamanhos++;

    // Exive o botão "-"
    if ( countTamanhos > 1 )
    {
        document.getElementById('menos').style.display='block';
    }
}

/**
 * Remove 1 campo altura/largura
 */
function removeTamanhos()
{
    var span = document.getElementById('camposTamanho');
    var elements = span.getElementsByTagName('*');

    if ( countTamanhos > 1 )
    {
        for ( var i=0; i < 9; i++)
        {
            span.removeChild(elements[elements.length-1]);
        }

        countTamanhos--;
    }

    // Esconde o botão "-"
    if ( countTamanhos < 2 )
    {
        document.getElementById('menos').style.display='none';
    }
}

/**
 * Quando preenchido um campo de arquivo .ZIP, adiciona outro
 * <input type="file" id="compactadas[]" name="compactadas[]" class="campoFile">
 */
var count_zipField = 0;
function add_zipField()
{
    var form = document.getElementById('div_zips');
    var element = form.getElementsByTagName('input');

    var br = document.createElement('br');
    var label = document.createElement('label');
    label.textContent = (1+count_zipField) + '- ';
    label.setAttribute('style','width:25px;margin-top:3px;');
    var novo = document.createElement('input');
    novo.setAttribute('type','file');
    novo.setAttribute('id','compactadas[]');
    novo.setAttribute('name','compactadas[]');
    novo.setAttribute('onchange','validaZip(this);');
    novo.setAttribute('class','campoFile botao');

    form.insertBefore(label, element[element.length]);
    form.insertBefore(novo, element[element.length]);
    form.insertBefore(br, element[element.length]);
    count_zipField++;

    // Exive o botão "-"
    if ( count_zipField > 1 )
    {
        document.getElementById('menos_zips').style.display='block';
    }
}

/**
 * Remove campo de arquivo ZIP 
 */
function removeCampoZipField()
{
    var div = document.getElementById('div_zips');
    var elements = div.getElementsByTagName('*');

    if ( count_zipField > 1 )
    {
        for ( var i=0; i < 3; i++)
        {
            div.removeChild(elements[elements.length-1]);
        }

        count_zipField--;
    }

    // Esconde o botão "-"
    if ( count_zipField < 2 )
    {
        document.getElementById('menos_zips').style.display='none';
    }
}


function validaImagens(elemento)
{
    var tipo = elemento.value.substr(-4).toLowerCase();
    if ( (tipo != '.jpg') &&
         (tipo != 'jpeg') &&
         (tipo != '.bmp') &&
         (tipo != '.gif') &&
         (tipo != '.png') &&
         (tipo != '') )
    {
        alert('Formato inválido!');
        elemento.value = '';
    }
}

function validaZip(elemento)
{
    if ( elemento.value.substr(-4).toLowerCase() != '.zip' )
    {
        alert('Formato inválido!');
        elemento.value = '';
    }
}
