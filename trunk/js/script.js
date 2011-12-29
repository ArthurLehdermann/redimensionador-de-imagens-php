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
var countTamanhos = 1;
function adicionaTamanhos()
{
    var span = document.getElementById('camposTamanho');
    var elements = span.getElementsByTagName('input');

    var label1 = document.createElement('label');
    label1.textContent = 'Altura máxima ' + countTamanhos;
    var input1 = document.createElement('input');
    input1.setAttribute('type','text');
    input1.setAttribute('id','altura[]');
    input1.setAttribute('name','altura[]');
    input1.setAttribute('class','campoAlturaLargura');
    input1.setAttribute('onchange','somenteNumeros(this);');
    var label2 = document.createElement('label');
    label2.textContent = 'Largura máxima ' + countTamanhos;
    var input2 = document.createElement('input');
    input2.setAttribute('type','text');
    input2.setAttribute('id','largura[]');
    input2.setAttribute('name','largura[]');
    input2.setAttribute('class','campoAlturaLargura');
    input2.setAttribute('onchange','somenteNumeros(this);');
    
    span.insertBefore(label1, elements[elements.length]);
    span.insertBefore(input1, elements[elements.length]);
    var br = document.createElement('br');
    span.insertBefore(br, elements[elements.length]);
    span.insertBefore(label2, elements[elements.length]);
    span.insertBefore(input2, elements[elements.length]);
    var br = document.createElement('br');
    span.insertBefore(br, elements[elements.length]);
    countTamanhos++;
}

/**
 * Remove 1 campo altura/largura
 */
function removeTamanhos()
{
    var span = document.getElementById('camposTamanho');
    var elements = span.getElementsByTagName('*');

    if ( elements.length > 12 )
    {
        for ( var i=0; i < 6; i++)
        {
            span.removeChild(elements[elements.length-1]);
        }

        countTamanhos--;
    }
}

/**
 * Quando preenchido um campo de imagem, adiciona outro
 */
var count_fileField = 1;
function add_filefield()
{
    var form = document.getElementById('div_imagens');
    var element = form.getElementsByTagName('input');

    if ( (count_fileField == 1) || (element[element.length-1].value != '') )
    {
        var label = document.createElement('label');
        label.textContent = 'Nome da imagem ' + count_fileField;
        var br1 = document.createElement('br');
        var nome = document.createElement('input');
        nome.setAttribute('type', 'text');
        nome.setAttribute('id', 'nome_imagem[]');
        nome.setAttribute('name', 'nome_imagem[]');
        var br2 = document.createElement('br');
        var novo = document.createElement('input');
        novo.setAttribute('type','file');
        novo.setAttribute('id','imagens[]');
        novo.setAttribute('name','imagens[]');
        novo.setAttribute('class','campoFile');
        novo.setAttribute('onchange','add_filefield();');
        var br3 = document.createElement('br');
        var br4 = document.createElement('br');
        form.insertBefore(label, element[element.length]);
        form.insertBefore(br1, element[element.length]);
        form.insertBefore(nome, element[element.length]);
        form.insertBefore(br2, element[element.length]);
        form.insertBefore(novo, element[element.length]);
        form.insertBefore(br3, element[element.length]);
        form.insertBefore(br4, element[element.length]);
        count_fileField++;
    }
}
