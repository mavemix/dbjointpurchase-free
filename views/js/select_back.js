$(document).ready(function() {

    let tab = $('#related-product').parent();
    let titulo = $('<h2>Comprados juntos habitualmente</h2><p>Personaliza los productos que quieres que se recomienden con este producto</p>');
    let container = $('<ul class="category-tree"></ul>');
    
    $.ajax({
        type: 'POST',
        url: controller_link,
        dataType: 'html',
        async: true,
        data: {
          ajax : true,
          product : product,
          joints: joints,
          action : 'charge'
        },
        success: function(response)
        {   
            tab.append(titulo);
            tab.append(container.append(response));
        },
        error: function(response)
        {
            tab.append("No se pueden cargar los productos");
        }
    });

    $(document).on('click', '.jointCheckbox', function() {

        let checkbox = $(this);

        $.ajax({
            type: 'POST',
            url: controller_link,
            dataType: 'json',
            async: true,
            data: {
              ajax : true,
              product : product,
              joints: products_cat,
              value : this.value,
              checked : this.checked,
              action : 'check'
            },
            success: function(response)
            {
                if(!response[0]) {
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    error.css('font-color', 'red');

                } 
            }
        });
    })
});