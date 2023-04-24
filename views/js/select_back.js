$(document).ready(function() {
    let tab = $('#related-product').parent();
    
    let titulo = $('<h2>Comprados juntos habitualmente</h2>');

    let container = $('<ul class="category-tree">'+product+'</ul>');

    let contenido = "";

    products_cat.forEach(products => {
        products.forEach(product => {
            contenido += '<li><div class="checkbox"><label><input class = "jointCheckbox" type="checkbox" ';
            contenido += 'value="' + product.id_product + '">' + product.name;
            contenido += '</label></div></li>';
        })
    });
    
    tab.append(titulo);
    tab.append(container.append(contenido));

    $(document).on('click', '.jointCheckbox', function() {

        $.ajax({
            type: 'POST',
            url: controller_link,
            dataType: 'json',
            async: true,
            data: {
              ajax : true,
              product : product,
              value : this.value,
              checked : this.checked
            },
            success: function(jsonData)
            {
            }
        });
    })
});