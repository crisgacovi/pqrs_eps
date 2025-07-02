// Función para agregar nuevo campo de email
$(document).on('click', '.add-email', function() {
    const emailField = `
        <div class="input-group mb-2">
            <input type="email" class="form-control" name="emails[]" placeholder="ejemplo@eps.com">
            <button type="button" class="btn btn-danger remove-email"><i class="bi bi-trash"></i></button>
        </div>
    `;
    $('#emailContainer').append(emailField);
});

// Función para remover campo de email
$(document).on('click', '.remove-email', function() {
    $(this).closest('.input-group').remove();
});